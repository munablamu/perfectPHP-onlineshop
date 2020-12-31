<?php

require '../bootstrap.php';
require '../config/WebApplication.php';

# PHP_TYPE is set in .htaccess
switch ( getenv('PHP_TYPE') ) {
  case 'module':
    $debug = true;
    break;
  default:
    $debug = false;
    break;
}

$app = new WebApplication($debug);
$app->run();
