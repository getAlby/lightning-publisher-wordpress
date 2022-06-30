# Lightning Publisher for WordPress

Lightning Publisher is a Bitcoin Lightning Paywall and Donation plugin for Wordpress. It allows you to receive Bitcoin Lightning payments with your WordPress website.

## Installation

Clone the repository and install the dependency

```bash
git clone https://github.com/getAlby/lightning-publisher-wordpress.git
cd lightning-publisher-wordpress
composer install # (maybe you need to add `--ignore-platform-reqs`)
```
To build a .zip file of the WordPress plugin run:
```bash
./build.sh # this builds a `wordpress-lightning-publisher.zip`
```

Then upload and activate the plugin.

## Paywall options

* Fixed amount
* Free once a certrain amount is collected
* Free after a certain time
* Free until a certain time

## Usage

Use the plugin as a shortcode/widget/block.

## Folder Structure

Folder structure is based on https://github.com/DevinVinson/WordPress-Plugin-Boilerplate

- `wp-lightning.php` is the entrypoint of the plugin
- `includes` is where functionality shared between the admin area and the public-facing parts of the site reside
- `admin` is for all admin-specific functionality
- `public` is for all public-facing functionality
- `includes/class-wp-lightning.php` is the main plugin class which handles including all the related classes.
- `includes/class-wp-lightning-loader.php` is responsible for registering the action and filter hooks, and shortcodes. 

## REST API

The plugin also provides a set of REST API Endpoints for handling payments and donations.

#### Intiate Payment for Paywall

- URL: `/lnp-alby/v1/paywall/pay`
- Method: `POST`
- Auth Required: No
- Data example

```
{
    post_id: "xxx"
}
```

#### Verify Payment for Paywall

- URL: `/lnp-alby/v1/paywall/verify`
- Method: `POST`
- Auth Required: No
- Data example

```
{
    post_id: "xxx",
    token: "xxx",
    preimage: "xxx"
}
```

#### Initiate Donation

- URL: `/lnp-alby/v1/donate`
- Method: `POST`
- Auth Required: No
- Data example

```
{
    post_id: "xxx",
    amount: "xxx"
}
```

#### Verify Donation

- URL: `/lnp-alby/v1/verify`
- Method: `POST`
- Auth Required: No
- Data example

```
{
    amount: "xxx",
    token: "xxx",
    preimage: "xxx"
}
```

## License

MIT
