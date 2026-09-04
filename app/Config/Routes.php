<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- Rutas Web (Renderizan la Interfaz HTML) ---
$routes->get('/',               'HomeController::login');
$routes->get('login',           'HomeController::login');
$routes->get('register',        'HomeController::register');
$routes->get('dashboard',       'HomeController::index');

$routes->get('vinilos/view',    'HomeController::vinilosView');
$routes->get('usuarios/view',   'HomeController::usuariosView');
$routes->get('roles/view',      'HomeController::rolesView');

// --- Rutas Públicas de Autenticación & CORS Preflight ---
$routes->options('auth/login', static function () {
    $response = response();
    $response->setHeader('Access-Control-Allow-Origin', '*');
    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
    return $response->setStatusCode(200);
});
$routes->post('auth/register', 'AuthController::register');
$routes->post('auth/login',    'AuthController::login');

// --- Consulta Pública de Vinilos ---
$routes->get('vinilos',        'ViniloController::index');
$routes->get('vinilos/(:num)', 'ViniloController::show/$1');

// --- Rutas Protegidas por JWT + Permisos Dinámicos ---
$routes->post('vinilos',                'ViniloController::create',          ['filter' => 'permission:vinilos.create']);
$routes->put('vinilos/(:num)',          'ViniloController::update/$1',       ['filter' => 'permission:vinilos.edit']);
$routes->delete('vinilos/(:num)',       'ViniloController::delete/$1',       ['filter' => 'permission:vinilos.delete']);

$routes->post('vinilos/(:num)/fotos',   'ViniloController::agregarFotos/$1', ['filter' => 'permission:vinilos.create']);
$routes->delete('vinilos/fotos/(:num)', 'ViniloController::eliminarFoto/$1', ['filter' => 'permission:vinilos.delete']);

// --- Rutas de Perfil Propio ---
$routes->get('perfil',                  'ProfileController::index',           ['filter' => 'permission:perfil.view']);
$routes->put('perfil',                  'ProfileController::update',          ['filter' => 'permission:perfil.edit']);
$routes->put('perfil/cambiar-password', 'ProfileController::cambiarPassword', ['filter' => 'permission:perfil.edit']);

// --- Rutas de Gestión de Usuarios (API) ---
$routes->group('usuarios', ['filter' => 'permission:usuarios.view'], static function ($routes) {
    $routes->get('/',            'UserController::index');
    $routes->get('(:num)',       'UserController::show/$1');
    $routes->post('/',           'UserController::create',     ['filter' => 'permission:usuarios.create']);
    $routes->put('(:num)',       'UserController::update/$1',  ['filter' => 'permission:usuarios.edit']);
    $routes->delete('(:num)',    'UserController::delete/$1',  ['filter' => 'permission:usuarios.delete']);
    $routes->put('(:num)/rol',   'UserController::assignRole/$1', ['filter' => 'permission:usuarios.edit']);
});

// --- Rutas de Gestión de Roles y Permisos (API) ---
$routes->group('roles', ['filter' => 'permission:roles.view'], static function ($routes) {
    $routes->get('/',                 'RoleController::index');
    $routes->get('(:num)',            'RoleController::show/$1');
    $routes->post('/',                'RoleController::create',              ['filter' => 'permission:roles.create']);
    $routes->put('(:num)',            'RoleController::update/$1',           ['filter' => 'permission:roles.edit']);
    $routes->delete('(:num)',         'RoleController::delete/$1',           ['filter' => 'permission:roles.delete']);
    $routes->post('(:num)/permisos',  'RoleController::syncPermissions/$1',  ['filter' => 'permission:roles.edit']);
});
