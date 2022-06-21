# WP-lightning

WP Lightning is a Bitcoin Lightning Paywall and Donation plugin for Wordpress.

## Installation

Clone the repository and install the dependency

```
git clone https://github.com/bumi/wp-lightning
cd wp-lightning
composer install
```

Activate the plugin

## Paywall options

* Fixed amount
* Flat fee for all articles
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

## License

MIT

Initial code based on: https://github.com/ElementsProject/wordpress-lightning-publisher

