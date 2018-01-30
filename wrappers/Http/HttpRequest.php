<?php

class HttpRequest {
    private $httpVersion;
    private $method;
    private $headers;
    private $path;
    private $query;
    private $cookies;
    private $payload;// for post requests

    public function __construct($httpVersion = '1.0', $method = "GET", $headers = array(), $path = "/", $query = array(), $cookies = array(), $payload = "") {
        $this->httpVersion = $httpVersion;
        $this->method = $method;
        $this->headers = $headers;
        $this->path = $path;
        $this->query = $query;
        $this->cookies = $cookies;
        $this->payload = $payload;
    }

    public function getCookie($name) {
        return isset($this->cookies[$name]) ?? NULL;
    }

    public function getHeader($name) {
        return isset($this->headers[$name]) ?? NULL;
    }

    public function getHttpVersion() { return $this->httpVersion; }
    public function getMethod() { return $this->method; }
    public function getHeaders() { return $this->headers; }
    public function getPath() { return $this->path; }
    public function getQuery() { return $this->query; }
    public function getPayload() { return $this->payload; }
}
