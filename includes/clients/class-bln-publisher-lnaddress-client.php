<?php

/**
 * LNAddress Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_LNAddress_Client extends Abstract_BLN_Publisher_Client
{
    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new LightningAddress();
        if (!empty($this->options['lnaddress_address'])) {
            $this->client->setAddress($this->options['lnaddress_address']);
        } elseif (!empty($this->options['lnaddress_lnurl'])) {
            $this->client->setLnurl($this->options['lnaddress_lnurl']);
        }
    }
}
