<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo esc_html($this->get_page_title()); ?></h1>

    <h2>Getting Started</h2>
    <p>How to get started receiving lightning payments with your WordPress page</p>

    <ol>
        <li>
            <strong>Configure your preferred Lightning wallet</strong>:
            Go to <i>Wallet Settings</i> and configure your existing Wallet connection.<br>
            If you do not have one, or want <a href="https://getalby.com/">Alby</a> to manage it for you then simply create a new account or use your existing <a href="https://getalby.com/">Alby account</a>.
        </li>
        <li>
            <strong>Configure your paywall defaults</strong>:
            Go to <i>Paywall Settings</i> to configure your default settings. Those can always be overwritten on an individual post level but defaults make it easy.
        </li>
        <li>
            <strong>Enable Value 4 Value</strong>:
            Go to <i>General Settings</i> to enable the Value 4 Value options for your site.<br>
            This allows you to add the Lightning metatag and add the podcast:value tag to your RSS feed (mainly for the podcasters). Value 4 Value payments enable visitors to send micro-transactions as they use your site.
        </li>
        <li>
            <strong>Done</strong>:
            Add the paywall or donation blocks to your posts using the Gutenberg Block editor or use the <i>[lnpaywall]</i>, <i>[ln_v4v]</i> shortcodes.<br>
            In the Gutenberg block search for the Block "Lightning" (-"Lightning Paywall", "Twentyuno Lightning Payment Widget", "WebLN Donate Button"-)
        </li>
    </ol>

    <hr>

    <h2>Getting Help</h2>
    <p>If you need help please reach out to support@getalby.com</p>
    <p>For more details please visit the <a href="https://github.com/getAlby/lightning-publisher-wordpress">GitHub repository</a>.</p>

    <hr>

    <h2>Shortcodes</h2>

    <h3>Value 4 Value</h3>
    <p>
        Use the [ln_v4v] shortcode to add a Value 4 Value / donation button to your post. This is a simple button to allow your visitors to send you sats, it is a voluntary payment from your visitors. The Lightning Network and <a href="https://getalby.com">Alby</a> make this as easy as clicking a single button.
    </p>
    <p>
        Configure the button with the following options: <code>amount</code>, <code>currency</code>, <code>success_message</code>
    </p>
    <p>Examples:</p>
    <p><code>[ln_v4v]Support our work with sats[/ln_v4v]</code></p>
    <p><code>[ln_v4v amount="20" currency="eur"]Support us with 0.2EUR[/ln_v4v]</code></p>
    <p><code>[ln_v4v success_message="yay, thanks"]</code></p>
    <p><code>[ln_v4v amount="210"]</code></p>

    <h4>simple-boost</h4>
    <p>
        LN Publisher includes the <a href="https://getalby.github.io/simple-boost/">simple-boost</a> widget which can be added using a shortcode: [ln_simple_boost]
    </p>
    <p>Examples:</p>
    <p><code>[ln_simple_boost]Support our work with sats[/ln_simple_boost]</code></p>
    <p><code>[ln_simple_boost amount="20" currency="eur"]Support us with 0.2EUR[/ln_simple_boost]</code></p>
    <p><code>[ln_simple_boost amount="210"]</code></p>

    <h3>Paywall</h3>
    <p>
        Use the [lnpaywall] shortcode to add and configure a paywall to your post. Any content after the [lnpaywall] shortcode will be behind the paywall and only accessible to the user after a payment.
    </p>
    <p>
        Configure the paywall defaults in the <i>Paywall Settings</i> or overwrite the defaults with the following options: <code>amount</code>, <code>currency</code>, <code>button_text</code>, <code>total</code>, <code>timein</code>, <code>timeout</code>
    </p>
    <p>Examples:</p>
    <p><code>[lnpaywall amount="100"]</code> (100 sats)</p>
    <p><code>[lnpaywall amount="100" currency="eur"]</code> (100 EUR cents (1.00 EUR))</p>
    <p><code>[lnpaywall button_text="Support our work"]</code> (100 EUR cents (1.00 EUR))</p>

    <hr>

    <h2>Paywall Settings</h2>
    <p>
        The advanced Paywall Settings allow you to activate the paywall with certain options:
    </p>
    <ul style="list-style:disc; margin-left: 1rem;">
        <li>
            <strong>Timeout</strong>: Option to determine the number of hours you want to keep the article behind a paywall before making it free.
        </li>
        <li>
            <strong>Timein</strong>: Option to determine the number of hours you want to keep the article free before triggering the paywall.
        </li>
        <li>
            <strong>Total</strong>: Crowdfund the amount of funds you want to receive and disable the paywall after the desired funds are collected.
        </li>
        <li>
            <strong>Disable Paywall in RSS feeds</strong>: Show the full content in RSS feeds.
        </li>
    </ul>

    <h2>Wallet Settings</h2>
    <p>
      You have the option to receive your payments in the following wallets:
    </p>
    <ul style="list-style:disc; margin-left: 1rem;">
      <li><b>NWC Wallet Connection:</b> Nostr Wallet Connect (NWC) is an open protocol to connect lightning wallets to apps. You need to have "make_invoice", "lookup_invoice", "get_balance" and "get_info" permissions before creating the connection string. More information below:</li>
      <li><b>Alby Wallet:</b> Alby provides an easy to use all-in one service and manages a Lightning wallet for you. More information: https://getalby.com/</li>
      <li><b>Lightning Address:</b> A simple way for anyone to send you bitcoin instantly on the Lightning Network. It looks like a normal email address such as you@payaddress.com. But it only works if the website visitor uses a WebLN enabled wallet to pay. More information: <a href="https://lightningaddress.com/ target="_blank"">https://lightningaddress.com/</a></li>
      <li style="opacity: 0.6;"><b>LND (about to be deprecated):</b> The Lightning Network Daemon (LND) is one implementation of several of a Lightning Network node. If you want to connect a LND node make sure to have enough incoming liquidity to receive all payments.</li>
      <li style="opacity: 0.6;"><b>LNDHub (about to be deprecated):</b> A free and open source, multiple account plugin for Lightning built on top of the Lightning Network Deamon. It allows that a group of users run different accounts with only one node in a trust-minimized setup. More information: <a href="https://github.com/getAlby/lndhub.go" target="_blank">https://github.com/getAlby/lndhub.go</a></li>
      <li style="opacity: 0.6;"><b>LNBits (about to be deprecated):</b> A free and open-source Lightning accounts system with extensions. More information: <a href="https://lnbits.com/" target="_blank">https://lnbits.com/</a></li>
      <li style="opacity: 0.6;"><b>BTCPay (about to be deprecated):</b> A self-hosted, open-source cryptocurrency payment processor. More information: <a href="https://btcpayserver.org/" target="_blank">https://btcpayserver.org/</a></li>
    </ul>

    <hr/>

    <h2>What is NWC?</h2>
    <p>
      <a href="https://nwc.dev/">Nostr Wallet Connect (NWC)</a> is an open protocol to connect lightning wallets to apps. The lightning publisher uses this protocol to get payment details from your wallet. Payments will be sent directly to your wallet from your visitor.
    </p>
    <h3>What wallet is supported?</h3>
    <p>
      Any lightning wallet that supports NWC with the following permissions is supported:
    </p>
    <ul style="list-style:disc; margin-left: 1rem;">
      <li>get_info</li>
      <li>get_balance</li>
      <li>lookup_invoice</li>
      <li>make_invoice</li>
    </ul>
    <p>
      We recommend <a href="https://getalby.com/" target="_blank">getalby.com</a>
    </p>
    <h3>Where can I get a wallet?</h3>
    <p>
      Go to <a href="https://getalby.com/" target="_blank">getalby.com</a> for an easy to use wallet that can be used with WordPress
    </p>
    <h2>General Settings</h2>
    <ul style="list-style:disc; margin-left: 1rem;">
        <li>Value4Value Lightning meta tag: The meta tag gives websites a way to receive direct payments from their visitors. It allows websites to describe how and where they would like to receive payments. By enabling this feature you add the payment information directly into the code of the website. Wallets that can read this meta tag can send you payments without any additional payment widgets on your website. More information here: https://github.com/BitcoinAndLightningLayerSpecs/rfc/issues/1</li>
        <li>Enable Value4Value tag: Enabling this feature adds the podcast:value tag to your RSS feed. RSS feed reader apps (e.g. podcast apps) can read the payment information and allow the user to send you payments, if the app is bitcoin enabled. More information here: https://github.com/Podcastindex-org/podcast-namespace/blob/main/podcasting2.0.md</li>
    </ul>

</div>
