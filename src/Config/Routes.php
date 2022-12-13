<?php

namespace Daycry\CronJob\Config;

$routes->group('cronjob', ['namespace' => 'Daycry\CronJob\Controllers'], static function ($routes) {
    $routes->get('', 'Login::index');
    $routes->get('login/logout', 'Login::logout');
    $routes->post('login/validation', 'Login::validation');
    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('job/(:segment)/logs', 'Job::index/$1');
});
