<?php

if ( ! defined( 'ABSPATH' ) ) exit;


if (!class_exists('WPAdm_Method_Class')) {
    abstract class WPAdm_Method_Class {
        /**
        * @var mixed
        */
        protected $params;

        /**
        * @var WPAdm_result
        */
        protected  $result;

        /**
        * @param mixed $params
        */
        public function __construct($params) {
            $this->params = $params;
            $this->result = new WPAdm_Result();
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            $this->result->setError('');
        }

        abstract function getResult();

        public function isError()
        {
            $error = $this->result->getError();
            return isset($this->result) && !empty( $error );
        }
        public function get_results()
        {
            return $this->result;
        }
    }
}