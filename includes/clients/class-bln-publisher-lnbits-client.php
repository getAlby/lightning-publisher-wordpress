<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * LNBits Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_LNBits_Client extends Abstract_BLN_Publisher_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        try {
            $this->client = new LNbits\Client($this->options['lnbits_apikey'], $this->options['lnbits_host']);
        } catch (\Exception $e) {
            echo "Failed to connect to Wallet: " . $e->getMessage();
        }
    }
}
