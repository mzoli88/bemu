<?php

namespace hws;

use Exception;

class Chttp
{
    public $url;
    public $method = 'GET';
    public $headers = [];
    public $isJsonRequest = true;
    public $isJsonResponse = true;
    public $ignore_errors = false;
    public $hasError = false;
    public $status;
    public $response;
    public $files = [];
    public $outHeaders = [];
    public $logs = [];
    public $noContentLog = false;
    public $noResponseLog = false;
    public $log_event_id;

    public $proxyParams = false;

    public function __construct($url, $isJsonResponse = true, $isJsonRequest = true)
    {
        $this->url = $url;
        $this->isJsonResponse = $isJsonResponse;
        $this->isJsonRequest = $isJsonRequest;
    }

    public function getURL($id = false, $get = [])
    {
        return $this->url . ($id ? '/' . $id : '') . (!empty($get) && is_Array($get) ? '?' . http_build_query($get) : '');
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function GET($get_params = [])
    {
        return $this->send($get_params);
    }

    public function VIEW($id = false, $get_params = [])
    {
        return $this->send($get_params, null, $id);
    }

    public function CREATE($params, $get_params = [])
    {
        $this->method = 'POST';
        return $this->send($get_params, $params);
    }

    public function UPDATE($id = false, $params, $get_params = [])
    {
        $this->method = 'PUT';
        return $this->send($get_params, $params, $id);
    }

    public function DELETE($id = false, $get_params = [], $params = null)
    {
        $this->method = 'DELETE';
        return $this->send($get_params, $params, $id);
    }

    public function setProxyParams($params)
    {
        $this->proxyParams = $params;
    }

    public function addUpload($key, $tmp_name, $fname, $type)
    {
        $this->files[$key] = curl_file_create($tmp_name, $type, $fname);
    }

    public function send($get_params = [], $post_params = null, $id = false)
    {
        $this->hasError = false;
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->getUrl($id, $get_params),
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_TIMEOUT => 1200,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (is_array($this->proxyParams)){
            $options = array_merge($options, [
                CURLOPT_PROXY => $this->proxyParams["proxy_host"],
                CURLOPT_PROXYPORT => $this->proxyParams["proxy_port"],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_PROXYUSERPWD => $this->proxyParams["proxy_user"].":".$this->proxyParams["proxy_pw"]
            ]);
        }
        
        if (empty($this->files) && $this->isJsonRequest) {
            $this->setHeader('Content-type', 'application/json');
            if ($post_params) {
                $data_string = json_encode($post_params);
                $options[CURLOPT_POSTFIELDS] = $data_string;
                $this->setHeader('Content-Length', strlen($data_string));
            }
        } else {
            if (!empty($this->files)) {
                $post_params = array_merge($post_params, $this->files);
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $post_params;
            } else {
                if ($post_params) {
                    if (is_array($post_params)) {
                        $options[CURLOPT_POSTFIELDS] = http_build_query($post_params);
                    } else {
                        $options[CURLOPT_POSTFIELDS] = $post_params;
                    }
                }
            }
        }

        if (!empty($this->headers)) {
            $options[CURLOPT_HTTPHEADER] = [];
            foreach ($this->headers as $key => $value) {
                $options[CURLOPT_HTTPHEADER][] = $key . ": " . $value;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $this->outHeaders = [];
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, [$this, 'getOutHeaders']);
        $this->response = curl_exec($curl);
        $error = curl_error($curl);
        $curl_info = curl_getinfo($curl);

        $this->status = $curl_info['http_code'];

        if ($error) {
            $this->hasError = true;
            $this->doLog(true, "cHttp Error: " . $error, $options);
            if ($this->ignore_errors == false) sendError('Kommunikációs hiba történt!');
        }

        if ($this->status == 0 || $this->status >= 400) {
            $this->hasError = true;
            $this->doLog(true, "cHttp status Error:", $options);
            if ($this->ignore_errors == false) sendError('Kommunikációs hiba történt!');
        }

        $oldresponse = $this->response;
        $this->response = $this->isJsonResponse ? json_decode($this->response, true) : $this->response;
        if ($this->isJsonResponse) {
            if (json_last_error()) {
                $this->hasError = true;
                $this->response = $oldresponse;
                $this->doLog(true, "cHttp json_last_error: (bad json response)", $options);
                if ($this->ignore_errors == false) sendError('Kommunikációs hiba történt!');
            }
        }
        curl_close($curl);
        $this->doLog(false, "cHttp send", $options);
        return $this->response;
    }

    public function getOutHeaders($c, $h)
    {
        $tmp = explode(':', $h);
        if (count($tmp) == 2) {
            $this->outHeaders[$tmp[0]] = $tmp[1];
        }
        return strlen($h);
    }

    static function cutFile($txt)
    {
        $txt = preg_replace('/"file_content":"([^"]*?)"/', '"file_content":"file_replaced",', $txt);
        $txt = preg_replace('/"password":"([^"]*?)"/', '"password":"****",', $txt);
        return $txt;
    }

    public function doLog($error, $msg, $options)
    {
        $fOptions = [
            'url' => key_exists(CURLOPT_URL, $options) ? $options[CURLOPT_URL] : '',
            'method' => key_exists(CURLOPT_CUSTOMREQUEST, $options) ? $options[CURLOPT_CUSTOMREQUEST] : '',
            'headers' => key_exists(CURLOPT_HTTPHEADER, $options) ? $options[CURLOPT_HTTPHEADER] : '',
            'content' => key_exists(CURLOPT_POSTFIELDS, $options) && $this->noContentLog == false ? self::cutFile($options[CURLOPT_POSTFIELDS]) : '',
        ];

        $msg2 = "CHTTP_LOG_" . $this->method . ": " . $msg . "\nStatusCode:" . $this->status . "\n\n" . ($this->noResponseLog == false ? print_r($this->response, true) : '') . "\n\noptions:" . print_r($fOptions, true);

        if ($error) {
            logger()->error($msg2);
            $this->logs[] = $msg2;
            if ($this->log_event_id) {
                logger()->stack(['audit', 'system'])->info($msg2, [
                    'event' => $this->log_event_id . ' - interface hívás',
                    'code' => 'Sikertelen',
                    'error' => 'Kommunikációs hiba történt!',
                    'user_login' => '-',
                    'user_name' => 'Technikai felhasználó',
                    // 'uuid' => '-',
                    'uri' => 'Interface hívás',
                    'method' => '-',
                ]);
            }
        } else {
            logger()->info($msg2);
            if ($this->log_event_id) {
                logger()->stack(['audit', 'system'])->info($msg2, [
                    'event' => $this->log_event_id . ' - interface hívás',
                    'code' => 'Sikeres',
                    'user_login' => '-',
                    'user_name' => 'Technikai felhasználó',
                    // 'uuid' => '-',
                    'uri' => 'Interface hívás',
                    'method' => '-',
                ]);
            }
        }
    }
}
