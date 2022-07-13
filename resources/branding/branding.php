<?php

class branding {
    private $config;

    public function __construct()
    {
        $this->config = include "config.php";

        $current_url = $_SERVER['HTTP_HOST'];
        $branding_override = ROOT . "/resources/branding/overrides/" . $current_url . ".php";
        if (file_exists($branding_override)) {
            $override_config = include $branding_override;
            $this->config = array_merge($this->config, $override_config);
        }
    }

    public function getField($field) {
        if (array_key_exists($field, $this->config)) {
            return $this->config[$field];
        } else {
            throw new Exception("Field $field was not found in the branding config");
        }
    }
}