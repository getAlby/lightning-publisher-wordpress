( function ( blocks, editor, i18n, element, components, _, blockEditor ) {
  var __ = i18n.__;
  var el = element.createElement;
  var TextControl = components.TextControl;
  var useBlockProps = blockEditor.useBlockProps;

  blocks.registerBlockType( 'alby/twentyuno-widget', {
    title: __( 'Twentyuno Payment Widget', 'alby' ),
    icon: 'index-card',
    category: 'layout',
    attributes: {
      name: {
        type: 'string',
      },
      color: {
        type: 'string',
      },
      image: {
        type: 'string',
      },
    },
    example: {
      attributes: {
        name: "Your name",
        image: "image URL",
        color: "background color",
      },
    },

    edit: function ( props ) {
      var name = props.attributes.name;
      var color = props.attributes.color;
      var image = props.attributes.image;

      return el(
        'div',
        useBlockProps( { className: props.className } ),
        [
          el('h4', {}, "Twentyuno Payment Widget"),
          el(
            TextControl,
            {
              label: __("Name", "alby"),
              onChange: (v) => { console.log(v); props.setAttributes({name: v}) },
              value: name,
            }
          ),
          el(
            TextControl,
            {
              label: __("Image URL", "alby"),
              onChange: (v) => { props.setAttributes({image: v}) },
              value: image,
            }
          ),
          el(
            TextControl,
            {
              label: __("Color", "alby"),
              onChange: (v) => { props.setAttributes({color: v}) },
              value: color,
            }
          )
        ]
      );
    },

    save: function ( props ) {
      return null;
    }
  }) ;
} )(
  window.wp.blocks,
  window.wp.editor,
  window.wp.i18n,
  window.wp.element,
  window.wp.components,
  window._,
  window.wp.blockEditor
);
