<?php

interface HttpClientInterface {
    public static function can($requestMethod);

    public static function isAvailable();

    /**
     * @param HttpClientRequest $requests
     * @return HttpClientResponse
     */
    public function doRequest(HttpClientRequest $requests);
}