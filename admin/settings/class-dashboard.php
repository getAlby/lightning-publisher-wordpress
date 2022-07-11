<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_Dashboard extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings';
    protected $option_name   = 'lnp_dashboard';
    protected $template_html = 'settings/page-dashboard.php';

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __('Dashboard', 'lnp-alby');
        $this->menu_title = __('Dashboard', 'lnp-alby');
    }

    /**
     * Get the total payments made
     */
    public function get_total_payments()
    {
        $database_handler = $this->plugin->getDatabaseHandler();
        return $database_handler->total_payment_count('settled');
    }

    /**
     * Get the total payments sum
     */
    public function get_total_payments_sum()
    {
        $database_handler = $this->plugin->getDatabaseHandler();
        return $database_handler->total_payment_sum();
    }

    /**
     * Get the top posts
     */
    public function get_top_posts()
    {
        $database_handler = $this->plugin->getDatabaseHandler();
        $top_posts = $database_handler->top_posts();
        return $top_posts;
    }

    /**
     * Get the connected wallet
     */
    public function get_connected_wallet()
    {
        if ($this->check_connection_valid()) {
            $node_info = $this->plugin->getLightningClient()->getInfo();
            $message = sprintf(
                '%s %s - %s',
                __('Connected to:', 'lnp-alby'),
                $node_info['alias'],
                $node_info['identity_pubkey']
            );
        }
        else {
            $message = __('Wallet not connected', 'lnp-alby');
        }
        return $message;
    }

    /**
     * Check if connection is valid
     */
    public function check_connection_valid() {
        return $this->plugin->getLightningClient()
        && $this->plugin->getLightningClient()->isConnectionValid();
    }
}
