<?php

class Maintenance extends HttpComponent {
    public static $PATH = '/maintenance';

    public function onRequest($con, $request, $response) {
        $response->write("<h1>This is a demo maintenance page</h1>")->then(function ($data) use ($con) {
            $con->close();
        });
    }
}
