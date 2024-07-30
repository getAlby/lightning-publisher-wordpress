=== Bitcoin Lightning Publisher for WordPress ===

Tags: bitcoin, lightning, payment, donation, paywall
Requires at least: 5.6.0
Tested up to: 6.5.5
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv3
Donate link: https://getalby.com
Contributors: getalby
Bitcoin Lightning Publisher is a Paywall, Donation and Value 4 Value plugin to accept instant Bitcoin payments directly to your favorit wallet.


== Description ==
Bitcoin Lightning Publisher is a Paywall, Donation and Value 4 Value plugin for WordPress to accept instant Bitcoin Lightning payments.
It allows you to monetize any digital content with instant microtransactions and receive payments from your visitors directly to your preferred wallet - no need for expensive service providers.

The plugin is the easiest and most flexible plugin to sell your digital content and to receive donations or Value 4 Value payments.
Using the Bitcoin Lightning Network you can create the best visitor experience with seamless one-click payments.


**Benefits for you, the publisher:**
- Monetize any digital content with instant microtransactions
- Create custom paywalls according to your needs
- Best and fastest checkout payment experience
- Superior paywall user experience
- Save payment fees by using the inexpensive Bitcoin Lightning Network - no need to payment service providers
- Receive payments directly in your preferred wallet (see "Lightning node connections")

**Benefits for your vistors/customers:**
- Seamless one-click payments and quick access to the content
- Global availability - let customers from around the world send you payments through the open Bitcoin payment network


**Use Case Examples:**
- Accept donations / Value 4 Value payments from your visitors
- Monetize any digital content on your website: Articles, pages, file, videos, music, podcasts
- Receive payments (boosts and boostagrams) from podcasting apps via the Podcasting 2.0 `podcast:value` RSS standard
- Integrate payments with your website functionality
- many more


== Features ==

=== Paywall to sell content ===

Sell any digital content (pay-per-post, pay-per-view, pay-per-download, etc.) with a highly configurable paywall

* WebLN enabled by default for easy on-click payments
* Add a paywall to posts and pages to easily charge for any published content
* Crowdfund option: make the content freely available after a certain amount is received
* Time-in option: keep the article freely available for a certain time and then enable the paywall after that
* Time-out option: make the article freely available after a certain time
* Configure the price in Satoshis, EUR, USD, or GBP (with real-time exchange rate)
* Configure the paywall with a shortcode ([lnpaywall])
* Or configure the paywall with a Gutenberg Block
* Integrate with other tools and plugins like membership tools to control if the paywall should be enabled (see Paywall Hook section)

=== Donation / Value 4 Value payments ===

The plugin comes with various options to receive donations and Value 4 Value payments.

* Gutenberg block for a donation widget
* Donation widget for themes
* Enable the Lightning meta tag to allow users to send payments (Value 4 Value)
* Enable the [`podcast:value` tag](https://github.com/Podcastindex-org/podcast-namespace/blob/main/value/value.md) in your RSS feed to receive payments for your podcast

=== Lightning Node connections ===

Connect to your existing Bitcoin Lightning node or simply create a new Alby account to instantly receive Lightning payments.

* [Alby](https://getalby.com/)
* LND
* LNDHub (e.g. BlueWallet)
* LNBits
* BTCPay Server
* Lightning Address

=== REST-API for full advanced custom usage ===

For more advanced, custom Lightning integrations you can use the REST API to create and verify invoices. The API also provides a LNURL-pay endpoint. See the REST-API section for details.

== Documentation ==

Refer to the [readme on GitHub](https://github.com/getAlby/lightning-publisher-wordpress#readme) for documentation and more details on how to use the plugin.

== Screenshots ==

1. Paywall Settings
2. Wallet Settings
3. Payment flow
4. Demo article with paywall
5. Paying for a demo article with Alby

== Changelog ==
[Release notes on GitHub](https://github.com/getAlby/lightning-publisher-wordpress/releases)

== Upgrade Notice ==

= 1.4.1 =
Fix QR code rendering

= 1.4.0 =
Added support for [NWC](https://nwc.dev) wallets which allows you to receive payments to any wallet that supports NWC.
In the future we might deprecate other options in favor of NWC.

= 1.3.0 =
NOTE: we do no longer embed the [twentyuno widget](https://webln.twentyuno.net/widget). To use that widget get the embed code from the [twentyuno website](https://webln.twentyuno.net/widget) and include it on your side.
Add opton to use post author's lightning address for payments. This makes it possible to pay the author directly for the published content.
Support for fully customizable paywall templates

= 1.2.3 =
Fix conflict with Elementor plugin (better scope bln-js-modules filter)

= 1.2.2 =
Update dependencies with some performance improvements

= 1.2.1 =
Fully delete options on plugin uninstall
Check Lightning Addresses on wallet configuration

= 1.2.0 =
Include shortcodes for Value 4 Value and donation buttons
Use Alby API for currency rates

= 1.1.2 =
Fix issue with empty [lnpaywall] short code not being recognised
Remove ECAdapter dependency which has some issues on some hosting setups

= 1.1.1 =
Maintenance update to fix version numbers

= 1.1.0 =
Add WebLN donation widget
Add support for LNURL-verify (a proposal by Alby) to verify payments when connected to a Lightning Address
Bugfix: use correct callback function name for the paywall check hook

= 1.0.1 =
Improve BTCPay Server connection

= 1.0.0 =
Initial release

== Additional Info ==
**Contributing**
This plugin is free and open source. We welcome and appreciate new contributions.
Visit the [code repository](https://github.com/getAlby/lightning-publisher-wordpress) and help us to improve the plugin.

**Donations**
Want to support the work on this plugin?
Support the team behind it and send some sats to this Bitcoin Lightning Address hello@getalby.com
