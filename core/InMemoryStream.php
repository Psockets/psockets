<?php

class InMemoryStream implements DataStream {
    private $chunkSize;
    private $data;

    public function __construct($data, $chunkSize = 8192) {
        $this->chunkSize = $chunkSize;
        $this->data = $data;
    }

    public function getChunk() {
        $chunk = substr($this->data, 0, $this->chunkSize);
        return $chunk;
    }

    public function advanceBy($bytes) {
        $this->data = substr($this->data, $bytes);
    }

    public function eof() {
        return !$this->data;
    }
}
