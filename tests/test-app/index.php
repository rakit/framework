<?php

require("../../vendor/autoload.php");

$app = new Rakit\Framework\App('testApp');

$app->middleware('yell', function($req, $res, $next) {
    
    return strtoupper($next());

});

$app->get("/", function() {
    
    return "Hello World!";

})->middleware('yell');

$app->run();