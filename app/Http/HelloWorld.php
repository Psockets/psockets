<?php

class HelloWorld extends HttpComponent {
    public static $PATH = '/';

    public function onRequest($con, $request) {
        $con->send(new HttpResponse("Hello World!"));
        $con->close();
    }
}
