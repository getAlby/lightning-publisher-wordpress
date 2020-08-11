jQuery(function ($) {
  var checkPaidInterval = null;
  var wp_ajax_url = LN_Paywall.ajax_url;

  function pay(invoice) {
    return WebLN.requestProvider()
      .then(function (webln) {
        webln.sendPayment(invoice.payment_request).catch(function (e) {
          stopWatchingForPayment();
        });
        startWatchingForPayment(invoice);
      })
      .catch(function (err) {
        showQRCode(invoice);
        startWatchingForPayment(invoice);
        console.log(err);
      });
  }

  function startWatchingForPayment(invoice) {
    stopWatchingForPayment();
    checkPaidInterval = setInterval(checkPaymentStatus(invoice), 800);
  }

  function stopWatchingForPayment() {
    if (checkPaidInterval) {
      clearTimeout(checkPaidInterval);
      checkPaidInterval = null;
    }
  }

  function checkPaymentStatus(invoice) {
    return function () {
      fetch(wp_ajax_url, {
        method: "POST",
        body: JSON.stringify({
          action: "lnp_check_payment",
          token: invoice.token,
        }),
      }).then(function (content) {
        showContent(invoice.post_id, content);
      });
    };
  }

  function showContent(postId, content) {
    stopWatchingForPayment();
    document
    .querySelector(".wp-lnp-wrapper[data-lnp-postid=" + postId + "]").replaceWith(content);
  }

  function showQRCode(invoice) {
    var wrapper = $(".wp-lnp-wrapper[data-lnp-postid=" + invoice.post_id + "]");
    var button = wrapper.find("button.wp-lnp-btn");
    button.replaceWith(
      '<div class="wp-lnp-qrcode"><img src="https://chart.googleapis.com/chart?&chld=M|0&cht=qr&chs=200x200&chl=' +
        invoice.payment_request +
        '"></div>'
    );
  }

  function requestInvoice(postId) {
    fetch(wp_ajax_url, {
      method: "POST",
      body: JSON.stringify({ action: "lnp_invoice", post_id: postId }),
    })
      .then(function (invoice) {
        pay(invoice);
      })
      .catch(function (err) {
        throw err;
      });
  }

  document
    .querySelector("[data-lnp-postid] button.wp-lnp-btn")
    .addEventListener("click", function (e) {
      e.preventDefault();
      var button = e.target;
      button.attr("disabled", true);

      var wrapper = button.closest(".wp-lnp-wrapper");
      var autopayInput = wrapper.find("input.wp-lnp-autopay:checked");

      if (autopayInput.length) {
        localStorage.setItem("wplnp_autopay", true);
      }
      requestInvoice(wrapper.data("lnp-postid"));
    });

  if (localStorage.getItem("wplnp_autopay")) {
    var wrappers = document.getElementByClassName(
      ".wp-lnp-wrapper[data-lnp-postid]"
    );
    if (wrappers.length === 1) {
      var wrapper = wrappers.first();
      var button = wrapper.find("button.wp-lnp-btn");
      button.attr("disabled", true);
      requestInvoice(wrappers.first().data("lnp-postid"));
    }
  }
});
