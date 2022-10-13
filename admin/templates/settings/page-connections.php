<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">

    <h1><?php echo esc_html($this->get_page_title()); ?></h1>

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

    let generating_alby_account = false;

    function createAlbyAccount(e) {
        const button = e.target;

        if (generating_alby_account)
            return;

        generating_alby_account = true;

        button.disabled = true;
        button.innerHTML = <?php _e('"Generating...";', 'lnp-alby'); ?>

        const email = document.getElementById("alby_email").value;
        const password = document.getElementById("alby_password").value;

        fetch("<?php echo get_rest_url(null, '/lnp-alby/v1/account'); ?>", {
                method: "POST",
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce' : '<?php echo wp_create_nonce('wp_rest')?>'
                },
                body: JSON.stringify({email: email, password: password})
            })
            .then((response) => response.json())
            .then((response) => {
                if (response.lndhub && response.lndhub.login && response.lndhub.password && response.lndhub.url) {
                    document.getElementById('lndhub_url').value = response.lndhub.url;
                    document.getElementById('lndhub_login').value = response.lndhub.login;
                    document.getElementById('lndhub_password').value = response.lndhub.password;
                    button.innerHTML = "Generated";
                    document.getElementById("submit").click();
                } else {
                    alert("Error: " + JSON.stringify(response));
                }
            })
            .catch((e) => console.error(e));
    }

    window.addEventListener("DOMContentLoaded", function() {

        /**
         * Create a new wallet
         */
        const newWallet = document.getElementById("create_alby_account");
        if ( newWallet )
        {
            newWallet.addEventListener("click", createAlbyAccount);
        }

    });
</script>
