<?php

abstract class DataStream implements DataStreamInterface {
    protected $promise;
    protected $nextStream = NULL;

    public function setPromise($promise) {
        $this->promise = $promise;
    }

    public function getPromise() {
        return $this->promise;
    }

    public function setNext($stream) {
        $this->nextStream = $stream;
    }

    public function getNext() {
        return $this->nextStream;
    }

    abstract public function getChunk($chunkSize);
    abstract public function advanceBy($bytes);
    abstract public function eof();
}
