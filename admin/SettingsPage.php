<?php


abstract class SettingsPage
{
    protected $settings_path;
    protected $option_name;

    public $options;
    public $plugin;

    protected $page;
    protected $page_title;
    protected $menu_title;

    public function __construct($plugin, $page)
    {
        $this->plugin = $plugin;
        $this->page = $page;
        $this->options = get_option($this->option_name);
        add_action('admin_menu', array($this, 'init_page'));
        add_action('admin_init', array($this, 'init_fields'));
    }

    public function init_page()
    {
        add_submenu_page($this->page, $this->page_title, $this->menu_title, 'manage_options', $this->settings_path, array($this, 'renderer'));
    }

    public function init_fields()
    {
        register_setting($this->settings_path, $this->option_name,  array($this, 'sanitize'));
    }

    abstract public function renderer();

    public function sanitize($inputs)
    {
        $new_input = array();
        echo json_encode($inputs);
        foreach ($inputs as $key => $input) {
            if (isset($input)) {
                $new_input[$key] = sanitize_text_field($input);
            }
        }
        return $inputs;
    }

    public function get_field_name($name)
    {
        return "$this->option_name[$name]";
    }

    public function get_field_value($name)
    {
        if (!isset($this->options[$name])) return '';
        return  esc_attr($this->options[$name]);
    }
}
