<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
// Rutas Públicas de Autenticación
$routes->post('auth/register', 'AuthController::register');
$routes->post('auth/login', 'AuthController::login');

// Consulta pública de vinilos
$routes->get('vinilos', 'ViniloController::index');
$routes->get('vinilos/(:num)', 'ViniloController::show/$1');

// Rutas Protegidas por JWT
$routes->group('', ['filter' => 'jwt'], function ($routes) {
    $routes->post('vinilos', 'ViniloController::create');
    $routes->delete('vinilos/(:num)', 'ViniloController::delete/$1');
});
