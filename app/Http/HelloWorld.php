<?php

class HelloWorld extends HttpComponent {
    public static $PATH = '/';

    public function onRequest($con, $request, $response) {
        if ($request->getPath() == '/') {
            $response->write("Hello World!")->then(function ($data) use ($con) {
                $con->close();
            });

            return true;
        }

        return false;
    }
}
