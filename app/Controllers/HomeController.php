<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    // Vista del Dashboard / Principal
    public function index()
    {
        return view('vinilos/index');
    }

    // Vistas de Autenticación
    public function login()
    {
        return view('auth/login');
    }

    public function register()
    {
        return view('auth/register');
    }

    // Vistas de Módulos (Vistas HTML del Frontend)
    public function usuariosView()
    {
        return view('usuarios/index');
    }

    public function rolesView()
    {
        return view('roles/index');
    }

    public function vinilosView()
    {
        return view('vinilos/index');
    }
}
