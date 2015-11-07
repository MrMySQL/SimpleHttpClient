<?php

class HttpClientBasic {

    private $maxRedirects = 5;

    /**
     * @return int
     */
    public function getMaxRedirects() {
        return $this->maxRedirects;
    }

    /**
     * @param int $maxRedirects
     */
    public function setMaxRedirects($maxRedirects) {
        $this->maxRedirects = $maxRedirects;
    }

    public static function can($requestMethod) {
        switch ($requestMethod) {
            case HttpClientRequest::REQUEST_METHOD_GET:
            case HttpClientRequest::REQUEST_METHOD_POST:
            case HttpClientRequest::REQUEST_METHOD_PUT:
            case HttpClientRequest::REQUEST_METHOD_DELETE:
                $hasFeature = true;
                break;
            default:
                $hasFeature = false;
                break;
        }
        return $hasFeature;
    }

    protected function processRequestHeaders(array $headers) {
        $rawHeaders = array();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $rawHeaders[] = $key . ': ' . $value;
            }
        }
        return $rawHeaders;
    }
}