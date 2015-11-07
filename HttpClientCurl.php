<?php

class HttpClientCurl extends HttpClientBasic implements HttpClientInterface {
    /**
     * @var resource cURL
     */
    private $ch;

    public static function isAvailable() {
        return function_exists('curl_init');
    }

    public function doRequest(HttpClientRequest $request) {
        $this->ch = curl_init();

        curl_setopt_array($this->ch, array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::getMaxRedirects()
        ));

        if ($request->getBasicAuthUsername() OR $request->getBasicAuthPassword()) {
            curl_setopt_array($this->ch, array(
                CURLOPT_USERPWD => $request->getBasicAuthUsername() . ':' . $request->getBasicAuthPassword()
            ));
        }

        $response = NULL;
        switch ($request->getMethod()) {
            case HttpClientRequest::REQUEST_METHOD_GET:
                $this->doGet();
                break;
            case HttpClientRequest::REQUEST_METHOD_POST:
                $this->doPost($request);
                break;
            case HttpClientRequest::REQUEST_METHOD_PUT:
                $this->doPut($request);
                break;
            case HttpClientRequest::REQUEST_METHOD_DELETE:
                $this->doDelete();
                break;
        }

        // Convert to raw CURL headers and add to request
        $headers = $this->processRequestHeaders($request->getHeaders());
        if (!empty($headers)) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }

        $httpOutput = curl_exec($this->ch);
        $response = $this->parseResponse($httpOutput);

        curl_close($this->ch);
        return $response;
    }

    private function doGet() {
        curl_setopt_array($this->ch, array(
            CURLOPT_HTTPGET => true,
        ));
    }

    private function doPost(HttpClientRequest $request) {
        curl_setopt_array($this->ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request->getBody(),
        ));
    }

    private function doPut(HttpClientRequest $request) {
        curl_setopt_array($this->ch, array(
            CURLOPT_CUSTOMREQUEST => HttpClientRequest::REQUEST_METHOD_PUT,
            CURLOPT_POSTFIELDS => $request->getBody(),
        ));
    }

    private function doDelete() {
        curl_setopt_array($this->ch, array(
            CURLOPT_CUSTOMREQUEST => HttpClientRequest::REQUEST_METHOD_DELETE,
        ));
    }

    /**
     *    Parses the raw HTTP response and returns a response object
     **/
    private function parseResponse($output) {
        $response = new HttpClientResponse();
        $response->setStatus(curl_getinfo($this->ch, CURLINFO_HTTP_CODE));

        if ($output) {
            $response->setBody($output);
        }

        if ($error = curl_error($this->ch)) {
            $response->setStatusMsg($error);
        }

        return $response;
    }
}