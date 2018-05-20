<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Command_Context')) {
    class WPAdm_Command_Context {
        private $params = array();
        private $error = '';

        public function addParam($key, $val)
        {
            $this->params[$key] = $val;
            return $this;
        }

        public function get($key)
        {
            if (isset($this->params[$key])) {
                return $this->params[$key];
            } else {
                return false;
            }
        }

        public function setError($error)
        {
            $this->error = $error;
            return $this;
        }

        public function getError() {
            return $this->error;
        }
    }
}