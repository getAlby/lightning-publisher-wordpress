<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo $this->get_page_title(); ?></h1>
    <div class="card">
        <h2 class="title">Total Payments</h2>
        <h3><?php echo $this->get_total_payments(); ?></h3>
    </div>
    <div class="card">
        <h2 class="title">Total Payments Sum</h2>
        <h3><?php echo $this->get_total_payments_sum(); ?> SAT</h3>
    </div>
    <div class="card">
        <h2 class="title">Top 10 Posts</h2>
        <ol>
            <?php $top_posts = $this->get_top_posts(); foreach($top_posts as $top_post): ?>
                <li><?php echo $top_post->title; ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    <div class="card">
        <h2 class="title">Connected Wallet</h2>
        <h4><?php echo $this->get_connected_wallet(); ?></h4>
    </div>
</div>