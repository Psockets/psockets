<?php

class HttpRequest {
    public $type;
    public $headers;
    public $path;
    public $query;
    public $cookies;
    public $payload;// for post requests

    public function __construct($type = "GET", $headers = array(), $path = "/", $query = array(), $cookies = array(), $payload = "") {
        $this->type = $type;
        $this->headers = $headers;
        $this->path = $path;
        $this->query = $query;
        $this->cookies = $cookies;
        $this->payload = $payload;
    }

    public function getCookie($name) {
    }
}
