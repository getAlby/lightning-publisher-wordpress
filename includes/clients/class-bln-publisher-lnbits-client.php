<?php

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
        $this->client = new LNbits\Client($this->options['lnbits_apikey'], $this->options['lnbits_host']);
    }
}
