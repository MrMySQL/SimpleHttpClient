<?php

class HttpClientSocket extends HttpClientBasic implements HttpClientInterface {

    public static function isAvailable() {
        return function_exists('fsockopen');
    }

    //TODO manually follow redirects for fsockopen
    public function doRequest(HttpClientRequest $request) {
        $response = new HttpClientResponse();

        $query = $this->buildQuery($request);

        $body = $this->getSocketResponse($request, $query, $errno, $errstr, 0);

        if (strpos($body, "\r\n\r\n") !== false) {
            list($headers, $block) = explode("\r\n\r\n", $body, 2);
        } else {
            $headers = '';
            $block = strval($body);
        }

        $headers = explode(PHP_EOL, $headers);

        if (strpos($headers[0], 'HTTP/1.') === 0) {
            $httpCode = explode(' ', $headers[0]);
            $httpCode = $httpCode[1];
        } else {
            $httpCode = 0;
        }

        $response->setStatus((is_numeric($errno) AND $httpCode == 0) ? $errno : $httpCode);
        $response->setStatusMsg($errstr ? $errstr : 'Ok');
        $response->setHeaders($headers);
        $response->setBody(strval($block));

        return $response;
    }

    private function getSocketResponse(HttpClientRequest $request, $query, &$errno, &$errstr, $iteration) {
        $urlParsed = parse_url($request->getUrl());

        $socket = @fsockopen($urlParsed['host'], 80, $errno, $errstr);

        $body = '';

        if ($socket) {
            fwrite($socket, $query);
            while (!feof($socket)) {
                $line = fgets($socket);
                if (stristr($line, "location:") != "" AND self::getMaxRedirects() > $iteration) {
                    $redirect = trim(preg_replace("/location:/i", "", $line));
                    if (parse_url($redirect, PHP_URL_HOST)) {
                        $request->setUrl($redirect);
                    } else {
                        $urlRaw = $request->getUrl();
                        $request->setUrl(parse_url($urlRaw, PHP_URL_SCHEME) . '://' . parse_url($urlRaw, PHP_URL_HOST) . parse_url($urlRaw, PHP_URL_PORT) . $redirect);
                    }
                    $query = $this->buildQuery($request);
                    return $this->getSocketResponse($request, $query, $errno, $errstr, ++$iteration);
                }
                $body .= $line;
            }
            fclose($socket);
        }

        return $body;
    }

    private function buildQuery(HttpClientRequest $request) {
        $urlParsed = parse_url($request->getUrl());

        $query = "{$request->getMethod()} {$request->getPath()} {$request->getVersion()}\r\n" .
            "Host: {$urlParsed['host']}\r\n";

        if ($request->getMethod() == HttpClientRequest::REQUEST_METHOD_POST OR $request->getMethod() == HttpClientRequest::REQUEST_METHOD_PUT) {
            $query .= "Content-Type: application/x-www-form-urlencoded\r\n" .
                "Content-Length: " . strlen($request->getBody()) . "\r\n";
        }

        if ($request->getBasicAuthUsername() OR $request->getBasicAuthPassword()) {
            $query .= "Authorization: Basic " . base64_encode($request->getBasicAuthUsername() . ':' . $request->getBasicAuthPassword()) . "\r\n";
        }

        $headers = $this->processRequestHeaders($request->getHeaders());
        if (!empty($headers)) {
            $query .= join("\r\n", $headers) . "\r\n";
        }
        $headers = null;

        $query .= "Connection: close\r\n\r\n" . $request->getBody();

        return $query;
    }
}