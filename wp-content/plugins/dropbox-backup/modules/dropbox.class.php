<?php
error_reporting(E_ALL ^E_NOTICE ^E_WARNING);

require dirname(__FILE__)."/OAuthSimple.php";
require dirname(__FILE__)."/OAuth2/Client.php";

class dropbox
{
    private $app_key;
    private $app_secret;
    private $token;

    private $max_filesize_mb = 150;
    private $chunk_size_mb   = 50;
    private $request_token;

    private $api_url         = "https://api.dropbox.com/1/";   // api.dropboxapi.com      api.dropbox.com
    private $api_url_content = "https://api-content.dropbox.com/1/";

    private $api_url2         = "https://api.dropboxapi.com/2/";
    private $api_url_content2 = "https://content.dropboxapi.com/2/";
    private $api_auth         = "https://www.dropbox.com/oauth2/authorize";
    private $api_auth_token   = "https://api.dropboxapi.com/oauth2/token";

    private $oauth2 = false;
    public  $oauth2_obj = null;


    /**
    * @param $app_key
    * @param $app_secret
    * @param string $token - постоянный токен
    */
    public function __construct ($app_key, $app_secret, $token = "", $is_oauth2 = false)
    {
        $this->app_key    = $app_key;
        $this->app_secret = $app_secret;
        $this->token      = $token;
        if ($is_oauth2) {
            $this->is_oauth2($is_oauth2);
        }
    }

    public function setAccessToken2($token = '', $type = '')
    {
        if (!empty($token)) {
            $this->is_oauth2(true);  
            $this->oauth2_obj->setAccessToken($token);
            if (!empty($type)) {
                if ($type == 'bearer') {
                    $this->oauth2_obj->setAccessTokenType(Client::ACCESS_TOKEN_BEARER);
                }
            }
        }
    }

    public function isAuth2()
    {
        return (!is_null($this->oauth2_obj) && $this->oauth2_obj->hasAccessToken());
    }

    public function is_oauth2($set = false)
    {
        $this->oauth2 = $set;
        if ($set) {
            $this->oauth2_obj = new Client($this->app_key, $this->app_secret);
        }
    }

    /**
    * Проверка на то, был ли получен постоянный токен
    *
    * @return bool
    */
    public function isAuth()
    {
        return !empty($this->token);
    }

    /**
    * Получение временного токена, необходимого для авторизации и получения токена постоянного
    *
    * @return mixed
    */
    public function getRequestToken()
    {
        if (!$this->request_token) {
            $q = $this->doCurl($this->api_url."oauth/request_token");
            parse_str($q, $data_url);
            $this->request_token = $data_url;
        }

        return $this->request_token;
    }

    /**
    * Авторизация, шаг1: Генерация ссылки для авторизации приложения
    *
    * @param $callback - URL для возвращения токена после авторизации
    * @return string
    */
    public function generateAuthUrl($callback)
    {            
        if ($this->oauth2)   {
            return $this->oauth2_obj->getAuthenticationUrl($this->api_auth); 
        } else {
            $request_token = $this->getRequestToken();
            return "https://www.dropbox.com/1/oauth/authorize?oauth_token=".$request_token['oauth_token']."&oauth_callback=".urlencode($callback);
        }


    }


    /**
    * Получение токена, необходимого для запросов к API
    *
    * @param $request_token - временный токен, полученный функцией getRequestToken
    * @return string токен
    */
    public function getAccessToken($request_token)
    {
        if ($this->oauth2) {
            $params = array('code' => $request_token);   // set code
            try {
                //
                $response = $this->oauth2_obj->getAccessToken($this->api_auth_token, 'authorization_code', $params);
            } catch ( ClientException $e ) {
                $new_url = $this->getIp4ByHost($this->api_auth_token);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_auth_token);
                        $response = $this->oauth2_obj->getAccessToken($new_url, 'authorization_code', $params, array('Host' => $url['host']));
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Auth is invalid';
                    return $result;
                }
            } catch ( ClientInvalidArgumentException $e) {
                $new_url = $this->getIp4ByHost($this->api_auth_token);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_auth_token);
                        $response = $this->oauth2_obj->getAccessToken($new_url, 'authorization_code', $params, array('Host' => $url['host']));
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Auth is invalid';
                    return $result;
                }
            }
            $this->oauth2_obj->setAccessToken($response['result']['access_token']);
            if ($response['result']['token_type'] == 'bearer') {
                $this->oauth2_obj->setAccessTokenType( Client::ACCESS_TOKEN_BEARER );
            }
            return $response['result'];
        } else {
            if (!empty($this->token)) {
                return $this->token;
            }

            $this->request_token = $request_token;

            parse_str($this->doCurl($this->api_url."oauth/access_token"), $parsed_str);

            return $parsed_str;
        }
    }

    public function getIp4ByHost($url)
    {
        $host = '';
        if (is_array($url) && isset($url['host'])) {
            $host = $url['host'];
        } elseif (is_string($url)) {
            $url = parse_url($url);
            $host = $url['host'];
        }
        $ips = false;
        if (!empty($host) && function_exists('gethostbynamel')) {
            $ips = gethostbynamel($host);
            $url['host'] = array_pop($ips);
            $ips = $this->unparse_url($url);
        }
        return $ips;
    }

    public function unparse_url($parsed_url) 
    { 
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
        $pass     = ($user || $pass) ? "$pass@" : ''; 
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
        return "$scheme$user$pass$host$port$path$query$fragment"; 
    } 

    /**
    * Удаление файла
    *
    * @param $file
    * @return mixed
    */
    public function deleteFile($file)
    {
        if ($this->oauth2) {
            $this->oauth2_obj->setPOSTJSON(true);
			
            try {
                $res = $this->oauth2_obj->fetch($this->api_url2 . 'files/delete', array('path' => "/" . $file), Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8'));
            } catch (ClientInvalidArgumentException $e ) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $new_url . 'files/delete', array('path' => "/" . $file), Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Create dir is invalid';
                    return $result;
                }
            } catch (ClientException $e) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $new_url . 'files/delete', array('path' => "/" . $file), Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Create dir is invalid';
                    return $result;
                }
            }
            return $res;
        } else {
            return $this->doApi("fileops/delete", "POST", array(
                "path" => $file,
                "root" => "auto"
            ));
        }
    }

    /**
    * Удаление нескольких файлов
    *
    * @param array $files
    * @return array
    */
    public function deleteFiles($files = array())
    {
        $result = array();

        foreach ($files as $file) {
            $do = $this->deleteFile($file);
            $result[] = array (
                "name"      => $file,
                "result"    => $do['error'] ? 0 : 1
            );
        }

        return $result;
    }

    /**
    * Перемещение файлов
    *
    * @param $from - откуда
    * @param $to - куда
    * @return mixed
    */
    public function moveFile ($from, $to)
    {
        if ($this->oauth2) {
            $this->oauth2_obj->setPOSTJSON(true);
            return $this->oauth2_obj->fetch($this->api_url2 . 'files/move', array('from_path' => $from, 'to_path' => $to), Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json'));
        } else {
            return $this->doApi("fileops/move", "POST", array(
                "root"       => "auto",
                "from_path"  => $from,
                "to_path"    => $to
            ));
        }
    }

    public function listing($path = '/', $recursive = false, $is_folder = true)
    {
        if ($this->oauth2) {
            $this->oauth2_obj->setPOSTJSON(true);
            $data_send = array( 'path'=> '/' . ltrim( $path, '/' ) );
            if ($recursive) {
                $data_send['recursive'] = true;
            }  

            try {
                $dir =  $this->oauth2_obj->fetch($this->api_url2 . 'files/list_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8'));
            } catch (ClientInvalidArgumentException $e ) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $new_url . 'files/list_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Files list is invalid';
                    return $result;
                }
            } catch (ClientException $e) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $new_url . 'files/list_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Files list is invalid';
                    return $result;
                }
            }

        } else {
            $dir = $this->doApi("metadata/auto/".ltrim($path, "/"), "GET", array(
                "list" => TRUE
            ));
        }
		
        if ($dir['error']) {
            return $dir;
        } elseif (isset($dir['is_dir']) && !$dir['is_dir']) {  //this not folder
            return array (
                "size"  => $dir['size'],
                "bytes"  => $dir['bytes'],
                "date"  => $dir['modified'],
                "name"  => $dir['path']
            );
        } 
        $all_size = 0;
        $items   = array();
        if (isset($dir['contents'])) {
            foreach ($dir['contents'] as $item) {
                if (!$item['is_dir']) {
                    switch (substr($item['size'], -2)) {
                        default:
                            $size = $item['size'];
                            break;

                        case "KB":
                            $size = substr($item['size'], 0, -2)*1024;
                            break;

                        case "MB":
                            $size = substr($item['size'], 0, -2)*1024*1024;
                            break;

                        case "GB":
                            $size = substr($item['size'], 0, -2)*1024*1024*1024;
                            break;
                    }
                    $all_size += $size;
                }

                $items[]  = array (
                    "type"  => $item['is_dir'] ? "dir" : "file",
                    "date"  => $item['modified'],
                    "name"  => basename($item['path']),
                    "size"  => $item['size']
                );
            }

            $dir_result = array (
                "size"  => $this->bite2other($all_size),
                "date"  => $dir['modified'],
                "name"  => $dir['path'],
                "items" => $items
            );
        } elseif (isset($dir['result']['entries'])) {
            foreach ($dir['result']['entries'] as $item) {  
                if (isset($item['.tag'])) {
                    switch($item['.tag']) {
                        case 'folder' : 
                            if ($is_folder) {
                                $items[]  = array (
                                    "type"  =>  "dir",
                                    "date"  => '',
                                    "name"  => basename($item['path_display']),
                                    "size"  => 0
                                );
                            }
                            break;
                        case 'file' : 
                            $items[]  = array (
                                "type"  =>  "file",
                                "date"  => $item['client_modified'],
                                "name"  => $item['name'],
                                "size"  => $item['size']
                            );
                            $all_size += $item['size']; 
                            break;
                    }
                }
            }
            $dir_result = array (
                "size"  => $this->bite2other($all_size),
                "date"  => 0,
                "name"  => $path,
                "items" => $items
            );
        }
        return $dir_result;
    }

    private function bite2other($size)
    {
        $kb = 1024;
        $mb = 1024 * $kb;
        $gb = 1024 * $mb;
        $tb = 1024 * $gb;

        if ($size < $kb) {
            return $size.' B';
        } else if ($size < $mb) {
            return round($size / $kb, 2).' KB';
        } else if ($size < $gb) {
            return round($size / $mb, 2).' MB';
        } else if ($size < $tb) {
            return round($size / $gb, 2).' GB';
        } else {
            return round($size / $tb, 2).' TB';
        }
    }

    /**
    * Скачивание файла
    *
    * @param $file
    * @param $server_path - путь, куда скачать на сервер. Если задан -- вернет путь к файлу в случае успешной загрузки.
    * @return array
    */
    public function downloadFile($file, $server_path = "")
    {
        //$isset = $this->listing($file);

        //if (!$isset['error'])
        //{
        //Отдаем в браузер
        if (empty($server_path)) {

            if ($this->oauth2) {
                try  {
                    $this->oauth2_obj->fetch($this->api_url_content2 . 'files/download', 
                        array('path' => $file), Client::HTTP_METHOD_POST, 
                        array(
                            'socket' => array('file_download' => $server_path),
                            CURLOPT_BINARYTRANSFER => 1,
                            CURLOPT_RETURNTRANSFER => 0,  
                        )
                    );
                } catch(ClientException $e) {

                    $new_url = $this->getIp4ByHost($this->api_url_content2);
                    if ($new_url) {
                        try {
                            $url = parse_url($this->api_url_content2);
                            $this->oauth2_obj->fetch($new_url . '/files/download', 
                                array('path' => $file), Client::HTTP_METHOD_POST, 
                                array(
                                    'socket' => array('file_download' => $server_path),
                                    CURLOPT_BINARYTRANSFER => 1,
                                    CURLOPT_RETURNTRANSFER => 0,
                                    'Host' => $url['host'], 
                                )
                            );
                        } catch ( ClientException $e ) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        } catch ( ClientInvalidArgumentException $e) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        }
                    } else {
                        $result['error'] = 1;
                        $result['text'] = 'Create dir is invalid';
                        return $result;
                    }
                } catch (ClientInvalidArgumentException $e) {
                    $new_url = $this->getIp4ByHost($this->api_url_content2);
                    if ($new_url) {
                        try {
                            $url = parse_url($this->api_url_content2);
                            $this->oauth2_obj->fetch($new_url . 'files/download', 
                                array('path' => $file), Client::HTTP_METHOD_POST, 
                                array(
                                    'socket' => array('file_download' => $server_path),
                                    CURLOPT_BINARYTRANSFER => 1,
                                    CURLOPT_RETURNTRANSFER => 0,
                                    'Host' => $url['host'],
                                )
                            );
                        } catch ( ClientException $e ) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        } catch ( ClientInvalidArgumentException $e) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        }
                    } else {
                        $result['error'] = 1;
                        $result['text'] = __( 'Create dir is invalid', 'dropbox-backup' );
                        return $result;
                    }
                }


            } else {
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private", false);
                header("Content-Type: application/force-download");
                header('Content-Disposition: attachment; filename="'.$file.'"');
                header("Content-Transfer-Encoding: binary");
                $this->doApi("files/auto/".ltrim($file, "/"), "GET", array(), array(
                    'socket' => array('file_download' => $server_path),
                    CURLOPT_BINARYTRANSFER => 1,
                    CURLOPT_RETURNTRANSFER => 0,
                    ), TRUE);
            }
            exit;
        } else { //Скачиваем на сервер
            //Файл недоступен для записи
            if (!$fd = fopen($server_path, "wb")) {
                return array (
                    "error" => 1,
                    "text"  => "File '".$server_path."' not writable"
                );
            }

            if ($this->oauth2) {    
                $this->oauth2_obj->setPOSTJSON(false);
                $res = $this->oauth2_obj->fetch($this->api_url_content2 . 'files/download', array(), 
                    Client::HTTP_METHOD_POST, array('Dropbox-API-Arg' => json_encode( array('path' => '/' . ltrim( $file , '/' ) ) ) ) );
                if (isset($res['code']) && $res['code'] != 200) {
                    if (is_array($res['result'])) {
                        return  array (
                            "error" => 1,
                            "text"  => $res['result']['error_summary']
                        );
                    } else {
                        return  array (
                            "error" => 1,
                            "text"  => $res['result']
                        );
                    }
                } else {
                    fwrite($fd, $res['result']);
                }
            } else {
                $this->doApi("files/auto/".ltrim($file, "/"), "GET", array(), array(
                    'socket' => array('file_download' => $server_path),
                    CURLOPT_BINARYTRANSFER => 1,
                    CURLOPT_RETURNTRANSFER => 0,
                    CURLOPT_FILE           => $fd
                    ), TRUE);
            }

            fclose($fd);

            return $server_path;
        }

        //}
        // else {
        //     return $isset;
        // }
    }

    /**
    * Поделиться файлом
    *
    * @param $file
    * @param bool $short_url - короткая ссылка
    * @return mixed
    */
    public function shareFile($file, $short_url = TRUE)
    {
        if($this->oauth2) {
            $this->oauth2_obj->setPOSTJSON(true);
            $result = $this->oauth2_obj->fetch($this->api_url2 . 'sharing/create_shared_link', array('path' => $file), Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json') );
        } else {
            $result = $this->doApi("shares/auto/".ltrim($file, "/"), "POST", compact("short_url"));
        }
        return $result['error'] ? $result : ($result['result']) ? $result['result']['url'] : $result['url'];
    }

    /**
    * Загрузка нескольких файлов
    *
    * @param $files - массив формата файл_на_сервере => файл_в_дропбоксе
    *
    * @return array - информация о списке файлов
    */
    public function uploadFiles($files = array())
    {
        $result = array();

        foreach ($files as $file => $dbx) {
            $do = $this->uploadFile($file, $dbx);
            $result[] = array (
                "name"      => $file,
                "result"    => $do['error'] ? 0 : 1
            );
        }

        return $result;
    }

    /**
    * Загрузка файла
    *
    * @param $file_path - путь к файлу на сервере
    * @param $dropbox_path - куда загружать, если не задан, будет загружен в корень дропбокса с таким же именем
    * @param bool $overwrite - заменять ли файлы со схожими именами
    * @return mixed
    */
    public function uploadFile($file_path, $dropbox_path = "", $overwrite = FALSE)
    {
        if (empty($dropbox_path)) {
            $dropbox_path = basename($file_path);
        }

        if (!is_file($file_path)) {
            return array(
                "error" => 1,
                "text"  => "File '".$file_path."' not found."
            );
        }

        $fsize = filesize($file_path);
        $file = fopen($file_path, "rb");

        if ($fsize/1024/1024 < $this->max_filesize_mb)
        {
            if ($this->oauth2) {
                try {
                    $this->oauth2_obj->setPOSTJSON(false);
                    $result = $this->oauth2_obj->fetch(
                        $this->api_url_content2 . 'files/upload', 
                        file_get_contents($file_path),
                        Client::HTTP_METHOD_POST, 
                        array(
                            'Dropbox-API-Arg' => json_encode( array( 'path' => '/' . ltrim( $dropbox_path, '/' ) ) ),
                            'Content-Type' => 'application/octet-stream',
                        )
                    );
                } catch(ClientException $e) {

                    $new_url = $this->getIp4ByHost($this->api_url_content2);
                    if ($new_url) {
                        try {
                            $url = parse_url($this->api_url_content2);
							WPAdm_Core::log( $new_url . 'files/upload' );
                            $result = $this->oauth2_obj->fetch(
                                $new_url . 'files/upload', 
                                file_get_contents($file_path),
                                Client::HTTP_METHOD_POST, 
                                array(
                                    'Dropbox-API-Arg' => json_encode( array( 'path' => '/' . ltrim( $dropbox_path, '/' ) ) ),
                                    'Content-Type' => 'application/octet-stream',
                                    'Host' => $url['host']
                                )
                            );
                        } catch ( ClientException $e ) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        } catch ( ClientInvalidArgumentException $e) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        }
                    } else {
                        $result['error'] = 1;
                        $result['text'] = 'Create dir is invalid';
                        return $result;
                    }
                } catch (ClientInvalidArgumentException $e) {
                    $new_url = $this->getIp4ByHost($this->api_url_content2);
                    if ($new_url) {
                        try {
                            $url = parse_url($this->api_url_content2);
                            $result = $this->oauth2_obj->fetch(
                                $new_url . 'files/upload', 
                                file_get_contents($file_path),
                                Client::HTTP_METHOD_POST, 
                                array(
                                    'Dropbox-API-Arg' => json_encode( array( 'path' => '/' . ltrim( $dropbox_path, '/' ) ) ),
                                    'Content-Type' => 'application/octet-stream',
                                    'Host' => $url['host']
                                )
                            );
                        } catch ( ClientException $e ) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        } catch ( ClientInvalidArgumentException $e) {
                            $result['error'] = 1;
                            $result['text'] = $e->getMessage();
                            return $result;
                        }
                    } else {
                        $result['error'] = 1;
                        $result['text'] = __( 'Create dir is invalid', 'dropbox-backup' );
                        return $result;
                    }
                }

                if (is_string($result['result']) && $result['code'] != 200) {
                    $result['error'] = 1;
                    $result['text'] = $result['result'];
                }
                if (isset($result['result']['size']) && $fsize != $result['result']['size'] && $result['result']['size'] == 0 ) {
                    $result['error'] = 1;
                    $result['text']  = __('Error send to Dropbox Cloud, size mismath', 'dropbox-backup');
                }
            } else {             
                $result = $this->doApi("files_put/auto/".ltrim($dropbox_path, "/"),
                    "PUT",
                    compact ("overwrite"),
                    array(
                        'socket' => array('file' => $file_path),
                        CURLOPT_INFILE          => $file,
                        CURLOPT_INFILESIZE      => filesize($file_path),
                        CURLOPT_BINARYTRANSFER  => 1,
                        CURLOPT_PUT             => 1
                    ),
                    TRUE);
            }

        }
        //Файл слишком велик
        else {
            $upload_id = "";
            $offset    = 0;

            if ($this->oauth2) {
                $session = $this->oauth2_obj->fetch($this->api_url_content2 . 'files/upload_session/start', 
                    array(), Client::HTTP_METHOD_POST, 
                    array('Dropbox-API-Arg' => json_encode(array('close' => true) ),
                        'Content-Type' => 'application/octet-stream' ) 
                );
            }

            while(!feof($file)) {

                $chunk = min($fsize - $offset, $this->chunk_size_mb);
                $offset += $chunk;


                if ($this->oauth2) {
                    $result = $this->oauth2_obj->fetch($this->api_url_content2 . 'files/upload_session/append', 
                        array(), Client::HTTP_METHOD_POST, 
                        array('Dropbox-API-Arg' => json_encode(array('session_id' => $session['session_id'], 'offset' => $offset) ),
                            'Content-Type' => 'application/octet-stream' )
                    );
                } else {
                    $result = $this->doApi("chunked_upload",
                        "PUT",
                        compact("upload_id", "offset"),
                        array(
                            'socket' => array('file' => $file_path, 'dropbox_path' => $dropbox_path),

                            CURLOPT_INFILE          => $file,
                            CURLOPT_INFILESIZE      => $chunk,
                            CURLOPT_BINARYTRANSFER  => 1,
                            CURLOPT_PUT             => 1
                        ),
                        TRUE);
                }
                fseek($file, $offset);
                if($offset >= $fsize) {
                    break;
                }
                if ($this->oauth2) {
                    if ( !empty($result) ) {

                        break;
                    }
                } else {
                    if (empty($upload_id)) {
                        $upload_id = $result['upload_id'];
                    }
                }
            }
            if ($this->oauth2) {
                $result = $this->oauth2_obj->fetch($this->api_url_content2 . 'files/upload_session/finish', 
                    array(), Client::HTTP_METHOD_POST, 
                    array('Dropbox-API-Arg' => json_encode(array('cursor' => array('session_id' => $session['session_id'], 'offset' => $offset ), 
                        'commit' => array('path' => '/' . ltrim( $dropbox_path, '/' ) )
                        ) 
                        ),
                        'Content-Type' => 'application/octet-stream' )
                );
            } else {
                $result = $this->doApi("commit_chunked_upload/auto/".ltrim($dropbox_path, "/"),
                    "POST",
                    compact("upload_id", "overwrite"),
                    array(),
                    TRUE);
            }
        }

        @fclose($file);

        return $result;
    }

    /**
    * Создание директории
    *
    * @param $path
    * @return mixed
    */
    public function createDir($path)
    {
        if ( $this->oauth2 ) {
            $this->oauth2_obj->setPOSTJSON(true);
            $data_send = array( 'path' => "/" . ltrim($path, '/') );
            try {
                $dir =  $this->oauth2_obj->fetch($this->api_url2 . 'files/create_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8'));
            } catch (ClientInvalidArgumentException $e ) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $new_url . '/files/create_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Create dir is invalid';
                    return $result;
                }
            } catch (ClientException $e) {
                $new_url = $this->getIp4ByHost($this->api_url2);
                if ($new_url) {
                    try {
                        $url = parse_url($this->api_url2);
                        $dir = $this->oauth2_obj->fetch( $this->api_url2 . '/files/create_folder', $data_send, Client::HTTP_METHOD_POST, array('Content-Type' => 'application/json; charset=utf-8', 'Host' => $url['host']) );
                    } catch ( ClientException $e ) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    } catch ( ClientInvalidArgumentException $e) {
                        $result['error'] = 1;
                        $result['text'] = $e->getMessage();
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['text'] = 'Create dir is invalid';
                    return $result;
                }
            }
            return $dir;
        } else {
            return $this->doApi("fileops/create_folder", "POST", array(
                "root" => "auto",
                "path" => $path
            ));
        }
    }

    /**
    *  Информация об аккаунте
    *
    * @return mixed
    */
    public function accInfo()
    {

        return $this->doApi("account/info", "GET");
    }

    /** Запрос к API
    *
    * @param $op - операция (url)
    * @param $method - post/get
    * @param array $data - доп. данные, url запрос
    * @param array $opts - доп. параметры для curl
    * @param $api_content - использовать url для загрузки файлов
    * @return mixed
    */
    private function doApi ($op, $method, $data = array(), $opts = array(), $api_content = FALSE)
    {
        if (($method == "GET" || $method == "PUT") && count($data)) {
            $op .= "?".http_build_query($data);
        }

        $result = $this->doCurl((!$api_content ? $this->api_url : $this->api_url_content).$op, $method, $data, $opts);
        $return = json_decode($result, TRUE);
        return self::checkError($return);
    }

    /**
    * Обертка для отправки подписанного запроса через curl
    *
    * @param $url
    * @param string $method
    * @param array $data - POST данные
    * @param $opts - доп. параметры для curl
    * @return mixed
    */
    public function doCurl($url, $method = "POST", $data = array(), $opts = array())
    {
        $result = '';
        $oauth = new OAuthSimple($this->app_key, $this->app_secret);

        if (!$this->request_token && $this->token) {
            $this->request_token = $this->token;
        }

        if ($this->request_token) {
            $oauth->setParameters(array('oauth_token' => $this->request_token['oauth_token']));
            $oauth->signatures(array('oauth_secret'=> $this->request_token['oauth_token_secret']));
        }

        if ($method == "POST" && count($data)) {
            $oauth->setParameters(http_build_query($data));
        }

        $path = $url;
        $query = strrchr($url,'?');
        if(!empty($query)) {
            $oauth->setParameters(substr($query,1));
            $path = substr($url, 0, -strlen($query));
        }

        $signed = $oauth->sign(array(
            'action' => $method,
            'path'   => $path
            )
        );

        if (function_exists('curl_init')) {
            if (isset($opts['socket'])) {
                unset($opts['socket']);
            }
            $ch = curl_init($url);
            $opts += array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HEADER         => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
            );

            if ($method == "POST") {
                $opts[CURLOPT_POST]         = TRUE;
                $opts[CURLOPT_POSTFIELDS]   = http_build_query($data);
            }

            //$oauth = new OAuthSimple($this->app_key, $this->app_secret);

            /*if (!$this->request_token && $this->token) {
            $this->request_token = $this->token;
            }  

            if ($this->request_token) {
            $oauth->setParameters(array('oauth_token' => $this->request_token['oauth_token']));
            $oauth->signatures(array('oauth_secret'=> $this->request_token['oauth_token_secret']));
            }

            if ($method == "POST" && count($data)) {
            $oauth->setParameters(http_build_query($data));
            }

            $path = $url;
            $query = strrchr($url,'?');
            if(!empty($query)) {
            $oauth->setParameters(substr($query,1));
            $path = substr($url, 0, -strlen($query));
            }

            $signed = $oauth->sign(array(
            'action' => $method,
            'path'   => $path
            )
            );  */  
            $opts[CURLOPT_HTTPHEADER][] = "Authorization: ".$signed['header'];

            if ($method == "PUT") {
                $opts[CURLOPT_CUSTOMREQUEST] = "PUT";
            }
            curl_setopt_array($ch, $opts);
            $result = curl_exec($ch);

        } elseif (function_exists('fsockopen')) {
            include_once dirname(__FILE__) . '/HttpFsockopen.php';
            $socket = new HttpFsockopen($url, false);
            if ($method == 'POST') {
                $socket->setPostData($data);
            }
            if ($method == 'PUT') {
                if (isset($opts['socket']['file'])) {
                    $data = '';
                    srand( (double)microtime() * 1000000);
                    $file_data = join('', file( $opts['socket']['file'] ) ) ;
                    $rand =  substr( md5( rand( 0, 32000 ) ), 0, 10);
                    $boundary =  $rand; 
                    $data = ""; 
                    $data .= "Content-Disposition: form-data; name=\"" . basename($opts['socket']['file']) ."\"; filename=\"" . basename( $opts['socket']['file'] ) . "\"\r\n";
                    $data .= "Content-Type: application/x-www-form-urlencoded;\r\n";  //multipart/form-data;
                    $data .= "Content-length: " . strlen($file_data) . "\r\n";
                    $data .= "Connection: close\r\n\r\n";
                    $data .= $file_data ;
                    $socket->setStrHeaders($data);
                    $socket->setMethod("PUT");
                }
            }
            $socket->setHeaders('Authorization', $signed['header']);
            $res = $socket->exec();
            if ($method == 'GET') {
                if (isset($opts['socket']['file_download'])) {
                    $r = $this->getResult($res);
                    if (isset($r[1])) {
                        file_put_contents($opts['socket']['file_download'], $r[1]);
                    }
                }
            } 
            if ( empty($result) ) {
                $res = $this->getResult($res);
                if (isset($res[1])) {
                    $res1 = $this->getResult($res[1], "\r", true);
                    preg_match("/{.*}/is", trim($res1), $r);
                    if(isset($r[0])) {
                        $result =  $r[0];
                    } else {
                        $res1 = $this->getResult($res[1], "\n", true);
                        preg_match("/{.*}/is", trim($res1), $r);
                        if(isset($r[0])) {
                            $result =  $r[0];
                        } else {
                            $res1 = $this->getResult($res[1], "\r\n", true);
                            preg_match("/{.*}/is", trim($res1), $r);
                            if(isset($r[0])) {
                                $result =  $r[0];
                            }

                        }
                    }

                }
            }
        }
        return $result;
    }

    private function getResult($str, $patern = "\r\n\r\n", $is_json = false)
    {
        if ($is_json === false) {
            return explode($patern, $str);
        } else {
            $res = explode($patern, $str); 
            $n = count($res);
            $result = '';
            for($i = 0; $i < $n; $i++) {
                if ( preg_match_all("/[\{\"\:\,\}\s]+/is", trim($res[$i]), $r ) ) {
                    $result .= str_replace(array("\r", "\n"), '', $res[$i]); 
                }
            }
            return $result;
        }
    }


    private static function checkError($return)
    {
        if(!empty($return['error']))
            return array ("error" => 1, "text" => $return['error']);
        return $return;
    }


}
