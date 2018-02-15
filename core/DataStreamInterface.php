<?php

interface DataStreamInterface {
    public function getChunk($chunkSize);
    public function advanceBy($bytes);
    public function eof();
}
