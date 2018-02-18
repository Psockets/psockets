<?php

class HttpStatusMessages {
    const OK = "OK";
    const NOT_FOUND = "Not Found";
    const INTERNAL_ERROR = "Internal Server Error";
}

class HttpResponse {
    private $con;
    private $httpVersion;
    private $request;
    private $headers;
    private $statusCode;
    private $statusMessage;
    private $isKeepAlive;

    public function __construct($con, $request) {
        $this->con = $con;
        $this->request = $request;
        $this->headers = array();
        $this->httpVersion = $request->getHttpVersion();
        $this->isKeepAlive = false;
        $this->statusCode = 200;
        $this->statusMessage;

        $this->setHeader("Server", "psockets");
        if (strtolower($request->getHeader("connection")) == "keep-alive") {
            $this->addKeepAliveHeaders();
            $this->isKeepAlive = true;
        }
    }

    public function write($body) {
        if (!is_resource($body)) {
            $this->setHeader("Content-Length", strlen($body));
        }

        $this->con->send($this->getHeaderString());

        if ($this->isKeepAlive) {
            return $this->con->send($body);
        } else {
            $con = $this->con;
            return $this->con->send($body)->finally(function () use ($con) {
                $con->close();
            });
        }

    }

    public function setContentLength($len) {
        $this->setHeader("Content-Length", $len);
    }

    public function isKeepAlive() {
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

    public function getHeaderString() {
        return $this->getStatusLine() . "\r\n" . implode("\r\n", $this->headers) . "\r\n\r\n";
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
