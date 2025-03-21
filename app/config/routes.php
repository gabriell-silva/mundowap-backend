<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    // Rotas agrupadas API
    $routes->scope('/api', function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);

        $builder->scope('/visits', function (RouteBuilder $builder) {
            $builder->post('/', ['controller' => 'Visits', 'action' => 'store']);
            $builder->put('/{id}', ['controller' => 'Visits', 'action' => 'update'])->setPass(['id']);
            $builder->get('/{date}', ['controller' => 'Visits', 'action' => 'visitByDate'])->setPass(['date']);
        });

        $builder->scope('/workdays', function (RouteBuilder $builder) {
            $builder->get('/', ['controller' => 'Workday', 'action' => 'index']);
            $builder->post('/', ['controller' => 'Workday', 'action' => 'close']);
        });

        $builder->get('/csrf-token', ['controller' => 'App', 'action' => 'getCsrfToken']);
    });

    // Redirecionar todas as rotas não encontradas para o método do controlador
    $routes->fallbacks(DashedRoute::class);
};
