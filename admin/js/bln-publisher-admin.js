(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    $(function () {
        /**
     * Tabbed content settings page
     */
        const tabNav = [].slice.call(document.querySelectorAll('.lnp .nav-tab'));

        if (tabNav.length) {
            const length = tabNav.length;

            for (let i = length - 1; i >= 0; i--) {

                const menuItem = tabNav[i];

                menuItem.addEventListener('click', e => {
                    e.preventDefault();

                    // Unactivate current menu item
                    const menuActive = menuItem.closest('.nav-tab-wrapper').querySelector('.nav-tab-active');

                    if (menuActive) {
                        menuActive.classList.remove('nav-tab-active');
                    }


                    // Unactivate current tab
                    const tabCurrent = menuItem.closest('.tabbed-content').querySelector('.tab-content-active');

                    if (tabCurrent) {
                        tabCurrent.classList.remove('tab-content-active');
                    }

                    // Activate clicked menu item
                    menuItem.classList.add('nav-tab-active');

                    // Activate clicked tab
                    const tabActive = document.querySelector(menuItem.hash);

                    if (tabActive) {
                        tabActive.classList.add('tab-content-active');
                    }
                });
            }
        }
    });

})(jQuery);
