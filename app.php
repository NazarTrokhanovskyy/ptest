<?php

use App\Application;
use App\Services\BinInfo;
use App\Services\ExchangeRatesApi;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once './vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(__DIR__.'/logs/app.log', Logger::DEBUG));

$app = new Application($logger, new ExchangeRatesApi(), new BinInfo($logger));

$inputFile = $argv[1] ?? null;
$app->process($inputFile);
