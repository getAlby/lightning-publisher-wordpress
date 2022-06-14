<?php

/**
 * Lightning Client Interface.
 * 
 * Defines the interfaces that are shared by the different client types
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes/clients
 */
interface WP_Lightning_Client_Interface {
    public function addInvoice($params);
    public function getInvoice($params);
}