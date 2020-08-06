jQuery(function($) {

  var checkPaidInterval = null;
  var wp_ajax_url       = LN_Paywall.ajax_url;

  function pay(invoice) {
    return WebLN.requestProvider()
      .then(function(webln) {
        webln.sendPayment(invoice.payment_request)
          .catch(function(e) {
            stopWatchingForPayment();
          });
        startWatchingForPayment(invoice.token);
      })
      .catch(function(err) {
        stopWatchingForPayment();
      });
  }

  function startWatchingForPayment(token) {
    stopWatchingForPayment();
    checkPaidInterval = setInterval(checkPaymentStatus(token), 800);
  }

  function stopWatchingForPayment() {
    if (checkPaidInterval) {
      clearTimeout(checkPaidInterval);
      checkPaidInterval = null;
    }
  }

  function checkPaymentStatus(token) {
    return function() {
      $.post(wp_ajax_url, { action: 'lnp_check_payment', token: token })
        .success(function(content) {
          showContent(content);
        })
    }
  }

  function showContent(content) {
    stopWatchingForPayment();
    $('#wp-lnp-wrapper').replaceWith(content);
  }

  function requestInvoice(postId) {
    $.post(wp_ajax_url, { action: 'lnp_invoice', post_id: postId})
      .success(function(invoice) {
        pay(invoice);
      })
      .fail(function(err) { throw err })
  }

  $('[data-lnp-postid]').click(function(e) {
    e.preventDefault();
    var t = $(this);
    t.attr('disabled', true);

    if ($('#wp-lnp-autopay').val() === '1') {
      localStorage.setItem('wplnp_autopay', true);
    }
    requestInvoice(t.data('lnp-postid'));
  });

  if (localStorage.getItem('wplnp_autopay')) {
    var element = $('[data-lnp-postid]').first();
    if (element.length) {
      console.log(element);
      console.log(element.data());
      requestInvoice(element.data('lnp-postid'));
    }
  }

})
