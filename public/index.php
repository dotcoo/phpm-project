<?php
// Copyright 2021 The dotcoo <dotcoo@163.com>. All rights reserved.

namespace net\phpm\framework;

date_default_timezone_set('PRC');

require_once __DIR__ . '/../vendor/autoload.php';

$app = App::getInstance();

$app->init('index', 'admin');
$app->start();
