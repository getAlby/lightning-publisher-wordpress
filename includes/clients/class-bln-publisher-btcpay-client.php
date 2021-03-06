<?php

/**
 * BTCPay Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_BTCPay_Client extends Abstract_BLN_Publisher_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new BTCPay\Client($this->options['btcpay_host'], $this->options['btcpay_apikey'], $this->options['btcpay_store_id']);
        $this->client->init();
    }
}
