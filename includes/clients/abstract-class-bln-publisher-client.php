<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * Abstract LND Lightning Client.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes/client
 */
abstract class Abstract_BLN_Publisher_Client implements BLN_Publisher_Client_Interface
{

    /**
     * Underlying client library.
     *
     * @since  1.0.0
     * @access protected
     * @var    mixed    $client    Underlying client library.
     */
    protected $client;

    /**
     * Connection options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $options    Connection options.
     */
    protected $options;

    /**
     * Set the options.
     *
     * @since 1.0.0
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * Add Invoice
     *
     * @since 1.0.0
     */
    public function addInvoice($params)
    {
        return $this->client->addInvoice($params);
    }

    /**
     * Get Invoice
     *
     * @since 1.0.0
     */
    public function getInvoice($params)
    {
        return $this->client->getInvoice($params);
    }

    /**
     * Check if Invoice is paid
     *
     * @since 1.0.0
     */
    public function isInvoicePaid()
    {
        return $this->client->isInvoicePaid();
    }

    /**
     * Check for valid connection
     *
     * @since 1.0.0
     */
    public function isConnectionValid()
    {
        return $this->client->isConnectionValid();
    }

    /**
     * Get information about the client
     *
     * @since 1.0.0
     */
    public function getInfo()
    {
        return $this->client->getInfo();
    }

    /**
     * Get address
     *
     * @since 1.0.0
     */
    public function getAddress()
    {
        return $this->client->getAddress();
    }

    /**
     * Set address
     *
     * @since 1.0.0
     */
    public function setAddress()
    {
        return $this->client->setAddress();
    }

    /**
     * Request
     *
     * @since 1.0.0
     */
    public function request()
    {
        return $this->client->request();
    }

    /**
     * Client
     *
     * @since 1.0.0
     */
    public function client()
    {
        return $this->client->client();
    }
}
