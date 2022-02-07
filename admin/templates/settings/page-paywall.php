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
            <?php foreach ( $this->sections as $id => $label)
            {
                printf(
                    '<a href="#%s" class="%s">%s</a>',
                    $id,
                    ($id == $active) ? 'nav-tab nav-tab-active' : 'nav-tab',
                    $label
                );
            } ?>
        </h2>

        <div class="tab-content-wrapper">
            <form method="post" action="options.php">
                <?php foreach ( $this->sections as $id => $label) : 

                    $cssClass = ($id == $active)
                        ? 'tab-content tab-content-active'
                        : 'tab-content';
                    ?>

                    <div
                        id="<?php echo $id; ?>"
                        class="<?php echo $cssClass; ?>">
                        <?php

                        settings_fields('wpln_paywall_' . $id);
                        do_settings_sections('wpln_paywall_' . $id);

                        ?>
                    </div>
                <?php endforeach; ?>

                <div class="button-row">
                    <?php submit_button(); ?>
                </div>
            </form>
        </div>
    </div>
</div>