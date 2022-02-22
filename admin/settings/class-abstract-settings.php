<?php

// If this file is called directly, abort.
defined('WPINC') || die;

abstract class LNP_SettingsPage
{
    protected $settings_path;
    protected $template_html;
    protected $option_name;
    
    protected $tabs;
    protected $form_fields;

    public $options;
    public $plugin;

    protected $page;
    protected $page_title;
    protected $menu_title;

    public function __construct($plugin = false, $page = '')
    {
        $this->plugin      = $plugin;
        $this->page        = $page;
        $this->options     = array();
        $this->form_fields = array();
        
        $this->set_translations();
        $this->set_options();
        
        add_action( 'admin_menu', array($this, 'init_page') );
        add_action( 'admin_init', array($this, 'init_fields') );
    }


    /**
     * Add Menu Item page
     * @return [type] [description]
     */
    public function init_page()
    {
        add_submenu_page(
            $this->page,
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->settings_path,
            array($this, 'renderer')
        );
    }

    public function init_fields()
    {
        // Load form fields
        $this->set_form_fields();

        // Settings page without tabs
        register_setting(
            $this->settings_path,
            $this->option_name, 
            array($this, 'sanitize')
        );

        // Register Tabbed sections
        // This will register tabs as sections 
        if ( ! empty($this->tabs) )
        {
            // Create sections on a page
            foreach( $this->tabs as $id => $args )
            {
                add_settings_section(
                    "{$this->option_name}_section_{$id}",
                    $args['title'],
                    null,
                    "{$this->option_name}",
                );
            }
        }

        // Register fields
        foreach( $this->form_fields as $args )
        {
            add_settings_field(
                $args['field']['name'],
                $args['field']['label'],
                array($this, 'get_input'),
                $this->option_name,
                "{$this->option_name}_section_"  . $args['tab'],
                array(
                    'args' => $args
                ),
            );
        }
    }


    /**
     * Load options page HTML template 
     */
    public function renderer() {

        if ( empty($this->template_html) )
            return;

        settings_errors();

        // Include HTML file
        include $this->get_template_path($this->template_html);
    }


    public function sanitize($inputs)
    {
        $new_input = array();

        if ( is_array( $inputs) )
        {
            foreach ($inputs as $key => $input)
            {
                if (isset($input)) {
                    $new_input[$key] = sanitize_text_field($input);
                }
            }
        }

        return $inputs;
    }

    public function get_field_name($name)
    {
        return "$this->option_name[$name]";
    }

    
    public function get_field_value( $field )
    {
        $name = is_array($field)
            ? $field['field']['name']
            : $field;

        if ( ! isset($this->options[$name]) )
        {
            return '';
        }

        return esc_attr($this->options[$name]);
    }


    public function get_active_tab_id() {

        return isset($_GET['tab'])
            ? sanitize_text_field($_GET['tab'])
            : key($this->tabs);
    }


    /**
     * Display all sections from a page as tabs
     */
    public function do_tabs_settings_section_nav() {

        $active   = $this->get_active_tab_id();
        $output   = array();
        $output[] = '<h2 class="nav-tab-wrapper">';
        
        foreach ( $this->tabs as $id => $args)
        {
            $output[] = sprintf(
                '<a href="#%s" class="%s">%s</a>',
                $id,
                ($id == $active) ? 'nav-tab nav-tab-active' : 'nav-tab',
                $args['title']
            );
        }

        $output[] = '</h2>';

        echo join('', $output);
    }


    /**
     * Display all sections from a page as tabs
     */
    public function do_tabs_settings_section() {

        $active = $this->get_active_tab_id();

        foreach ( $this->tabs as $id => $args) 
        {
            // Open Tab
            printf(
                '<div id="%s" class="tab-content%s">',
                $id,
                ($id == $active)
                    ? ' tab-content-active'
                    : '',
            );

            // Open Table
            echo '<table class="form-table" role="presentation">';

            // Render fields
            $this->do_section_settings_fields( $id );

            // Cloase table and tab
            echo '</table></div>';
        }
    }


    /**
     * Display settings field from a page section in WP style
     * 
     * @param  [type] $tab_id [description]
     * @return [type]         [description]
     */
    public function do_section_settings_fields( $tab_id = '' ) {

        global $wp_settings_fields;
        
        $page    = $this->option_name;
        $section = "{$this->option_name}_section_{$tab_id}";

        if (!isset($wp_settings_fields[$page][$section])) {
            return;
        }

        echo '<table class="form-table" role="presentation">';

        foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field )
        {
            $class = '';
     
            if ( ! empty( $field['args']['class'] ) )
            {
                $class = sprintf(
                    ' class="%s"',
                    esc_attr( $field['args']['class'] )
                );
            }
     
            echo "<tr{$class}>";
     
            if ( ! empty( $field['args']['label_for'] ) )
            {
                printf(
                    '<th scope="row"><label for="%s">%s</label></th>',
                    esc_attr( $field['args']['label_for'] ),
                    $field['title']
                );
            }
            else {

                printf(
                    '<th scope="row">%s</th>',
                    $field['title']
                );
            }
     
            echo '<td>';
            call_user_func( $field['callback'], $field['args'] );
            echo '</td></tr>';
        }

        echo '</table>';
    }


    protected function get_template_path( $filename = false ) {

        return sprintf(
            '%s/admin/templates/%s',
            WP_LN_ROOT_PATH,
            $filename
        );
    }


    protected function get_page_title()
    {
        return $this->page_title;
    }

    protected function get_menu_title()
    {
        return $this->menu_title;
    }


    protected function set_translations() {}
    protected function set_form_fields() {}


    /**
     * Load all options for current settings page from DB
     */
    protected function set_options() {

        $option_name = $this->option_name;

        // Load options for current tab only
        if ( $this->tabs )
        {
            $tab = isset($_GET['tab'])
                ? $_GET['tab']
                : key($this->tabs);

            $option_name = "{$this->option_name}_{$tab}";
        }

        $this->options = get_option($option_name);
    }



    protected function add_admin_notice( $message, $type = 'info' ) {

        add_action( 'admin_notices', function ( $message, $type ) {
            
            $class = 'notice notice-' . $type;
 
            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                esc_attr( $class ),
                esc_html( $message )
            );
        }, 10, 2);
    }


    /**
     * Generate markup output for <input> element
     * 
     * @param  string $option_name [description]
     * @param  array   $args        [description]
     * 
     * @return misc
     */
    public function get_input( $field_args = array() )
    {
        // error_log(print_r($option_name, true));
        // error_log(print_r($args, true));

        $args = $field_args['args'];
        $name = $args['field']['name'];

        /**
         * Deafult values that will be merged with $args
         */
        $defaults = array(
            'type'         => 'text',
            'class'        => 'regular-text',
            'name'         => '',
            'value'        => $this->get_field_value($args),
            'placeholder'  => '',
            'autocomplete' => 'off',
            'label'        => '',
            'description'  => ''
        );

        
        // Merge args with defaults and filter empty values
        $parsed_args = wp_parse_args($args['field'], $defaults);
        $parsed_args = array_filter($parsed_args);

        
        /**
         * Checkbox specifc
         */
        if ( 'checkbox' == $parsed_args['type'] )
        {
            // Don't add autocomplete arg to checkbox
            unset($parsed_args['autocomplete']);
        }
        
        // HTML output
        $output = array('<input');
        
        /**
         * Will append args as input element attributes
         * Eg: <input type="text" etc...
         */
        foreach ( $parsed_args as $arg => $val )
        {
            // Don't create markup for these args
            if ( in_array($arg, array('label', 'description')) )
                continue;

            // Append name attribute into array
            if ( 'name' == $arg )
            {
                $output[] = sprintf(
                    'name="%s[%s]"',
                    $this->option_name,
                    $val
                );

                continue;
            }

            // Append everything else
            $output[] = sprintf(
                '%s="%s"',
                $arg,
                $val
            );
        }

        // Close input
        $output[] = '/>';

        /**
         * For checkbox type we want to display label inline with input element
         */
        if ( 'checkbox' == $parsed_args['type'] )
        {
            // Append label
            $output[] = sprintf(
                '<label>%s</label>',
                esc_attr($parsed_args['label'])
            );
        }

        /**
         * Additional description if provided
         * Extra "help" instructions block below the field
         */
        if ( ! empty($parsed_args['description']) )
        {
            $output[] = sprintf(
                '<p class="description">%s</p>',
                $parsed_args['description']
            );
        }

        // Generate and return input field html output
        echo join(' ', $output);
    }
}
