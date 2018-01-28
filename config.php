<?php
$server_config = array(
    65000 => array(
        'WebSocket' => array(
            'hosts' => array(
                'localhost' => array('WebChat', 'SimpleEcho'),
            )
        ),
        'ssl' => array(
            'cert_file' => '',
            'privkey_file' => '',
            'passphrase' => ''
        )
    ),
    65001 => array(
        'RawTcp' => array()
    ),
    65002 => array(
        'Http' => array(
            'hosts' => array(
                'localhost' => array('HelloWorld'),
            )
        ),
        'ssl' => array(
            'cert_file' => '',
            'privkey_file' => '',
            'passphrase' => ''
        )
    )
);
