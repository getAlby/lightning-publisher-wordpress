<?php

// If this file is called directly, abort.
defined('WPINC') || die;

// Active tab
$active = isset($_GET['tab'])
    ? $_GET['tab']
    : 'pricing';


global $wp_settings_fields; ?>

<div class="wrap lnp">
    
    <?php settings_errors(); ?>

    <div class="tabbed-content">
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $this->tabs as $id => $args)
            {
                printf(
                    '<a href="#%s" class="%s">%s</a>',
                    $id,
                    ($id == $active) ? 'nav-tab nav-tab-active' : 'nav-tab',
                    $args['title']
                );
            } ?>
        </h2>

        <div class="tab-content-wrapper">
            <form method="post" action="options.php">

                <?php 
                
                $this->do_tabs_settings_section($active);

                settings_fields("{$this->option_name}");
                submit_button();
                
                ?>
            </form>
        </div>
    </div>
</div>