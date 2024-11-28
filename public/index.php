<?php
// Copyright 2021 The dotcoo <dotcoo@163.com>. All rights reserved.

namespace zay;

date_default_timezone_set('PRC');

require_once __DIR__ . '/../vendor/autoload.php';

$app = App::getInstance();

// $start = microtime(true);
$app->init('index', 'admin');
// $time1 = microtime(true);
$app->start();
// $time2 = microtime(true);
// var_dump('APP_ENGINE', APP_ENGINE);
// var_dump('load time', $time1 - $start);
// var_dump('run time', $time2 - $start);
// var_dump('run time', $time2 - $time1);
