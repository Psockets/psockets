<?php

class Http extends Wrapper {
    private $buffers;

    public function init() {
        $this->buffers = array();
    }

    public function onConnect($con) {
        $this->buffers[$con->id] = '';
    }

    public function onDisconnect($con) {
        $this->buffers[$con->id] = null;// Free up the memory, cause a simple unset will not and we will have a memory leak
        unset($this->buffers[$con->id]);
    }

    public function onData($con, $data) {
        $buffer = $this->buffers[$con->id];
        $buffer .= $data;

        if (strpos($buffer, "\r\n\r\n") !== false) {
            $this->buffers[$con->id] = '';

            $lines = explode("\r\n", $buffer);
            array_pop($lines);
            $requestLine = array_shift($lines);

            if (preg_match('/^(\w+)\s(.*?)\sHTTP\/([\d\.]+)$/', $requestLine, $matches)) {
                array_shift($matches);
                list($method, $target, $version) = $matches;
                $method = strtoupper($method);
                $allowed_methods = array('GET');

                if (!in_array($method, $allowed_methods)) {
                    $con->send($this->getHardcodedError501($version));
                    return;
                }

                $path = '/';
                $query = array();

                if (preg_match('/([^\?]*)(?:\?(.*))?/', $target, $matches)) {
                    $path = $matches[1];

                    if (isset($matches[2])) {
                        parse_str($matches[2], $query);
                    }
                }

                $headers = array();
                foreach ($lines as $line) {
                    $parts = explode(':', $line);
                    $header = strtolower(array_shift($parts));
                    $value = trim(implode(':', $parts));
                    $headers[$header] = $value;
                }

                $resp = $this->processRequest($version, $method, $path, $query, $headers);

                if ($resp) {
                    $con->send($resp);
                } else {
                    $con->send($this->getHardcodedError500($version));
                }

                $con->close();
            }
        } else {
            $this->buffers[$con->id] = $buffer;
        }
    }

    public function onStop() {}

    private function getResponse($version, $resp) {
        $contentLength = strlen($resp);
        return "HTTP/$version 200 OK\r\nContent-Length: $contentLength\r\n\r\n$resp";
    }

    private function getHardcodedReponse200($version) {
        return "HTTP/$version 200 OK\r\nContent-Length: 2\r\n\r\nHi";
    }

    private function getHardcodedError500($version) {
        return "HTTP/$version 500 Internal Server Error\r\nContent-Length: 5\r\n\r\nOops!";
    }

    private function getHardcodedError501($version) {
        return "HTTP/$version 501 Not Implemented\r\nContent-Length: 5\r\n\r\nOops!";
    }

    private function processRequest($httpVersion, $method, $path, $query, $headers) {
        if ($query) {
            //$resp = "Result is: " . ((int)$query['a'] + (int)$query['b']);
            $resp = (int)$query['a'] + (int)$query['b'];
            return $this->getResponse($httpVersion, $resp);
        }

        return $this->getHardcodedReponse200($httpVersion);
    }
}
