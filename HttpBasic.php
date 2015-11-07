<?php

class HttpBasic {

    const MAX_REDIRECTS = 5;

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