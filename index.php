<?php

require __DIR__.'/vendor/autoload.php';

use Nexa\Core\Application;

$app = new Application(__DIR__);

$app->run();