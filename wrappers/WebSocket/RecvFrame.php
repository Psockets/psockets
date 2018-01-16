<?php
class RecvFrame {
    public $FIN = false;
    public $RSV1 = false;
    public $RSV2 = false;
    public $RSV3 = false;
    public $opcode = 0;
    public $mask = false;
    public $payload_len = 0;
    public $mask_bytes = array();
    public $data_buffer = array();
    public $dataFirstByteIndex = 0;
    private $is_valid_frame = true;
    private $parsed_data = '';

    public static function unmaskData($mask, $data) {
        $bytes = unpack('C*byte', $data);
        if (!empty($bytes)) {
            $i = 1;
            $x = 0;
            $data_buffer = array();
            while(isset($bytes['byte'.$i])) {
                $data_buffer[] = ($bytes['byte'.$i] ^ $mask[$x%4]);
                $i++;
                $x++;
            }
            return call_user_func_array("pack", array_merge(array('C*'), $data_buffer));
        }
        return '';
    }

    public function __construct($data) {
        $bytes = unpack('C*byte', $data);
        if (empty($bytes)) {
            $this->is_valid_frame = false;
            return;
        }

        $this->FIN = (bool)($bytes['byte1'] & 0x80);
        $this->RSV1 = (bool)($bytes['byte1'] & 0x40);
        $this->RSV2 = (bool)($bytes['byte1'] & 0x20);
        $this->RSV3 = (bool)($bytes['byte1'] & 0x10);
        $this->opcode = $bytes['byte1'] & 0x0f;
        $this->mask = (bool)($bytes['byte2'] & 0x80);
        $this->payload_len = ($bytes['byte2'] & 0x7f);

        $payloadLenOverride = $this->payload_len;

        $i = 3;
        switch ($this->payload_len) {
        case 126:
            $payloadLenOverride = $bytes['byte3'] << 8;
            $payloadLenOverride = $payloadLenOverride | $bytes['byte4'];
            $i=5;
            if ($this->mask) {
                $this->mask_bytes = array(
                    $bytes['byte5'],
                    $bytes['byte6'],
                    $bytes['byte7'],
                    $bytes['byte8']
                );
                $i = 9;
            }
            break;
        case 127:
            $payloadLenOverride = $bytes['byte3'] << 8;
            $payloadLenOverride = $payloadLenOverride | $bytes['byte4'];
            for ($t=5;$t<11;$t++) {
                $payloadLenOverride << 8;
                $payloadLenOverride = $payloadLenOverride | $bytes['byte'.$t];
            }
            $i = 11;
            if ($this->mask) {
                $this->mask_bytes = array(
                    $bytes['byte11'],
                    $bytes['byte12'],
                    $bytes['byte13'],
                    $bytes['byte14']
                );
                $i = 15;
            }
            break;
        default:
            $i = 3;
            if ($this->mask) {
                $this->mask_bytes = array(
                    $bytes['byte3'],
                    $bytes['byte4'],
                    $bytes['byte5'],
                    $bytes['byte6']
                );
                $i = 7;
            }
        }

        $this->payload_len = $payloadLenOverride;
        $this->dataFirstByteIndex = $i;
        $x = 0;
        while(isset($bytes['byte'.$i])) {
            if ($this->mask) {
                $this->data_buffer[] = ($bytes['byte'.$i] ^ $this->mask_bytes[$x%4]);
            } else {
                $this->data_buffer[] = $bytes['byte'.$i];
            }
            $i++;
            $x++;
        }
    }

    public function getData() {
        return call_user_func_array("pack", array_merge(array('C*'), $this->data_buffer));
    }

    public function isValid() {
        return $this->is_valid_frame;
    }
}
