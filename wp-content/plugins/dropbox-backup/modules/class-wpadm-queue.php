<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
*
* Class WPAdm_Queue
*/
if (!class_exists('WPAdm_Queue')) {
    class WPAdm_Queue {

        /**
        * sleep, while waiting
        */
        const SLEEP_TIME = 2; //sec

        /**
        * the maximum number of falling asleep while waiting
        */
        const MAX_COUNT_SLEEPS = 1000;


        /**
        * @var array
        */
        private $contexts = array();

        /**
        * @var WPAdm_queue_status
        */
        private $status;

        /**
        * @var
        */
        private $id;

        private $error;

        private $user_agent = 'dropbox-backup user-agent';

        public function __construct($id) {
            $this->id = $id;
        }

        public function add(WPAdm_Command_Context $context) {
            
            $this->contexts[] = $context;
            return $this;
        }

        public function clear()
        {

            $file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue';
            if (file_exists($file)) {
                unlink($file);
            }

            $s = uniqid();
            $this->id = preg_replace("|(.*__).*|", '${1}'.$s, $this->id);

            $this->contexts = array();
            return $this;
        }

        public function execute() {
            $url = get_option('siteurl');
            $pu = parse_url($url);
            $host = $pu['host'];
            $path = isset($pu['path']) ? $pu['path'] . "/" : "/" ;

            $data = array(
            'method'    =>  'queue_controller',
            'params'    =>  array(
            'id'  =>  $this->id,
            ),
            'sign'      =>  '',

            );

            
            $dp = explode(DIRECTORY_SEPARATOR, DRBBACKUP_BASE_DIR);
            $pl = array_pop($dp);
            $wpadm = new WPAdm_Core($data, $pl, DRBBACKUP_BASE_DIR);
            return $wpadm->getResult()->toArray();
        }

        private function wait_result() {
            $step = 0;
            $done_file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue.done';
            while (!file_exists($done_file) && $step <= self::MAX_COUNT_SLEEPS) {
                $step ++;
                @sleep(self::SLEEP_TIME);
            }
            if (!file_exists($done_file)) {
                $this->error  = 'No result of the command';
            } else {
                $queue = unserialize(file_get_contents($done_file));
                if (isset($queue['contexts'][0])) {
                    $this->error  = $queue['contexts'][0]->getError();
                }
            }
            if (file_exists($done_file)) {
                unlink($done_file);
            }
            if (!empty($this->error) ) {
                return false;
            }
            return true;
        }
        private function deleteCommands()
        {
            $files = glob(WPAdm_Core::getTmpDir() . "/wpadm_method_*");
            if (!empty($files)) {
                for($i = 0; $i < $n; $i++) {
                    WPAdm_Core::rmdir($files[$i]);
                }
            }
        }

        public function save() {
            
            $this->deleteCommands();
            $file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue';
            $txt = serialize(
            array(
            'id' => $this->id,
            'contexts' => $this->contexts,
            )
            );
            file_put_contents($file, $txt);
            return $this;
        }

        public function getError() {
            return $this->error;
        }
    }
}