<?php

class HelloWorld extends HttpComponent {
    public static $PATH = '/';

    public function onRequest($con, $request) {
        if ($request->getPath() == '/') {
            $con->send(new HttpResponse($request, "Hello World!"));
            $con->close();
            return true;
        }

        return false;
    }
}
