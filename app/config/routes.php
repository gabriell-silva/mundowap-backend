<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    // Rotas agrupadas API
    $routes->scope('/api', function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);

        $builder->scope('/visits', function (RouteBuilder $builder) {
            $builder->post('/', ['controller' => 'Visits', 'action' => 'add']);
            $builder->put('/{id}', ['controller' => 'Visits', 'action' => 'edit'])->setPass(['id']);
            $builder->get('/{date}', ['controller' => 'Visits', 'action' => 'visitByDate'])->setPass(['date']);
        });

        $builder->scope('/workdays', function (RouteBuilder $builder) {
            $builder->get('/', ['controller' => 'Workdays', 'action' => 'index']);
            $builder->post('/', ['controller' => 'Workdays', 'action' => 'close']);
        });

        $builder->get('/csrf-token', ['controller' => 'App', 'action' => 'getCsrfToken']);
    });

    // Redirecionar todas as rotas não encontradas para o método do controlador
    $routes->fallbacks(DashedRoute::class);
};
