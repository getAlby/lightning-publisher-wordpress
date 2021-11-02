<?php
interface SettingsPage
{
    public function __construct($plugin);
    public function renderer();
    public function initFields();
    public function initPage();
}
