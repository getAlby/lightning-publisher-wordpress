<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo $this->get_page_title(); ?></h1>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Total payments</h2>
        <h3><?php echo $this->get_total_payments() ?? 0; ?></h3>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Total amount received</h2>
        <h3><?php echo $this->get_total_payments_sum() ?? 0; ?> sats</h3>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Top 10 posts</h2>
        <ol>
            <?php $top_posts = $this->get_top_posts(); foreach($top_posts as $top_post): ?>
                <li><?php echo $top_post->title; ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    <div class="card" style="float:left;width:49%;margin:10px 10px 10px 0">
        <h2 class="title">Connected Wallet</h2>
        <h4>
            <?php
                echo $this->get_connected_wallet();
                if (!$this->check_connection_valid()) {
                    echo "<br><a href='". admin_url('admin.php?page=lnp_settings_connections') ."'>Link your wallet</a>";
                }
            ?>
        </h4>
    </div>
</div>
