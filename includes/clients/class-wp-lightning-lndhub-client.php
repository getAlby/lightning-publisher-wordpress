<?php

/**
 * LNDHub Lightning Client.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/client
 */
class WP_Lightning_LNDHub_Client extends Abstract_WP_Lightning_Client
{

    public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new LNDHub\Client($this->options['lndhub_url'], $this->options['lndhub_login'], $this->options['lndhub_password']);
        $this->client->init();
    }
}
