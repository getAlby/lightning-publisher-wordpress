<?php

// If this file is called directly, abort.
defined('WPINC') || die;

$table = new LNP_TransactionsTable($this->database_handler);
$table->prepare_items();

?>

<div class="wrap lnp">
    <h1><?php echo $this->get_page_title(); ?></h1>
    <div>
        <?php $table->display(); ?>
    </div>
</div>