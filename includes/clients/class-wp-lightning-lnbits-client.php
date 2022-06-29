<?php

/**
 * LNBits Lightning Client.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/client
 */
class WP_Lightning_LNBits_Client extends Abstract_WP_Lightning_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new LNbits\Client($this->options['lnbits_apikey'], $this->options['lnbits_host']);
    }
}
