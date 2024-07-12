<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * LND Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
class BLN_Publisher_LND_Client extends Abstract_BLN_Publisher_Client
{

  public function __construct($options)
    {
        parent::__construct($options);
        $this->client = new LND\Client();
        $this->client->setAddress(trim($this->options['lnd_address']));
        $this->client->setMacarronHex(trim($this->options['lnd_macaroon']));
        if (!empty($this->options['lnd_cert'])) {
            $certPath = tempnam(sys_get_temp_dir(), "WPLNP");
            file_put_contents($certPath, hex2bin($this->options['lnd_cert']));
            $this->client->setTlsCertificatePath($certPath);
        }
    }
}
