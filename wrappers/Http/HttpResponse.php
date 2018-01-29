<?php

class HttpResponse {
    private $httpVersion;
    private $body;
    private $request;

    public function __construct($request, $body) {
        $this->request = $request;
        $this->body = $body;
        $this->httpVersion = $request->getHttpVersion();;
    }

    public function __toString() {
        $contentLength = strlen($this->body);
        $headers = array(
            "HTTP/$this->httpVersion 200 OK",
            "Server: psockets",
            "Content-Length: $contentLength"
        );
        return implode("\r\n", $headers) . "\r\n\r\n" . $this->body;
    }
}
