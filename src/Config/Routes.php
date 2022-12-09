<?php

namespace Daycry\CronJob\Config;

$routes->group('cronjob', ['namespace' => 'Daycry\CronJob\Controllers'], static function ($routes) {
    $routes->get('', 'Login');
});
