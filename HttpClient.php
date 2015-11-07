<?php

class HttpClient {
    /**
     * @var HttpClientRequest
     */
    protected $request;
    /**
     * @var HttpClientResponse
     */
    protected $response;
    /**
     * @var HttpClientInterface
     */
    protected $client;


    public function __construct() {
        if (HttpClientCurl::isAvailable()) {
            $this->client = new HttpClientCurl();
        } elseif (HttpClientSocket::isAvailable()) {
            $this->client = new HttpClientSocket();
        } elseif (HttpClientFileContents::isAvailable()) {
            $this->client = new HttpClientFileContents();
        } else {
            throw new Exception('No transport libraries available on current server.');
        }
    }

    public function get($url) {
        $request = new HttpClientRequest();
        $request->setMethod(HttpClientRequest::REQUEST_METHOD_GET);
        $request->setParsedUrl($url);
        return $this->doRequest($request);
    }

    public function post($url, $body) {
        $request = new HttpClientRequest();
        $request->setMethod(HttpClientRequest::REQUEST_METHOD_POST);
        $request->setParsedUrl($url);
        $request->setBody($body);
        return $this->doRequest($request);
    }

    public function put($url, $body) {
        $request = new HttpClientRequest();
        $request->setMethod(HttpClientRequest::REQUEST_METHOD_PUT);
        $request->setParsedUrl($url);
        $request->setBody($body);
        return $this->doRequest($request);
    }

    public function delete($url) {
        $request = new HttpClientRequest();
        $request->setMethod(HttpClientRequest::REQUEST_METHOD_DELETE);
        $request->setParsedUrl($url);
        return $this->doRequest($request);
    }

    public function doRequest($request) {
        $this->response = null;
        if ($this->isClientCapable($request)) {
            $isReady = $this->isRequestReady($request);
            if ($isReady) {
                $this->response = $this->client->doRequest($request);
            } else {
                throw new Exception("WARN: Request isn't ready");
            }
        } else {
            throw new Exception("WARN: HTTP Client isn't capable of performing this request");
        }
        return $this->response;
    }

    private function isRequestReady(HttpClientRequest $request) {
        $isReady = true;

        if (!$request->getUrl()) {
            $isReady = false;
        }
        switch ($request->getMethod()) {
            case HttpClientRequest::REQUEST_METHOD_GET:
            case HttpClientRequest::REQUEST_METHOD_DELETE:
                break;
            case HttpClientRequest::REQUEST_METHOD_POST:
            case HttpClientRequest::REQUEST_METHOD_PUT:
                if (!$request->getBody()) {
                    $isReady = false;
                }
                break;
            default:
                $isReady = false;
                break;
        }

        return $isReady;
    }

    protected function isClientCapable(HttpClientRequest $request) {
        $isCapable = $this->client->can($request->getMethod());
        return $isCapable;
    }
}