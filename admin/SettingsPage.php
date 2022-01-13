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

    protected function input_field($name, $label, $type = 'text', $autocomplete = false)
    {
        printf(
            '<input id="%s" type="%s" autocomplete="%s" name="%s" value="%s"  /><br><label>%s</label>',
            $name,
            $type,
            $autocomplete ? 'on' : 'off',
            $this->get_field_name($name),
            $this->get_field_value($name),
            $label
        );
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
}
