<?php

/**
 * BTCPay Lightning Client.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/client
 */
class WP_Lightning_BTCPay_Client extends Abstract_WP_Lightning_Client{

    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new BTCPay\Client($this->options['btcpay_host'], $this->options['btcpay_apikey'], $this->options['btcpay_store_id']);
        $this->client->init();
    }
}
