<?php

abstract class DataStream implements DataStreamInterface {
    protected $promise;

    public function setPromise($promise) {
        $this->promise = $promise;
    }

    public function getPromise() {
        return $this->promise;
    }

    abstract public function getChunk($chunkSize);
    abstract public function advanceBy($bytes);
    abstract public function eof();
}
