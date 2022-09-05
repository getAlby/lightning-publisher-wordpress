document.addEventListener("DOMContentLoaded", function(event) {
    (function () {
        var checkPaidInterval = null;
        var wp_rest_base_url = LN_Paywall.rest_base;
        var LN_Paywall_Spinner =
        '<svg class="LNP_spinner" viewBox="0 0 50 50"><circle class="LNP_path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>';

        function pay(invoice, options)
        {
            if (!window.webln) {
                showQRCode(invoice, options);
                return startWatchingForPayment(invoice);
            }
            return window.webln.enable()
            .then(
                function () {
                    window.webln
                    .sendPayment(invoice.payment_request)
                    .then(
                        (response) => {
                            window.LNP_CURRENT_PREIMAGE =
                            response.preimage || response.payment_preimage;
                        }
                    )
                    .catch(
                        function (e) {
                            console.error(e);
                            showQRCode(invoice, options);
                        }
                    );
                    return startWatchingForPayment(invoice);
                }
            )
            .catch(
                function (err) {
                    console.error(err);
                    showQRCode(invoice, options);
                    return startWatchingForPayment(invoice);
                }
            );
        }

        function startWatchingForPayment(invoice)
        {
            stopWatchingForPayment();
            return new Promise(
                function (resolve, reject) {
                    checkPaidInterval = setInterval(
                        checkPaymentStatus(invoice, resolve),
                        2500
                    );
                }
            );
        }

        function stopWatchingForPayment()
        {
            window.LNP_CURRENT_PREIMAGE = null;
            if (checkPaidInterval) {
                clearTimeout(checkPaidInterval);
                checkPaidInterval = null;
            }
        }

        function checkPaymentStatus(invoice, callback)
        {
            if (!invoice || !invoice.token) {
                console.log("Ligthning invoice missing");
                return;
            }
            return function () {
                let body = {
                    token: invoice.token,
                    preimage: window.LNP_CURRENT_PREIMAGE,
                    t: Date.now()
                };
                fetch(
                    wp_rest_base_url+'/paywall/verify', {
                        method: "POST",
                        credentials: "same-origin",
                        cache: "no-cache",
                        body: JSON.stringify(body),
                        headers: {
                            "Content-Type": "application/json",
                        },
                    }
                ).then(
                    function (response) {
                        if (response.ok) {
                            response.json().then(
                                function (content) {
                                    stopWatchingForPayment();
                                    callback(content, invoice);
                                }
                            );
                        }
                    }
                );
            };
        }

        function showQRCode(invoice, options)
        {
            var button = options.target.querySelector("button.wp-lnp-btn");
            button.outerHTML = `<div class="wp-lnp-qrcode">
            <a href="lightning:${invoice.payment_request
            }"><img src="https://chart.googleapis.com/chart?&chld=M|0&cht=qr&chs=200x200&chl=${invoice.payment_request
            }"></a>
            <br />
            <a href="lightning:${invoice.payment_request
            }">${invoice.payment_request.substr(0, 36)}...</a>
            </div>`;
        }

        function requestPayment(params, options)
        {
            return fetch(
                wp_rest_base_url+'/paywall/pay', {
                    method: "POST",
                    credentials: "same-origin",
                    cache: "no-cache",
                    body: JSON.stringify(params),
                    headers: {
                        "Content-Type": "application/json",
                    },
                }
            )
                .then(
                    function (resp) {
                        return resp.json();
                    }
                )
                .then(
                    function (invoice) {
                        if (!invoice || !invoice.payment_request) {
                            console.log("Failed to generate lightning invoice", invoice);
                            return;
                        }
                        return pay(invoice, options);
                    }
                );
        }

        function initPostPaywalls()
        {
            var buttons = document.querySelectorAll(
                "[data-lnp-postid] button.wp-lnp-btn"
            );
            if (buttons.length === 0) {
                return;
            }
            buttons.forEach(
                function (button) {
                    button.addEventListener(
                        "click", function (e) {
                            e.preventDefault();
                            this.setAttribute("disabled", "");

                            this.innerHTML = LN_Paywall_Spinner;
                            var wrapper = this.closest(".wp-lnp-wrapper");

                            requestPayment(
                                { post_id: wrapper.dataset.lnpPostid },
                                { target: wrapper }
                            )
                            .then(
                                function (content, invoice) {
                                    wrapper.outerHTML = content;
                                }
                            )
                            .catch(
                                function (e) {
                                    console.log(e);
                                    alert("sorry, something went wrong.");
                                }
                            );
                        }
                    );
                }
            );
        }

        initPostPaywalls();
    })();
});
