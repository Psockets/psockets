<?php

class HttpStatusMessages {
    const OK = "OK";
    const NOT_FOUND = "Not Found";
    const INTERNAL_ERROR = "Internal Server Error";
}

class HttpResponse extends DataStream {
    private $httpVersion;
    private $body;
    private $request;
    private $headers;
    private $statusCode;
    private $statusMessage;
    private $isKeepAlive;
    private $hasContentLength;
    private $data;

    public function __construct($request, $body) {
        $this->request = $request;
        $this->headers = array();
        $this->httpVersion = $request->getHttpVersion();;
        $this->isKeepAlive = false;
        $this->statusCode = 200;
        $this->statusMessage;
        $this->data = false;

        $this->setHeader("Server", "psockets");

        $this->body = $body;

        if (!is_resource($body)) {
            $this->setHeader("Content-Length", strlen($body));
            $this->hasContentLength = true;
        } else {
            $this->hasContentLength = false;
        }
    }

    //DataStream implementation
    public function getChunk($chunkSize) {
        if ($this->hasContentLength) {
            $chunk = substr($this->data, 0, $chunkSize);
            return $chunk;
        }

        return "";
    }

    public function advanceBy($bytes) {
        $this->data = substr($this->data, $bytes);
    }

    public function eof() {
        return !$this->data;
    }
    //End DataStream implementation

    public function setContentLength($len) {
        $this->setHeader("Content-Length", $len);
        $this->hasContentLength = true;
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

    public function generateResponse() {
        if ($this->isKeepAlive) {
            $this->addKeepAliveHeaders();
        }

        $statusLine = $this->getStatusLine();

        $this->data = $statusLine . "\r\n" . implode("\r\n", $this->headers) . "\r\n\r\n" . $this->body;
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
