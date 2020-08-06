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
        startWatchingForPayment(invoice);
      })
      .catch(function(err) {
        stopWatchingForPayment();
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
    return function() {
      $.post(wp_ajax_url, { action: 'lnp_check_payment', token: invoice.token })
        .success(function(content) {
          showContent(invoice.post_id, content);
        });
    }
  }

  function showContent(postId, content) {
    stopWatchingForPayment();
    $('.wp-lnp-wrapper[data-lnp-postid=' + postId + ']').replaceWith(content);
  }

  function requestInvoice(postId) {
    $.post(wp_ajax_url, { action: 'lnp_invoice', post_id: postId})
      .success(function(invoice) { pay(invoice); })
      .fail(function(err) { throw err })
  }

  $('[data-lnp-postid] button.wp-lnp-btn').click(function(e) {
    e.preventDefault();
    var button = $(this);
    button.attr('disabled', true);

    var wrapper = button.closest('.wp-lnp-wrapper');
    var autopayInput = wrapper.find('input.wp-lnp-autopay:checked');

    if (autopayInput.length) {
      localStorage.setItem('wplnp_autopay', true);
    }
    requestInvoice(wrapper.data('lnp-postid'));
  });


  if (localStorage.getItem('wplnp_autopay')) {
    var wrappers = $('.wp-lnp-wrapper[data-lnp-postid]')
    if (wrappers.length === 1) {
      var wrapper = wrappers.first();
      var button = wrapper.find('button.wp-lnp-btn');
      button.attr('disabled', true);
      requestInvoice(wrappers.first().data('lnp-postid'));
    }
  }
});
