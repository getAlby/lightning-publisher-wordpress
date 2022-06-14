<?php

/**
 * LNAddress Lightning Client.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/client
 */
class WP_Lightning_LNAddress_Client extends Abstract_WP_Lightning_Client{

    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new LightningAddress();
        if (!empty($this->options['lnaddress_address'])) {
            $this->client->setAddress($this->options['lnaddress_address']);
        } elseif (!empty($this->options['lnaddress_lnurl']))  {
            $this->client->setLnurl($this->options['lnaddress_lnurl']);
        }
    }
}
