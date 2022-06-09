window.addEventListener("DOMContentLoaded", function () {
  var checkPaidInterval = null;
  var wp_ajax_url = LN_Paywall.ajax_url;
  var LN_Paywall_Spinner =
    '<svg class="LNP_spinner" viewBox="0 0 50 50"><circle class="LNP_path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>';

  function pay(invoice, options) {
    return WebLN.requestProvider()
      .then(function (webln) {
        webln
          .sendPayment(invoice.payment_request)
          .then((response) => {
            window.LNP_CURRENT_PREIMAGE =
              response.preimage || response.payment_preimage;
          })
          .catch(function (e) {
            console.error(e);
            showQRCode(invoice, options);
          });
        return startWatchingForPayment(invoice);
      })
      .catch(function (err) {
        console.error(err);
        showQRCode(invoice, options);
        return startWatchingForPayment(invoice);
      });
  }

  function startWatchingForPayment(invoice) {
    stopWatchingForPayment();
    return new Promise(function (resolve, reject) {
      checkPaidInterval = setInterval(
        checkPaymentStatus(invoice, resolve),
        2500
      );
    });
  }

  function stopWatchingForPayment() {
    window.LNP_CURRENT_PREIMAGE = null;
    if (checkPaidInterval) {
      clearTimeout(checkPaidInterval);
      checkPaidInterval = null;
    }
  }

  function checkPaymentStatus(invoice, callback) {
    return function () {
      fetch(wp_ajax_url, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-cache",
        body:
          "action=lnp_check_payment&token=" +
          invoice.token +
          "&preimage=" +
          window.LNP_CURRENT_PREIMAGE,
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
      }).then(function (response) {
        if (response.ok) {
          response.json().then(function (content) {
            stopWatchingForPayment();
            callback(content, invoice);
          });
        }
      });
    };
  }

  function showQRCode(invoice, options) {
    var button = options.target.querySelector("button.wp-lnp-btn");
    button.outerHTML = `<div class="wp-lnp-qrcode">
      <img src="https://chart.googleapis.com/chart?&chld=M|0&cht=qr&chs=200x200&chl=${
        invoice.payment_request
      }">
      <br />
      <a href="lightning:${
        invoice.payment_request
      }">${invoice.payment_request.substr(0, 36)}...</a>
      </div>`;
  }

  function requestPayment(params, options) {
    var paramsQueryString = Object.keys(params)
      .map((key) => `${key}=${params[key]}`)
      .join("&");

    return fetch(wp_ajax_url, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-cache",
      body: "action=lnp_invoice&" + paramsQueryString,
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
    })
      .then(function (resp) {
        return resp.json();
      })
      .then(function (invoice) {
        return pay(invoice, options);
      });
  }

  function initPostPaywalls() {
    var buttons = document.querySelectorAll(
      "[data-lnp-postid] button.wp-lnp-btn"
    );
    if (buttons.length === 0) {
      return;
    }
    buttons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        this.setAttribute("disabled", "");

        this.innerHTML = LN_Paywall_Spinner;
        var wrapper = this.closest(".wp-lnp-wrapper");
        var autopayInput = wrapper.querySelector(
          "input.wp-lnp-autopay:checked"
        );

        if (autopayInput) {
          localStorage.setItem("wplnp_autopay", true);
        }
        requestPayment(
          { post_id: wrapper.dataset.lnpPostid },
          { target: wrapper }
        )
          .then(function (content, invoice) {
            wrapper.outerHTML = content;
          })
          .catch(function (e) {
            console.log(e);
            alert("sorry, something went wrong.");
          });
      });
    });
  }

  function initAllPaywalls() {
    var buttonsForAll = document.querySelectorAll(
      ".wp-lnp-all button.wp-lnp-btn"
    );
    if (buttonsForAll.length === 0) {
      return;
    }
    buttonsForAll.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        this.setAttribute("disabled", "");

        var wrapper = this.closest(".wp-lnp-all");
        requestPayment({ all: "1" }, { target: wrapper })
          .then(function (content, invoice) {
            wrapper.innerHTML =
              '<p class="wp-all-confirmation">' + content + "</p>";
          })
          .catch(function (e) {
            console.log(e);
            alert("Sorry, something went wrong.");
          });
      });
    });
  }

  function autopay() {
    if (localStorage.getItem("wplnp_autopay")) {
      var wrappers = document.querySelectorAll(
        ".wp-lnp-wrapper[data-lnp-postid]"
      );
      if (wrappers.length === 1) {
        var wrapper = wrappers[0];
        var button = wrapper.querySelector("button.wp-lnp-btn");
        button.setAttribute("disabled", "");
        requestPayment({ post_id: wrapper.dataset.lnpPostid })
          .then(function (content, invoice) {
            wrapper.outerHTML = content;
          })
          .catch(function (e) {
            console.log(e);
            alert("Sorry, something went wrong.");
          });
      }
    }
  }

  initPostPaywalls();
  initAllPaywalls();
  autopay();
});
