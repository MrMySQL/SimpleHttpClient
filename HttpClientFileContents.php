<?php

class HttpClientFileContents extends HttpClientBasic implements HttpClientInterface {

    public static function isAvailable() {
        return ini_get('allow_url_fopen');
    }

    public function doRequest(HttpClientRequest $request) {
        switch ($request->getMethod()) {
            case HttpClientRequest::REQUEST_METHOD_GET:
                $contextArray = $this->doGet();
                break;
            case HttpClientRequest::REQUEST_METHOD_POST:
                $contextArray = $this->doPost($request);
                break;
            case HttpClientRequest::REQUEST_METHOD_PUT:
                $contextArray = $this->doPut($request);
                break;
            case HttpClientRequest::REQUEST_METHOD_DELETE:
                $contextArray = $this->doDelete();
                break;
            default:
                $contextArray = array();
        }

        if (!isset($contextArray['http'])) {
            $contextArray['http'] = array();
        }

        $contextArray['http']['max_redirects'] = self::getMaxRedirects();

        if ($request->getBasicAuthUsername() OR $request->getBasicAuthPassword()) {
            $string = "Authorization: Basic " . base64_encode($request->getBasicAuthUsername() . ':' . $request->getBasicAuthPassword()) . PHP_EOL;
            $contextArray['http']['header'] = isset($contextArray['http']['header']) ? $contextArray['http']['header'] . $string : $string;
        }

        $headers = $this->processRequestHeaders($request->getHeaders());
        if (!empty($headers)) {
            $string = join("\r\n", $headers) . "\r\n";
            $contextArray['http']['header'] = isset($contextArray['http']['header']) ? $contextArray['http']['header'] . $string : $string;
        }

        $context = empty($contextArray) ? null : stream_context_create($contextArray);

        $body = @file_get_contents($request->getUrl(), false, $context);

        $response = $this->processRequest($request->getUrl(), $body);

        return $response;
    }

    protected function doGet() {
        return array();
    }

    protected function doPost(HttpClientRequest $request) {
        return array(
            'http' => array(
                'method' => HttpClientRequest::REQUEST_METHOD_POST,
                'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                'content' => $request->getBody()
            ),
        );
    }

    protected function doPut(HttpClientRequest $request) {
        return array(
            'http' => array(
                'method' => HttpClientRequest::REQUEST_METHOD_PUT,
                'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                'content' => $request->getBody()
            ),
        );
    }

    protected function doDelete() {
        return array(
            'http' => array(
                'method' => HttpClientRequest::REQUEST_METHOD_DELETE
            ),
        );
    }

    private function processRequest($url, $body) {
        $response = new HttpClientResponse();

        $response->setBody(strval($body));

        // Check whether file_get_contents threw an error
        $error = error_get_last();

        if (strpos($error['message'], $url) !== false) {
            $msg = $error['message'];

            //echo "file get contents returned an error.\n";
            $httpPattern = '/HTTP\/(\d\.\d) (\d*) (.*)$/';
            if (preg_match($httpPattern, $msg, $matches)) {
                //echo 'HTTP/', $matches[1], ' ', $matches[2], ' ', $matches[3], "\n";

                $response->setStatus($matches[2]);
                $response->setStatusMsg(trim($matches[3]));
                $response->setVersion($matches[1]);
            } elseif (preg_match('/stream: (.*)$/', $msg, $matches)) {
                $response->setStatus(0);
                $response->setStatusMsg($matches[1]);
            } else {
                throw new Exception("WARN: No valid HTTP reply from file_get_contents:\n" . $msg . "\n");
            }
        } else {
            $response->setStatus(200);
            $response->setStatusMsg('Ok');
        }
        return $response;
    }
}