window.addEventListener('DOMContentLoaded', function () {
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
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-cache',
        body: 'action=lnp_check_payment&token=' + invoice.token,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
      })
       .then(function (response) {
         if (response.ok) {
            response.json().then(function (content) {
              showContent(invoice.post_id, content);
            });
         }
       });
    };
  }

  function showContent(postId, content) {
    stopWatchingForPayment();
    var wrapper = document.querySelector('.wp-lnp-wrapper[data-lnp-postid="' + postId + '"]');
    if (wrapper) {
      wrapper.outerHTML = content;
    }
  }

  function showQRCode(invoice) {
    var button = document.querySelector('.wp-lnp-wrapper[data-lnp-postid="' + invoice.post_id + '"] button.wp-lnp-btn');
    button.outerHTML = '<div class="wp-lnp-qrcode"><img src="https://chart.googleapis.com/chart?&chld=M|0&cht=qr&chs=200x200&chl=' +invoice.payment_request + '"></div>';
  }

  function requestInvoice(postId) {
    fetch(wp_ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-cache',
      body: 'action=lnp_invoice&post_id=' + postId,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    })
    .then(function (resp) { return resp.json(); })
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
      this.setAttribute("disabled", "");

      var wrapper = this.closest(".wp-lnp-wrapper");
      var autopayInput = wrapper.querySelector("input.wp-lnp-autopay:checked");

      if (autopayInput) {
        localStorage.setItem("wplnp_autopay", true);
      }
      requestInvoice(wrapper.dataset.lnpPostid);
    });

  if (localStorage.getItem("wplnp_autopay")) {
    var wrappers = document.querySelectorAll('.wp-lnp-wrapper[data-lnp-postid]');
    if (wrappers.length === 1) {
      var wrapper = wrappers[0]
      var button = wrapper.querySelector("button.wp-lnp-btn");
      button.setAttribute("disabled", "");
      requestInvoice(wrapper.dataset.lnpPostid);
    }
  }
});
