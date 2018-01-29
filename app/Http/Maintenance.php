<?php

class Maintenance extends HttpComponent {
    public static $PATH = '/maintenance';

    public function onRequest($con, $request) {
        $con->send(new HttpResponse($request, "<h1>This is a demo maintenance page</h1>"));
        $con->close();
    }
}
