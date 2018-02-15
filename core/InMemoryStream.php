<?php

class InMemoryStream extends DataStream {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function getChunk($chunkSize) {
        $chunk = substr($this->data, 0, $chunkSize);
        return $chunk;
    }

    public function advanceBy($bytes) {
        $this->data = substr($this->data, $bytes);
    }

    public function eof() {
        return !$this->data;
    }
}
