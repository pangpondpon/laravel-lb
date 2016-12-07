<?php 

namespace LaravelLb;

use LaravelLb\Exceptions\InvalidFormatException;
use LaravelLb\Exceptions\InvalidRequestTypeException;

use LaravelLb\Request;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;

class LogicBoxes {

    private $testMode = true;
    private $userId = "";
    private $apiKey = "";
    private $format = "json";
    private $variables = [];
    private $requestType = "GET";

    public function __construct()
    {
        $this->interface = null;
        
        if(function_exists('config'))
        {
            $this->testMode = config('logicboxes.test_mode');
            $this->userId = config('logicboxes.auth_userid');
            $this->apiKey = config('logicboxes.api_key');
            $this->interface = config('logicboxes.interface');
        }   
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId = "")
    {
        $this->userId = $userId;
        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey = "")
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getTestMode()
    {
        return $this->testMode;
    }

    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function getRequestType()
    {
        return $this->requestType;
    }

    public function setRequestType($requestType)
    {
        if(!in_array($requestType, ['GET', 'POST']))
        {
            throw new InvalidRequestTypeException("Request type must be only GET or POST", 2);
        }

        $this->requestType = $requestType;
        return $this;
    }

    public function getCredentialQueryString()
    {
    	return "auth-userid={$this->userId}&api-key={$this->apiKey}";
    }

    public function getRootPath()
    {
    	$path = "https://";
    	if($this->testMode) $path .= "test.";

    	$path .= "httpapi.com/api";
    	return $path;
    }

    public function setResource($resource)
    {
    	$this->resource = $resource;
    	return $this;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        if(!in_array($format, ['json', 'xml']))
        {
            throw new InvalidFormatException('Logicboxes format can be only json or xml', 1);
        }

        $this->format = $format;
        return $this;
    }

    public function setMethod($method)
    {
    	$this->method = $method;
    	return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setVariables($variables)
    {
        $this->variables = $variables;
        return $this;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function call()
    {
        switch($this->getRequestType())
        {
            case "GET":
                return $this->get($this->resource, $this->method, $this->variables, $this->format);
            case "POST":
                return $this->post($this->resource, $this->method, $this->variables, $this->format);
        }
    }

    public function get($resource, $method, $variables = [], $format = "json")
    {
        $this->requestType = "GET";
        return $this->fire($resource, $method, $variables, $format);
    }

    public function post($resource, $method, $variables = [], $format = "json")
    {
        $this->requestType = "POST";
        return $this->fire($resource, $method, $variables, $format);
    }

    private function fire($resource, $method, $variables = [], $format)
    {
        $this->resource = $resource;
        $this->method = $method;
        $this->variables = $variables;
        $this->format = $format;

        $endPoint = $this->getEndPoint();

        $client = new Request($endPoint, $this->getRequestType(), $this->interface);

        $this->response = $client->get()->getResponse();
        
        return $this;
    }

    public function setInterface($interface)
    {
        $this->interface = $interface;

        return $this;
    }

    public function getInterface()
    {
        return $this->interface;
    }

    public function getEndPoint()
    {
        $rootPath = $this->getRootPath();
        $credentialString = $this->getCredentialQueryString();
        $queryString = $this->getQueryString();
        
        return "{$rootPath}/{$this->resource}/{$this->method}.{$this->format}?{$credentialString}&{$queryString}";
    }

    public function getJson()
    {
    	return json_decode($this->response);
    }

    public function toJson()
    {
        return $this->getJson();
    }

    public function getArray()
    {
    	return json_decode($this->response, true);
    }

    public function toArray()
    {
        return $this->getArray();
    }

    public function getQueryString()
    {
        $queryStringArray = [];
        foreach ($this->variables as $key => $value) {
            $queryStringArray[] = "${key}=${value}";
        }
        return implode("&", $queryStringArray);
    }

}
