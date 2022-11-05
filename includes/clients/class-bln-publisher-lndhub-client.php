<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * LNDHub Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_LNDHub_Client extends Abstract_BLN_Publisher_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        try {
            $this->client = new LNDHub\Client($this->options['lndhub_url'], $this->options['lndhub_login'], $this->options['lndhub_password']);
            $this->client->init();
        } catch (\Exception $e) {
            echo "Failed to connect to Wallet: " . $e->getMessage();
        }
    }
}
