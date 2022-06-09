/**
 * Script handles gutenberg block: alby/donate
 */
window.addEventListener("DOMContentLoaded", function () {
    ( function ( blocks, blockEditor, element ) {

        var el = element.createElement;
        // var RichText = blockEditor.RichText;
        //var AlignmentToolbar = blockEditor.AlignmentToolbar;
        var BlockControls = blockEditor.BlockControls;
        var useBlockProps = blockEditor.useBlockProps;
     
        blocks.registerBlockType( 'alby/donate', {
            edit: function ()
            {
                return el(
                    'div',
                    useBlockProps(),
                    el(
                       BlockControls,
                       { key: 'controls' },
                    ),
                    el('hr', {class: "lnp-alby-donation-widget" })
                );
                
            },
            save: function ()
            {
                return [
                    el(
                       'div', {},
                       el('hr', {class: "lnp-alby-donation-widget" })
                    ),
                ];
            },
        } );
    })( window.wp.blocks, window.wp.blockEditor, window.wp.element );
});