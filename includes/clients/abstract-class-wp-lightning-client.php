<?php

/**
 * Abstract LND Lightning Client.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/client
 */
abstract class Abstract_WP_Lightning_Client implements WP_Lightning_Client_Interface {

	/**
     * Underlying client library.
     *
     * @since    1.0.0
     * @access   protected
     * @var      mixed    $client    Underlying client library.
     */
    protected $client;
	
    /**
     * Connection options.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $options    Connection options.
     */
    protected $options;

    /**
	 * Set the options.
	 *
	 * @since    1.0.0
	 */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * Add Invoice
     * 
     * @since    1.0.0
     */
    public function addInvoice($params)
    {
        $this->client->addInvoice($params);
    }
    
    /**
     * Get Invoice
     * 
     * @since    1.0.0
     */
    public function getInvoice($params)
    {
        $this->client->getInvoice($params);
    }
}
