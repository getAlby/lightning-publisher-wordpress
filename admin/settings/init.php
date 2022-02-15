<?php

// If this file is called directly, abort.
defined('WPINC') || die;

abstract class SettingsPage
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

        // Register Tabbed sections
        // This will register tabs as sections 
        if ( is_array($this->tabs) )
        {
            foreach( $this->tabs as $id => $args )
            {
                // Register new settings group
                register_setting(
                    "wpln_page_{$this->option_name}_{$id}",
                    "{$this->option_name}_{$id}",
                    array($this, 'sanitize')
                );

                // Create section
                add_settings_section(
                    "{$this->option_name}_section_{$id}",
                    $args['title'],
                    null,
                    "wpln_page_{$this->option_name}_{$id}",
                );
            }
        }
        else
        {
            // Settings page without tabs
            register_setting(
                $this->settings_path,
                $this->option_name, 
                array($this, 'sanitize')
            );
        }

        // Register fields
        foreach( $this->form_fields as $args )
        {
            add_settings_field(
                $args['field']['name'],
                $args['field']['label'],
                array($this, 'get_input'),
                "wpln_page_{$this->option_name}_" . $args['tab'],
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

    protected function input_field($name, $label, $type = 'text', $autocomplete = false)
    {

        printf(
            '<input id="%s" type="%s" autocomplete="%s" name="%s" value="%s" />',
            $name,
            $type,
            $autocomplete ? 'on' : 'off',
            $this->get_field_name($name),
            $this->get_field_value($name)
        );
        if ($type !== 'hidden') {
            printf(
                '<br><label>%s</label>',
                $label
            );
        }
    }

    public function create_input_field($args = [])
    {
        $this->input_field($args['key'], $args['label'], $args['type'] ?? 'text', $args['autocomplete'] ?? false);
    }

    protected function add_section($data = [])
    {
        add_settings_section($data['key'], $data['title'], null, $this->settings_path);
    }

    protected function add_input_field($data = [])
    {
        add_settings_field($data['key'], $data['name'], array($this, 'create_input_field'), $this->settings_path, $data['section'], $data);
    }

    protected function add_custom_field($data = [], $callback, $args = [])
    {
        add_settings_field($data['key'], $data['name'], $callback, $this->settings_path, $data['section'], $args);
    }

    protected function add_custom_input_field($data = [], $callback)
    {
        add_settings_field($data['key'], $data['name'], $callback, $this->settings_path, $data['section'], $data);
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
                    $args['tab'],
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
                esc_attr($parsed_args['description'])
            );
        }

        // Generate and return input field html output
        echo join(' ', $output);
    }
}
