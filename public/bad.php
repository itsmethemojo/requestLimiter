<?php

require '../vendor/autoload.php';

use Itsmethemojo\RequestLimiter;

$req = new RequestLimiter();

$req->countLimtedAction(
    'scraper',
    60,
    2,
    120
);

if($req->isLimitReached()){
    echo "you are banned";
    exit;
}

echo "everything ok";
