<?php
namespace hws;

use Exception;
use GuzzleHttp\Client;

class Ghttp
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
	public $options = [];
	
	public function __construct($url,$isJsonResponse = true){
		$this->url = $url;
		$this->isJsonResponse = $isJsonResponse;		
	}
	
	public function getURL($id = false, $get = []){
		return $this->url . ($id ? '/' . $id : '') . (empty($get) ? '' : '?' . http_build_query($get));
    }
	
    public function setHeader($key, $value){
		$this->headers[$key] = $value;
	}
		
	public function GET($get_params = false){
        return $this->send($get_params);
	}
	
	public function VIEW($id = false,$get_params = false){
        return $this->send($get_params,false,$id);
    }

    public function CREATE($params, $get_params = false){
		$this->method = 'POST';
        return $this->send($get_params,$params);
    }

    public function UPDATE($id = false, $params, $get_params = false){
        $this->method = 'PUT';
        return $this->send($get_params,$params,$id);
    }

    public function DELETE($id = false, $get_params = false){
        $this->method = 'DELETE';
        return $this->send($get_params,false,$id);
    }

    public function send($get_params = true, $post_params = false, $id = false){

		if ($this->isJsonRequest){
			$this->setHeader('Content-type', 'application/json');
		}
		$options = array_merge([
			'timeout'  => 30,
			'verify' => false, //SSL
			'headers' => $this->headers
		],$this->options);
		$client = new Client($options);
		$this->hasError = false;
		
		
		$params = [];

		if($post_params){
			if ($this->isJsonRequest){
				$params['json'] = $post_params;
			}else{
				$params['form_params'] = $post_params;
			}
		}

		try {
			$response = $client->request($this->method,$this->getUrl($id,$get_params),$params);
			$this->response = (string)$response->getBody();
			$this->status = $response->getStatusCode();
		} catch (Exception $e) {
			report($e);
			$this->doLog(true,"GHttp Error: ".$e->getMessage(),array_merge($options,$params,['method'=>$this->method],['url'=>$this->getUrl($id,$get_params)]));
			if($this->ignore_errors == false){
				if(config('app.debug'))	throw $e;
				sendError('Kommunikációs hiba történt!');
			}
			// $this->response = (string)$response->getBody();
			$this->status = $e->getCode();
		}
		
		if ($this->status == 0 || $this->status >= 400) {
			$this->hasError = true;
			$this->doLog(true,"GHttp status Error:",array_merge($options,$params,['method'=>$this->method],['url'=>$this->getUrl($id,$get_params)]));
			if($this->ignore_errors == false)sendError('Kommunikációs hiba történt!');
		}

		$oldresponse = $this->response;
		$this->response = $this->isJsonResponse ? json_decode($this->response, true) : $this->response;
		if($this->isJsonResponse){
			if(json_last_error()){
				$this->hasError = true;
				$this->response = $oldresponse;
				$this->doLog(true,"GHttp json_last_error: (bad json response)",array_merge($options,$params,['method'=>$this->method],['url'=>$this->getUrl($id,$get_params)]));
				if($this->ignore_errors == false)sendError('Kommunikációs hiba történt!');
			}
		}
		
		$this->doLog(false,"GHttp send",array_merge($options,$params,['method'=>$this->method],['url'=>$this->getUrl($id,$get_params)]));
		return $this->response;
    }
	
	public function doLog($error,$msg,$options){
		// if(!$error && $this->method === 'GET')return;
		
		$msg2 = $msg . "\nStatusCode:" . $this->status . "\n\n". print_r($this->response,true) . "\n\noptions:" . print_r($options,true);
		if($error){
			logger()->error($msg2);
		}else{
			logger()->info($msg2);
		}
    }

}
