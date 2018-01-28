<?php

class HttpResponse {
    public $version;
    public $body;

    public function __construct($body) {
        $this->body = $body;
        $this->version = '1.1';
    }

    public function __toString() {
        $contentLength = strlen($this->body);
        $headers = array(
            "HTTP/$this->version 200 OK",
            "Server: psockets",
            "Content-Length: $contentLength"
        );
        return implode("\r\n", $headers) . "\r\n\r\n" . $this->body;
    }
}
