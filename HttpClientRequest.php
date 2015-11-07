<?php

class HttpClientRequest {
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_METHOD_DELETE = 'DELETE';

    protected $method;
    protected $path;

    protected $version = 'HTTP/1.1';

    protected $headers;
    protected $body;

    /**
     * @var array segmented url
     */
    protected $parsedUrl;
    protected $rawUrl;

    private $basicAuthUsername = '';
    private $basicAuthPassword = '';

    /**
     * @return string
     */
    public function getBasicAuthUsername() {
        return $this->basicAuthUsername;
    }

    /**
     * @param string $basicAuthUsername
     */
    public function setBasicAuthUsername($basicAuthUsername) {
        $this->basicAuthUsername = $basicAuthUsername;
    }

    /**
     * @return string
     */
    public function getBasicAuthPassword() {
        return $this->basicAuthPassword;
    }

    /**
     * @param string $basicAuthPassword
     */
    public function setBasicAuthPassword($basicAuthPassword) {
        $this->basicAuthPassword = $basicAuthPassword;
    }

    public function __construct($url = NULL) {
        $this->headers = array();

        $this->setUrl($url);
    }

    public function setParsedUrl($parsedUrl) {
        $this->rawUrl = $parsedUrl;
        $this->parsedUrl = $this->segmentUrl($parsedUrl);
        $this->setPath($this->parsedUrl['path']);
    }

    public function getUrl() {
        return $this->rawUrl;
    }


    public function setPath($path) {
        $this->path = ($path) ? $path : '/';
    }

    /**
     * @return string url path
     */
    public function getPath() {
        return $this->path;
    }

    public function setMethod($method) {
        $method = strtoupper($method);
        if ($this->isValidMethod($method)) {
            $this->method = $method;
        }
    }

    public function getMethod() {
        return $this->method;
    }

    public function setVersion($version) {
        $version = $this->normaliseVersion($version);
        if (!is_null($version)) {
            $this->version = 'HTTP/' . $version;
        }
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getHeader($name) {
        if (!empty($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    public function addHeader($name, $value) {
        $name = $this->normaliseHeader($name);
        $this->headers[$name] = $value;
    }

    public function setHeaders($headers) {
        foreach ($headers as $key => $value) {
            $key = $this->normaliseHeader($key);
            $this->headers[$key] = $value;
        }
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        if (is_array($body)) {
            $tmp = array();
            foreach ($body as $name => $val) {
                $tmp[] = $name . '=' . $val;
            }
            $this->body = implode('&', $tmp);
        } else {
            $this->body = $body;
        }
    }


    protected function normaliseHeader($header) {
        $name = str_replace('-', ' ', $header);
        $name = ucwords(strtolower($name));
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    protected function segmentUrl($url) {
        $segments = array();
        if (preg_match('/^(\w+):\/\/([^\/]+)(.+)$/', $url, $matches)) {
            $segments['protocol'] = $matches[1];
            $segments['path'] = $matches[3];

            $domain = $matches[2];
            // TODO: Check for username/passwords in the URL
            if (preg_match('/^([^:]+):?(\d*)/', $domain, $matches)) {
                $segments['domain'] = $matches[1];
                if (!empty($matches[2])) {
                    $segments['port'] = $matches[2];
                }
            }
        }
        return $segments;
    }

    protected function normaliseVersion($version) {
        if ($version == '1.1' || $version = 1.1) {
            return '1.1';
        } elseif ($version == '1.0' || $version == 1.0 || $version == 1) {
            return '1.0';
        }
        return null;
    }

    protected function isValidMethod($method) {
        switch ($method) {
            case self::REQUEST_METHOD_GET:
            case self::REQUEST_METHOD_POST:
            case self::REQUEST_METHOD_PUT:
            case self::REQUEST_METHOD_DELETE:
                $isValid = true;
                break;
            default:
                $isValid = false;
                break;

        }
        return $isValid;
    }

    public function setUrl($url) {
        if (!is_null($url)) {
            $this->setParsedUrl($url);
        }
    }
}