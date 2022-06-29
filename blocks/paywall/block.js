(function(blocks, editor, i18n, element, components, _, blockEditor) {
  var __ = i18n.__;
  var el = element.createElement;
  var TextControl = components.TextControl;
  var useBlockProps = blockEditor.useBlockProps;
  var BlockControls = blockEditor.BlockControls;

  blocks.registerBlockType('alby/paywall', {
    attributes: {
      amount: {
        type: 'number',
      },
      button_text: {
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
    edit: function(props) {
      const {
        amount,
        button_text,
        timeout,
        timein,
        total
      } = props.attributes;
      return el(
        'div',
        useBlockProps({
          className: props.className
        }),
        el(
          BlockControls, {
            key: 'controls'
          },
        ),
        el(
          TextControl, {
            label: __("Amount", "alby"),
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
            label: __("Disable the paywall x hours after publishing", "alby"),
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
            label: __("Enable the paywall only for x hours after publishing", "alby"),
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
            label: __("Disable paywall after X Satoshis have been received", "alby"),
            onChange: (v) => {
              if (v !== "") {
                v = parseInt(v);
              }
              props.setAttributes({ total: v });
            },
            value: total,
          }
        ),
        el('hr', {
          className: "lnp-alby-paywall-widget"
        })
      );

    },
    save: function(props) {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.editor,
  window.wp.i18n,
  window.wp.element,
  window.wp.components,
  window._,
  window.wp.blockEditor
);
