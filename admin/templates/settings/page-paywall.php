<?php

// If this file is called directly, abort.
defined('WPINC') || die;

// Active tab
$active = isset($_GET['tab'])
    ? $_GET['tab']
    : 'pricing';

?>

<div class="wrap wpln">
    <div class="tabbed-content">
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $this->tabs as $id => $label)
            {
                printf(
                    '<a href="%s?page=%s&tab=%s" class="%s">%s</a>',
                    admin_url('admin.php'),
                    sanitize_text_field($_GET['page']),
                    $id,
                    ($id == $active) ? 'nav-tab nav-tab-active' : 'nav-tab',
                    $label
                );
            } ?>
        </h2>

        <div class="tab-content-wrapper">
            <form method="post" action="options.php">
                <?php

                settings_fields("wpln_page_{$this->option_name}_{$active}");
                do_settings_sections("wpln_page_{$this->option_name}_{$active}");

                ?>

                <div class="button-row">
                    <?php submit_button(); ?>
                </div>
            </form>
        </div>
    </div>
</div>