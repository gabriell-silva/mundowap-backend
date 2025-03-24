<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/api', function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);

        $builder->scope('/visits', function (RouteBuilder $builder) {
            $builder->get('/{date}', ['controller' => 'Visits', 'action' => 'visitByDate'])->setPass(['date']);
            $builder->put('/{id}', ['controller' => 'Visits', 'action' => 'edit'])->setPass(['id']);
            $builder->post('/', ['controller' => 'Visits', 'action' => 'add']);
        });

        $builder->scope('/workdays', function (RouteBuilder $builder) {
            $builder->get('/', ['controller' => 'Workdays', 'action' => 'index']);
            $builder->post('/{date}', ['controller' => 'Workdays', 'action' => 'close'])->setPass(['date']);
        });

        $builder->get('/csrf-token', ['controller' => 'App', 'action' => 'getCsrfToken']);
    });

    $routes->fallbacks(DashedRoute::class);
};
