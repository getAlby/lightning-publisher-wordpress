<?php

// If this file is called directly, abort.
defined('WPINC') || die;

abstract class SettingsPage
{
    protected $settings_path;
    protected $template_html;
    protected $option_name;
    protected $sections;
    protected $form_fields;

    public $options;
    public $plugin;

    protected $page;
    protected $page_title;
    protected $menu_title;

    public function __construct($plugin, $page)
    {
        $this->plugin      = $plugin;
        $this->page        = $page;
        $this->options     = get_option($this->option_name);
        $this->form_fields = array();
        
        $this->set_translations();
        
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
            $this->get_page_title(),
            $this->get_menu_title(),
            'manage_options',
            $this->settings_path,
            array($this, 'renderer')
        );
    }

    public function init_fields()
    {

        if ()
        register_setting(
            $this->settings_path,
            $this->option_name, 
            array($this, 'sanitize')
        );
    }

    public function renderer() {

        if ( empty($this->template_html) )
            return;

        // Include HTML file
        include $this->get_template_path($this->template_html);
    }

    public function sanitize($inputs)
    {
        $new_input = array();

        foreach ($inputs as $key => $input)
        {
            if (isset($input)) {
                $new_input[$key] = sanitize_text_field($input);
            }
        }

        return $inputs;
    }

    public function get_field_name($name)
    {
        return $this->option_name[$name];
    }

    public function get_field_value($name)
    {
        if (!isset($this->options[$name]))
            return '';

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



    /**
     * Generate markup output for <input> element
     * 
     * @param  string $option_name [description]
     * @param  array   $args        [description]
     * 
     * @return misc
     */
    public function get_input( $option_name = '', $args = array() )
    {
        /**
         * Deafult values that will be merged with $args
         */
        $defaults = array(
            'type'         => 'text',
            'class'        => 'regular-text',
            'name'         => $this->get_field_name($option_name),
            'value'        => $this->get_field_value($option_name),
            'placeholder'  => '',
            'autocomplete' => 'off',
            'label'        => '',
            'description'  => ''
        );

        
        // Merge args with defaults and filter empty values
        $parsed_args = wp_parse_args($args, $defaults);
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

            // Append everything else
            $output[] = sprintf(
                '%s="%s"',
                $arg,
                $val
            );
        }

        /**
         * For checkbox type we want to display label inline with input element
         */
        if ( 'checkbox' != $parsed_args['type'] )
        {
            $output[] = '<br>';
        }

        // Append label
        $output[] = sprintf(
            '<label>%s</label>',
            esc_attr($parsed_args['label'])
        );

        /**
         * Additional description if provided
         * Extra "help" instructions block below the field
         */
        if ( ! empty($parsed_args['description']) )
        {
            $output[] = sprintf(
                '<div class="description">%s</div>',
                esc_attr($parsed_args['description'])
            );
        }

        // Generate and return input field html output
        return join(' ', $output);
    }
}
