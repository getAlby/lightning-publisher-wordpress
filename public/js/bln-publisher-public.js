document.addEventListener('DOMContentLoaded', function (event) {
    (function () {
        var checkPaidInterval = null;
        var wp_rest_base_url = LN_Paywall.rest_base;
        var LN_Paywall_Spinner =
            '<svg class="LNP_spinner" viewBox="0 0 50 50"><circle class="LNP_path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>';
        var LN_Paywall_Copy =
            '<svg xmlns="http://www.w3.org/2000/svg" class="LNP_copy" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path class="LNP_path" stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>';

        function pay(invoice, options) {
            if (!window.webln) {
                showQRCode(invoice, options);
                return startWatchingForPayment(invoice);
            }
            return window.webln
                .enable()
                .then(function () {
                    window.webln
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
            if (!invoice || !invoice.token) {
                console.log('Ligthning invoice missing');
                return;
            }
            return function () {
                let body = {
                    token: invoice.token,
                    preimage: window.LNP_CURRENT_PREIMAGE,
                    t: Date.now(),
                };
                fetch(wp_rest_base_url + '/paywall/verify', {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-cache',
                    body: JSON.stringify(body),
                    headers: {
                        'Content-Type': 'application/json',
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
            var button = options.target.querySelector('button.wp-lnp-btn');
            button.outerHTML = `<div class="wp-lnp-qrcode">
            <a href="lightning:${
                invoice.payment_request
            }"><img src="https://quickchart.io/chart?cht=qr&chs=200x200&chl=${
              invoice.payment_request
            }"></a>
            <br />
            <a href="lightning:${
                invoice.payment_request
            }">${invoice.payment_request.substr(
                0,
                36
            )}...</a><span onClick="navigator.clipboard.writeText('${
                invoice.payment_request
            }');" class="wp-lnp-copy">${LN_Paywall_Copy}</span>
            </div>`;
        }

        function requestPayment(params, options) {
            return fetch(wp_rest_base_url + '/paywall/pay', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-cache',
                body: JSON.stringify(params),
                headers: {
                    'Content-Type': 'application/json',
                },
            })
                .then(function (resp) {
                    return resp.json();
                })
                .then(function (invoice) {
                    if (!invoice || !invoice.payment_request) {
                        console.error(
                            'Failed to generate lightning invoice',
                            invoice
                        );
                        throw new Error(
                            'Failed to generate lightning invoice ' +
                                JSON.stringify(invoice)
                        );
                    }
                    return pay(invoice, options);
                });
        }

        function initPostPaywalls() {
            var buttons = [].slice.call(
                document.querySelectorAll('.wp-lnp-btn')
            );
            if (buttons.length === 0) {
                return;
            }
            buttons.forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    this.setAttribute('disabled', '');

                    this.innerHTML = LN_Paywall_Spinner;
                    var wrapper = this.closest('.wp-lnp-wrapper');

                    requestPayment(
                        { post_id: parseInt(window.LN_Paywall.post_id) },
                        { target: wrapper }
                    )
                        .then(function (content, invoice) {
                            wrapper.outerHTML = content;
                        })
                        .catch(function (e) {
                            console.log(e);
                            alert('sorry, something went wrong.');
                        });
                });
            });
        }

        initPostPaywalls();
    })();
});
