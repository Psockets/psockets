<?php

class HttpStatusMessages {
    const OK = "OK";
    const NOT_FOUND = "Not Found";
    const INTERNAL_ERROR = "Internal Server Error";
}

class HttpResponse {
    private $httpVersion;
    private $body;
    private $request;
    private $headers;
    private $statusCode;
    private $statusMessage;
    private $isKeepAlive;

    public function __construct($request, $body) {
        $this->request = $request;
        $this->body = $body;
        $this->headers = array();
        $this->httpVersion = $request->getHttpVersion();;
        $this->isKeepAlive = false;
        $this->statusCode = 200;
        $this->statusMessage;

        $this->setHeader("Server", "psockets");
        $this->setHeader("Content-Length", strlen($body));
    }

    public function setKeepAlive($state) {
        $this->isKeepAlive = $state;
    }

    public function getKeepAlive() {
        return $this->isKeepAlive;
    }

    public function setHeader($name, $value) {
        $this->headers[] = $name . ": " . $value;
    }

    public function setStatusCode($code, $message = NULL) {
        $this->statusCode = $code;

        if ($message) {
            $this->statusMessage = $message;
        }
    }

    public function __toString() {
        if ($this->isKeepAlive) {
            $this->addKeepAliveHeaders();
        }

        $statusLine = $this->getStatusLine();

        return $statusLine . "\r\n" . implode("\r\n", $this->headers) . "\r\n\r\n" . $this->body;
    }

    private function addKeepAliveHeaders() {
        $this->setHeader("Connection", "Keep-Alive");
        $this->setHeader("Keep-Alive", "timeout=15, max=1000");
    }

    private function getStatusLine() {
        $statusLine = "HTTP/$this->httpVersion $this->statusCode ";

        if ($this->statusMessage) {
            return $statusLine . $this->statusMessage;
        } else {
            switch ($this->statusCode) {
            case 404:
                return $statusLine . HttpStatusMessages::NOT_FOUND;
            case 500:
                return $statusLine . HttpStatusMessages::INTERNAL_ERROR;
            default:
                return $statusLine . HttpStatusMessages::OK;
            }
        }
    }
}
