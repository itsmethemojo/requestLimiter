<?php

require '../vendor/autoload.php';

use Itsmethemojo\RequestLimiter;

$req = new RequestLimiter();

if($req->isLimitReached()){
    echo "you are banned";
    exit;
}

echo "everything ok";
