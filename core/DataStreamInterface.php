<?php

interface DataStreamInterface {
    public function getChunk();
    public function advanceBy($bytes);
    public function eof();
}
