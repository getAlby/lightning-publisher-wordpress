(function (blocks, i18n, element, components, _, blockEditor) {
    var __ = i18n.__;
    var el = element.createElement;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType(
        'alby/paywall', {
            attributes: {
                amount: {
                    type: 'number',
                },
                currency: {
                    type: 'string',
                },
                button_text: {
                    type: 'string',
                },
                description: {
                    type: 'string',
                },
                timeout: {
                    type: 'number',
                },
                timein: {
                    type: 'number',
                },
                total: {
                    type: 'number',
                },
            },
            edit: function (props) {
                const {
                    amount,
                    currency,
                    button_text,
                    description,
                    timeout,
                    timein,
                    total
                } = props.attributes;

                return el(
                    'div',
                    useBlockProps({ className: props.className }),
                    [
                    el('h4', {}, "Lightning Paywall"),
                    el(
                        'p',
                        { },
                        'Settings (leave blank to use your default config):'
                    ),
                    el(
                        TextControl, {
                            label: __("Amount (in smallest unit (e.g. cents))", "alby"),
                            onChange: (v) => {
                                if (v !== "") {
                                    v = parseInt(v);
                                }
                                props.setAttributes({ amount: v });
                            },
                            value: amount
                        }
                    ),
                    el(
                        SelectControl, {
                            label: __("Currency", "alby"),
                            onChange: (v) => {
                                props.setAttributes({ currency: v });
                            },
                            value: currency,
                            options: [
                                { value: '', label: '' },
                                { value: 'btc', label: 'BTC (sats)' },
                                { value: 'usd', label: 'USD' },
                                { value: 'eur', label: 'EUR' },
                                { value: 'gbp', label: 'GBP' },
                            ]
                        }
                    ),
                    el(
                        TextControl, {
                            label: __("Button Label", "alby"),
                            onChange: (v) => {
                                props.setAttributes({ button_text: v });
                            },
                            value: button_text,
                        }
                    ),
                    el(
                        TextControl, {
                            label: __("Description", "alby"),
                            onChange: (v) => {
                                props.setAttributes({ description: v });
                            },
                            value: description,
                        }
                    ),
                    el(
                        TextControl, {
                            label: __("Remove paywall and make the article free X hours after it is published", "alby"),
                            onChange: (v) => {
                                if (v !== "") {
                                    v = parseInt(v);
                                }
                                props.setAttributes({ timeout: v });
                            },
                            value: timeout,
                        }
                    ),
                    el(
                        TextControl, {
                            label: __("Remove paywall and make the article free for X hours.", "alby"),
                            onChange: (v) => {
                                if (v !== "") {
                                    v = parseInt(v);
                                }
                                props.setAttributes({ timein: v });
                            },
                            value: timein,
                        }
                    ),
                    el(
                        TextControl, {
                            label: __("Total amount to collect.", "alby"),
                            onChange: (v) => {
                                if (v !== "") {
                                    v = parseInt(v);
                                }
                                props.setAttributes({ total: v });
                            },
                            value: total,
                        }
                    ),
                    el(
                        'p',
                        { className: 'alby-paywall-note' },
                        'Any content below this will be behind the lightning paywall'
                    ),
                    ]
                );
            },
            save: function (props) {
                return null;
            },
        }
    );
})(
    window.wp.blocks,
    window.wp.i18n,
    window.wp.element,
    window.wp.components,
    window._,
    window.wp.blockEditor
);
