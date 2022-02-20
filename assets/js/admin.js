window.addEventListener("DOMContentLoaded", function () {


    /**
     * Tabbed content settings page
     */
    const tabNav = [].slice.call( document.querySelectorAll('.lnp .nav-tab') );

    if ( tabNav.length )
    {
        const length = tabNav.length;

        for (let i = length - 1; i >= 0; i--) {
            
            const menuItem = tabNav[i];

            menuItem.addEventListener('click', e => {
                e.preventDefault();

                // Unactivate current menu item
                const menuActive = menuItem.closest('.nav-tab-wrapper').querySelector('.nav-tab-active');

                if ( menuActive )
                {
                    menuActive.classList.remove('nav-tab-active');
                }


                // Unactivate current tab
                const tabCurrent = menuItem.closest('.tabbed-content').querySelector('.tab-content-active');

                if ( tabCurrent )
                {
                    tabCurrent.classList.remove('tab-content-active');
                }

                // Activate clicked menu item
                menuItem.classList.add('nav-tab-active');

                // Activate clicked tab
                const tabActive = document.querySelector( menuItem.hash );

                if ( tabActive )
                {
                    tabActive.classList.add('tab-content-active');
                }
            });
        }
    }
});
