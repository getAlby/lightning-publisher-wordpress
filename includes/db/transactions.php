<?php

// If this file is called directly, abort.
defined('WPINC') || die;

if (!class_exists('WP_List_Table')) {
    include_once ABSPATH . 'wp-admin/includes/screen.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LNP_TransactionsTable extends WP_List_Table
{
    protected $database_handler;

    public function __construct($database_handler)
    {
        $this->database_handler = $database_handler;

        parent::__construct(
            array(
            'singular'  => 'wp_list_event',
            'plural'    => 'wp_list_events',
            'ajax'      => false
            )
        );
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        // Filter using state args
        $state = (isset($_REQUEST['state']) ? $_REQUEST['state'] : 'settled');
        if (!in_array($state, ['all', 'settled', 'unpaid'])) {
            $state = 'settled';
        }


        $perPage = 25;
        $currentPage = $this->get_pagenum();
        $totalItems = $this->database_handler->total_payment_count($state);

        $this->views();

        $data = $this->table_data($currentPage, $perPage, $state);
        usort($data, array(&$this, 'sort_data'));

        $this->set_pagination_args(
            array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
            )
        );
        // $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'post'          => 'Post',
            'payment_hash'       => 'Payment Hash',
            'payment_request' => 'Payment Request',
            'amount'        => 'Amount (in sats)',
            // 'exchange_rate'    => 'Exchange Rate',
            // 'exchange_currency' => 'Exchange Currency',
            'state' => 'State',
            'created_at' => 'Created At',
            'settled_at' => 'Settled At'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('post' => array('post', false), 'state' => array('state', false), 'created_at' => array('created_at', false), 'settled_at' => array('settled_at', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($page, $perPage, $state = 'all')
    {
        $data = array();
        $payments = $this->database_handler->get_payments($page, $perPage, $state);
        foreach ($payments as $payment) {
            if ($state == $payment->state || $state == 'all') {
                $post = empty($payment->post_id) ? null : get_post($payment->post_id);
                $link = empty($payment->post_id) ? null : get_permalink($post);
                $payment_hash = substr($payment->payment_hash, 0, 20) . '...';
                $payment_request = substr($payment->payment_request, 0, 20) . '...';
                // $link = get_permalink($post);
                $data[] = array(
                    'post'          => (empty($post) ? "" : "<a href='$link'>$post->post_title</a>"),
                    'payment_hash'       => "<span title='$payment->payment_hash'>$payment_hash</span>",
                    'payment_request' => "<span title='$payment->payment_request'>$payment_request</span>",
                    'amount'        => $payment->amount_in_satoshi,
                    // 'exchange_rate'    => $payment->exchange_rate,
                    // 'exchange_currency' => $payment->exchange_currency,
                    'state' => $payment->state,
                    'created_at' => $payment->created_at,
                    'settled_at' => $payment->settled_at
                );
            }
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param Array  $item        Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
        case 'post':
        case 'payment_hash':
        case 'payment_request':
        case 'amount':
        case 'exchange_rate':
        case 'exchange_currency':
        case 'state':
        case 'created_at':
        case 'settled_at':
            return $item[$column_name];

        default:
            return print_r($item, true);
        }
    }

    protected function handle_row_actions($item, $column_name, $primary)
    {
        return $column_name === $primary
            ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __('Show more details') . '</span></button>'
            : '';
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'created_at';
        $order   = 'desc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }

    /**
     * Get the filters for settled and unsettled transactions
     */
    protected function get_views(){
        $views = array();
        $current = ( !empty($_REQUEST['state']) ? $_REQUEST['state'] : 'settled');

        //All link
        $class = ($current == 'all' ? ' class="current"' :'');
        $all_url =  add_query_arg('state','all');
        $views['all'] = "<a href='{$all_url }' {$class} >All</a>";

        //Settled link
        $settled_url = add_query_arg('state','settled');
        $class = ($current == 'settled' ? ' class="current"' :'');
        $views['settled'] = "<a href='{$settled_url}' {$class} >Settled</a>";

        //unpaid link
        $unpaid_url = add_query_arg('state','unpaid');
        $class = ($current == 'unpaid' ? ' class="current"' :'');
        $views['unpaid'] = "<a href='{$unpaid_url}' {$class} >Unpaid</a>";

        return $views;
    }
}
