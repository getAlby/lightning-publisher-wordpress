<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * Alby Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_Alby_Client extends Abstract_BLN_Publisher_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        try {
            $this->client = new Alby\Client($this->options['alby_access_token']);
            $this->client->init();
        } catch (\Exception $e) {
            echo "Failed to connect to Wallet: " . $e->getMessage();
        }
    }
}
