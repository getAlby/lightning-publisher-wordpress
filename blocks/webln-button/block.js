(function (blocks, i18n, element, components, _, blockEditor) {
    var __ = i18n.__;
    var el = element.createElement;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType(
        'alby/webln-button', {
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
                success_message: {
                    type: 'string',
                },
            },
            edit: function (props) {
                const {
                    amount,
                    currency,
                    button_text,
                    success_message,
                } = props.attributes;

                return el(
                    'div',
                    useBlockProps({ className: props.className }),
                    [
                    el('h4', {}, "Donation Button"),
                    el(
                        TextControl, {
                            label: __("Amount (in the smallest unit (sats/cents e.g. for $4.90 it is 490))", "alby"),
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
                            label: __("Success message", "alby"),
                            onChange: (v) => {
                                props.setAttributes({ success_message: v });
                            },
                            placeholder: "Thanks",
                            value: success_message,
                        }
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
