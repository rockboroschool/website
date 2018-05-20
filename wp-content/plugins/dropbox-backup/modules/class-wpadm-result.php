<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Result')) {
    class WPAdm_Result {
        /*
        * Status of the request.
        * Can be either a "success" - the request is successful,
        * or "error" - in an error was encountered
        * @var string
        */
        private $result;

        /*
        * Text of the error that occurred during query execution
        * @var string
        */
        private $error = '';

        /*
        * Data obtained during query execution
        * @var mixed
        */
        private $data;

        /*
        * Total size of backup
        */
        private $size;

        const WPADM_RESULT_SUCCESS = 'success';
        const WPADM_RESULT_ERROR = 'error';

        public function __construct() {
            $this->result = WPAdm_Result::WPADM_RESULT_ERROR;
        }

        /**
        * @param mixed $data
        */
        public function setData($data)
        {
            $this->data = $data;
        }

        public function setValue($val, $data)
        {
            $this->$val = $data;
        }

        /**
        * @return mixed
        */
        public function getData()
        {
            return $this->data;
        }

        /**
        * @param string $error
        */
        public function setError($error)
        {
            WPAdm_Core::log($error);
            $this->error = $error;
        }

        /**
        * @return string
        */
        public function getError()
        {
            return $this->error;
        }

        /**
        * @param int $size
        */
        public function setSize($size)
        {
            $this->size = $size;
        }

        /**
        * @return int
        */
        public function getSize()
        {
            return $this->size;
        }

        /**
        * @param string $result
        */
        public function setResult($result)
        {
            $this->result = $result;
        }

        /**
        * @return string
        */
        public function getResult()
        {
            return $this->result;
        }

        public function exchangeArray(array $a) {
            $this->result   = (isset($a['result'])) ? $a['result']  : '';
            $this->data     = (isset($a['data']))   ? $a['data']    : '';
            $this->error    = (isset($a['error']))  ? $a['error']   : '';
            $this->size     = (isset($a['size']))   ? $a['size']    : '';
        }

        public function toArray($all = false) {
            
            if ($all) {
                return get_object_vars( $this );
            } else {
                return array(
                    'result' => $this->getResult(),
                    'error' => $this->getError(),
                    'data' => $this->getData(),
                    'size' => $this->getSize()
                );
            }
        }
    }
}