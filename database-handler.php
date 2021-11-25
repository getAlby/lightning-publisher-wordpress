<?php

class DatabaseHandler
{
    private $table_name;

    public function init()
    {
        global $wpdb;

        $this->table_name = $wpdb->prefix . "lightning_publisher_payments";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        payment_hash text NOT NULL,
        payment_request text NOT NULL,
        amount_in_satoshi int(10) DEFAULT 0.0 NOT NULL,
        exchange_rate int(10) DEFAULT 0.0 NOT NULL,
        exchange_currency varchar(10) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        settled_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        state tinytext NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function find()
    {
        $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->links WHERE link_id = %d", $link_id));
    }

    public function store_invoice($post_id, $payment_hash, $payment_request, $amount, $currency, $exchange_rate)
    {
        global $wpdb;
        // // TODO: implement get exchange rate functionality
        try {
            $wpdb->insert(
                $this->table_name,
                array(
                    'post_id' => $post_id,
                    'payment_hash' => $payment_hash,
                    'payment_request' => $payment_request,
                    'amount_in_satoshi' => $amount,
                    'exchange_rate' => $exchange_rate,
                    'exchange_currency' => $currency,
                    'created_at' => current_time('mysql'),
                    'settled_at' => current_time('mysql'),
                    'state' => 'unpaid',
                )
            );
        } catch (Exception $e) {
        }
    }


    public function update_invoice_status($hash)
    {
        global $wpdb;
        // // TODO: implement get exchange rate functionality
        try {
            $wpdb->update(
                $this->table_name,
                array(
                    'state' => 'settled',
                ),
                array(
                    'payment_hash' => $hash,
                )
            );
        } catch (Exception $e) {
        }
    }
}
