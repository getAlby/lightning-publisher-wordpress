<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * NWC Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_NWC_Client extends Abstract_BLN_Publisher_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        try {
            $this->client = new NWC\Client($this->options['nwc_connection_uri']);
            $this->client->init();
        } catch (\Exception $e) {
            echo "Failed to connect to Wallet: " . $e->getMessage();
        }
    }
}
