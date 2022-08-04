<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo esc_html($this->get_page_title()); ?></h1>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Total payments</h2>
        <h3><?php echo esc_html($this->get_total_payments() ?? 0); ?></h3>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Total amount received</h2>
        <h3><?php echo esc_html($this->get_total_payments_sum() ?? 0); ?> sats</h3>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Top 10 posts</h2>
        <ol>
            <?php $top_posts = $this->get_top_posts(); foreach($top_posts as $top_post): ?>
                <li><?php echo esc_html($top_post->title); ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0;overflow:hidden">
        <h2 class="title">Connected wallet</h2>
        <h4>
            <?php
                echo esc_html($this->get_connected_wallet());
                if (!$this->check_connection_valid()) {
                    ?>
                    <br><a href="<?php echo admin_url('admin.php?page=lnp_settings_connections'); ?>">Setup your wallet</a>
                    <?php
                }
            ?>
        </h4>
    </div>
</div>
