<?php

interface DataStream {
    public function getChunk();
    public function advanceBy($bytes);
    public function eof();
}
