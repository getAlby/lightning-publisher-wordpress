<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    
    <h1><?php echo $this->get_page_title(); ?></h1>
    
    <div class="tabbed-content">
        
        <?php $this->do_tabs_settings_section_nav(); ?>

        <div class="tab-content-wrapper">
            <form method="post" action="options.php">

                <?php 

                $this->do_tabs_settings_section();
                settings_fields($this->settings_path);
                submit_button();
                
                ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    
    let generating_lndhub_account = false;

    function lndhubCreateAccount(e) {
        const button = e.target;

        if (generating_lndhub_account)
            return;

        generating_lndhub_account = true;

        button.disabled = true;
        button.innerHTML = <?php _e('"Generating...";', 'lnp-alby'); ?>

        const data = new FormData();

        data.append('action', 'create_lnp_hub_account');

        fetch("<?php echo home_url( '/wp-json/lnp-alby/v1/account' ); ?>", {
                method: "POST",
                credentials: 'same-origin',
                body: data
            })
            .then((response) => response.json())
            .then((response) => {
                document.getElementById('lndhub_url').value = response.url;
                document.getElementById('lndhub_login').value = response.login;
                document.getElementById('lndhub_password').value = response.password;
                button.innerHTML = "Generated";
                document.getElementById("submit").click();
            })
            .catch((e) => console.error(e));
    }

    window.addEventListener("DOMContentLoaded", function() {

        /**
         * Create a new wallet
         */
        const newWallet = document.getElementById("create_lndhub_account");

        if ( newWallet )
        {
            newWallet.addEventListener("click", lndhubCreateAccount);    
        }
        
        
        
        const connectLNDButton = document.getElementById('load_from_lndconnect');

        if ( connectLNDButton )
        {
            connectLNDButton.addEventListener('click', function(e) {
                e.preventDefault();
                    
                var lndConnectUrl = prompt(<?php _e('"Please enter your lndconnect string (e.g. run: lndconnect --url --port=8080)"', 'lnp-alby'); ?>);

                if (!lndConnectUrl) {
                    return;
                }
                var url = new URL(lndConnectUrl);
                document.getElementById('lnp_lnd_address').value = 'https:' + url.pathname;
                document.getElementById('lnp_lnd_macaroon').value = url.searchParams.get('macaroon');
                document.getElementById('lnp_lnd_cert').value = url.searchParams.get('cert');
            });
        }
    });
</script>