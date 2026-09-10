<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ============================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================

// Página principal y autenticación
$routes->get('/', 'HomeController::login');
$routes->get('login', 'HomeController::login');
$routes->get('register', 'HomeController::register');

// Auth API (CORS)
$routes->options('auth/login', static function () {
    $response = response();
    $response->setHeader('Access-Control-Allow-Origin', '*');
    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
    return $response->setStatusCode(200);
});

$routes->post('auth/register', 'AuthController::register');
$routes->post('auth/login', 'AuthController::login');
$routes->get('auth/verify', 'AuthController::verify');
$routes->post('auth/resend-verification', 'AuthController::resendVerification');
$routes->post('auth/forgot-password', 'AuthController::forgotPassword');
$routes->get('auth/reset-password', 'AuthController::verifyResetToken');
$routes->post('auth/reset-password', 'AuthController::resetPassword');
$routes->post('auth/logout', 'AuthController::logout');

// ============================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ============================================

// --- VISTAS HTML (Páginas principales) ---
$routes->get('dashboard', 'HomeController::index');
$routes->get('vinilos', 'HomeController::vinilosView');        // ← Vista de vinilos
$routes->get('usuarios', 'HomeController::usuariosView');       // ← Vista de usuarios
$routes->get('roles', 'HomeController::rolesView');             // ← Vista de roles
$routes->get('perfil', 'ProfileController::index');             // ← Vista de perfil

// --- API: VINILOS (JSON) - CON PROTECCIÓN JWT ---
$routes->group('api/vinilos', ['filter' => 'jwt'], function ($routes) {
    $routes->get('', 'ViniloController::index');
    $routes->get('(:num)', 'ViniloController::show/$1');
    $routes->post('', 'ViniloController::create', ['filter' => 'permission:vinilos.create']);
    $routes->put('(:num)', 'ViniloController::update/$1', ['filter' => 'permission:vinilos.edit']);
    $routes->delete('(:num)', 'ViniloController::delete/$1', ['filter' => 'permission:vinilos.delete']);

    // Rutas para fotos
    $routes->post('(:num)/fotos', 'ViniloController::agregarFotos/$1', ['filter' => 'permission:vinilos.create']);
    $routes->delete('fotos/(:num)', 'ViniloController::eliminarFoto/$1', ['filter' => 'permission:vinilos.delete']);
});

// --- API: USUARIOS (JSON) ---
$routes->group('api/usuarios', ['filter' => 'permission:usuarios.view'], function ($routes) {
    $routes->get('', 'UserController::index');
    $routes->get('(:num)', 'UserController::show/$1');
    $routes->post('', 'UserController::create', ['filter' => 'permission:usuarios.create']);
    $routes->put('(:num)', 'UserController::update/$1', ['filter' => 'permission:usuarios.edit']);
    $routes->delete('(:num)', 'UserController::delete/$1', ['filter' => 'permission:usuarios.delete']);
    $routes->put('(:num)/rol', 'UserController::assignRole/$1', ['filter' => 'permission:usuarios.edit']);
});

// --- API: ROLES Y PERMISOS (JSON) ---
$routes->group('api/roles', ['filter' => 'permission:roles.view'], function ($routes) {
    $routes->get('', 'RoleController::index');
    $routes->get('(:num)', 'RoleController::show/$1');
    $routes->post('', 'RoleController::create', ['filter' => 'permission:roles.create']);
    $routes->put('(:num)', 'RoleController::update/$1', ['filter' => 'permission:roles.edit']);
    $routes->delete('(:num)', 'RoleController::delete/$1', ['filter' => 'permission:roles.delete']);
    $routes->post('(:num)/permisos', 'RoleController::syncPermissions/$1', ['filter' => 'permission:roles.edit']);
});

// --- API: PERFIL (JSON) ---
$routes->group('api/perfil', ['filter' => 'jwt'], function ($routes) {
    $routes->get('', 'ProfileController::getProfile');
    $routes->put('', 'ProfileController::updateApi');
    $routes->put('cambiar-password', 'ProfileController::cambiarPasswordApi');
});

// --- FORMULARIOS HTML: PERFIL ---
$routes->group('perfil', ['filter' => 'jwt'], function ($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->get('api', 'ProfileController::getProfile'); // ← AGREGAR ESTA LÍNEA

    $routes->post('update', 'ProfileController::update');
    $routes->post('check-email', 'ProfileController::checkEmail');
    $routes->post('cambiar-password', 'ProfileController::cambiarPassword');
    $routes->post('upload-avatar', 'ProfileController::uploadAvatar');
    $routes->post('eliminar', 'ProfileController::eliminar');
});
