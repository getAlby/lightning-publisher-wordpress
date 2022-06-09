<?php

class LNP_DatabaseHandler
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . "lightning_publisher_payments";
    }

    public function init()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        payment_hash varchar(256) NOT NULL,
        payment_request text NOT NULL,
        amount_in_satoshi int(10) DEFAULT 0 NOT NULL,
        exchange_rate int(10) DEFAULT 0 NOT NULL,
        exchange_currency varchar(10) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        settled_at datetime DEFAULT NULL,
        state tinytext NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY payment_hash (payment_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function store_invoice($post_id, $payment_hash, $payment_request, $amount, $currency, $exchange_rate)
    {
        global $wpdb;
        // // TODO: implement get exchange rate functionality
        try {
            $wpdb->insert(
                $this->table_name,
                array(
                    'post_id'           => $post_id,
                    'payment_hash'      => $payment_hash,
                    'payment_request'   => $payment_request,
                    'amount_in_satoshi' => $amount,
                    'exchange_rate'     => $exchange_rate,
                    'exchange_currency' => $currency,
                    'created_at'        => current_time('mysql'),
                    'state'             => 'unpaid',
                )
            );
        } catch (Exception $e) {
        }
    }


    public function update_invoice_state($hash, $state)
    {
        global $wpdb;
        try {
            $wpdb->update(
                $this->table_name,
                array(
                    'state' => $state,
                    'settled_at' => current_time('mysql'),
                ),
                array(
                    'payment_hash' => $hash,
                )
            );
        } catch (Exception $e) {
        }
    }

    public function get_payments($page, $items_per_page)
    {
        global $wpdb;

        $offset = (intval($page) - 1) * intval($items_per_page);
        $query  = "SELECT * FROM $this->table_name ORDER BY created_at DESC LIMIT ${items_per_page} OFFSET ${offset}";
        
        return $wpdb->get_results($query);
    }

    public function total_payment_count()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
    }
}
