<?php

class HelloWorld extends HttpComponent {
    public static $PATH = '/';

    public function onRequest($con, $request, $response) {
        if ($request->getPath() == '/') {
            $response->write("Hello World!");

            return true;
        }

        return false;
    }
}
