<?php
class Request {
    public $con;
    public $data;

    public function __construct($con, $data){
        $this->con = $con;
        $this->data = $data;
    }

    public function getConnection() {
        return $this->con;
    }

    public function getData() {
        return $this->data;
    }
}
