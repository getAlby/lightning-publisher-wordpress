<?php

// If this file is called directly, abort.
defined('WPINC') || die;
$table = new LNP_TransactionsTable($this->database_handler);

?>

<div class="wrap lnp">
    <h1><?php echo esc_html($this->get_page_title()); ?></h1>
    <div>
        <?php $table->prepare_items(); ?>
        <?php $table->display(); ?>
    </div>
</div>
