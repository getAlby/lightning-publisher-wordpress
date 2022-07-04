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

## Paywall

### Paywall options

* Fixed amount
* Free once a certrain amount is collected
* Free after a certain time
* Free until a certain time

#### Paywall Hook to have custom logic when to enable/disable the paywall

To integrate with other plugins or to write custom conditions on when the paywall should be enabled a hook can be used. This means you can use a custom PHP function to decide if content should be behind the paywall or not.

This for example allows you to make the content available for all users/subscribers but enable the paywall for all other users.

##### Example

```php

// your function receives two arguments:
// 1. a boolean with the current check (true if the full content would be shown)
// 2. the ID of the post the user accesses
//
// return true if the full content should be shown or false to enable the paywall
function show_full_content_for_post($show_full_content, $post_id) {
  // Add your logic to check if the current user can see the post with ID $post_id

  return true; // return true to show the full content (disable the paywall)
}

// Check out the `add_filter` documentation for more information: https://developer.wordpress.org/reference/functions/add_filter/
add_filter('wp_lnp_has_paid_for_post', 'show_full_content_for_post', 10, 2);

```

Alternatively you can define a global function `wp_lnp_has_paid_for_post` which gets called. Return `true` to disable the paywall and show the full content.

```php

function wp_lnp_has_paid_for_post($show_full_content, $post_id) {
  return true; // show full content - disable the paywall
}

```

### Usage

Use the plugin as a shortcode/widget/block.

## Plugin Folder Structure

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
