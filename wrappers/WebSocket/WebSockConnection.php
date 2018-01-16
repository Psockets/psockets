<?php
class WebSockConnection {
    private $con;
    private $is_authorized = false;

    public $frameDataLength = 0;
    public $multiFrameBuffer = '';
    public $frameMask = array();
    public $dataBuffer = '';
    public $dataType = '';
    public $lastFrameOpcode = 0;
    public $is_last_frame = true;
    public $protocol = '';
    public $endpoint = '';

    public function __construct($con) {
        $this->con = $con;
    }

    public function sendRaw($data) {
        $this->con->send($data);
    }

    public function send($data, $send_as_binary = false) {
        if (!empty($data)) {
            $message = new SendFrame($data);
            if ($send_as_binary) {
                $message->opcode = 0x2;
            }
            $msgFrame = $message->getFrame();
            $this->con->send($msgFrame);
        }
    }

    public function close() {
        if ($this->isAuthorized()) {
            $closingFrame = new SendFrame();
            $closingFrame->opcode = 0x08;
            $this->con->send($closingFrame->getFrame());
            $this->con->close();
        }
    }

    public function getConnection() {
        return $this->con;
    }

    public function isAuthorized() {
        return $this->is_authorized;
    }

    public function setAuthorized($state) {
        $this->is_authorized = $state;
    }

    public function recvFrameDataLength() {
        return strlen($this->dataBuffer);
    }

    public function isFrameComplete() {
        return $this->frameDataLength == $this->recvFrameDataLength();
    }

    public function wasLastFrameFinal() {
        return $this->is_last_frame;
    }
}
