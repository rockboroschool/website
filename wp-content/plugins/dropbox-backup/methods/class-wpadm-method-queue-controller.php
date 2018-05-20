<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WPAdm_Method_Exec
 */
if (!class_exists('WPAdm_Method_Queue_Controller')) {
    class WPAdm_Method_Queue_Controller extends WPAdm_Method_Class {
    
        /**
         * За сколько секунд до окончания выделенного времени завершать работу
         */
        const TIME_DEAD_LINE = 3; //сек
    
        /**
         * максимальное число рестартов
         */
        const MAX_COUNT_STEPS = 500;
    
    
        /**
         * Файл для хранения парметров, между "перезапусками"
         * @var string
         */
        private $queue_file = "";
    
        /**
         * Время "запуска" этого метода
         * @var int
         */
        private $stime = '';
    
        /**
         * Максимальное допустимая длительность(сек.) выполнения скрипта
         * @var int
         */
        private $max_execution_time = 10;
    
        /**
         * Список задач для выполнения
         * @var array of WPAdm_Command_Context
         */
        private $contexts = array();
    
        private $step = 1;
    
        private $id = '';
    
        public function __construct($params) {
            $this->stime = time();
            parent::__construct($params);
            $this->queue_file = WPAdm_Core::getTmpDir() . '/' . $this->params['id'] . '.queue';
            if (!file_exists($this->queue_file)) {
                $this->log("queue-file not exists: {$this->queue_file}");
                exit;
            }
            $queue = unserialize(file_get_contents($this->queue_file));
            $this->id = $queue['id'];
            $this->step = (isset($queue['step']) && (int)$queue['step'] > 1) ? (int)$queue['step']+1 : 1;
            $this->contexts = $queue['contexts'];
            $this->max_execution_time = ini_get('max_execution_time');
        }
    
        public function getResult()
        {
            while(!$this->timeIsOver() && $context = $this->getNextContext()) {
                $com = $context->get('command');
                $cmd = WPAdm_Command_Factory::getCommand($com);
                if ($cmd === null) {
                    $this->result->setError( str_replace(array('%d', '%com'), array(SITE_HOME, $com ), __('Website "%d" returned an error: Command "%com" not found. To solve this problem, we need to access the system logs of your hosting/server and/or from your backup, that you tried to create or simply send to us your FTP access data. You can send to us support request using "Help" button on plugin page.','dropbox-backup') ) );
                    $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                    array_unshift($this->contexts, $context);
                    $this->done();
                    return $this->result;
                } elseif (!$cmd->execute($context)) {
                    $this->result->setError( str_replace(array('%d', '%com'), array(SITE_HOME, $com ), __('Website "%d" returned some unknown error during command "%com" was running. To solve this problem, we need to access the system logs of your hosting/server and/or from your backup, that you tried to create or simply send to us your FTP access data. You can send to us support request using "Help" button on plugin page.','dropbox-backup') ) );
                    $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                    array_unshift($this->contexts, $context);
                    $this->done();
                    return $this->result;
                } else {
                    
                }
            }
    
            if ($this->step >= self::MAX_COUNT_STEPS) {
                $this->log('max_step: ' . $this->id);
                exit;
            }
            //если еще есть невыполненые задачи - рестаратуем
            if (!empty($this->contexts)) {
                //$this->restart();
            }
    
            // если все задачи выполнили, то пометим файл
    
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            $this->done();
            return $this->result;
        }
    
    
        private function done() {
            $this->save();
            rename($this->queue_file, $this->queue_file.'.done');
        }
    
        /**
         * @return WPAdm_Command_Context|null
         */
        private function getNextContext() {
            if(empty($this->contexts)) {
                return null;
            } else {
                $context = array_shift($this->contexts);
                $this->save();
            }
            return $context;
        }
    
        private function restart() {
            $this->step ++;
            $url = get_option('siteurl');
            $pu = parse_url($url);
            $host = $pu['host'];
    
            $data = array(
                'method'    =>  'queue_controller',
                'params'    =>  array(
                    'id'  =>  $this->id,
                    'step' => $this->step,
                ),
                'sign'      =>  '',
    
            );
    
            $socket = fsockopen($host, 80, $errno, $errstr, 30);
//            $pl =  (defined('WPADM_PLUGIN')) ? WPADM_PLUGIN . '_' : '';
            $dp = explode(DIRECTORY_SEPARATOR,dirname(dirname(__FILE__)));
            $pl = array_pop($dp) . '_';
            $data = $pl.'request=' . wpadm_pack( $data );
            fwrite($socket, "POST / HTTP/1.1\r\n");
            fwrite($socket, "Host: {$host}\r\n");
    
            fwrite($socket,"Content-type: application/x-www-form-urlencoded\r\n");
            fwrite($socket,"Content-length:".strlen($data)."\r\n");
            fwrite($socket,"Accept:*/*\r\n");
            fwrite($socket,"User-agent:Opera 10.00\r\n");
            fwrite($socket,"Connection:Close\r\n");
            fwrite($socket,"\r\n");
            fwrite($socket,"$data\r\n");
            fwrite($socket,"\r\n");
            sleep(1);
            fclose($socket);
            exit;
        }
    
        private function timeIsOver() {
            if ($this->max_execution_time == 0) {
                return false;
            }
            return (time() - $this->stime + self::TIME_DEAD_LINE > $this->max_execution_time);
        }
    
        private function save() {
            $file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue';
            $txt = serialize(
                array(
                    'id' => $this->id,
                    'step' => $this->step,
                    'contexts' => $this->contexts,
    
                )
            );
            file_put_contents($file, $txt);
            return $this;
        }
    
        private function log($txt) {
            //WPAdm_Core::log($txt, 'WPAdm_Method_Queue_Controller');
        }
    }
}