<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    /**
     * Redirigir al login si NO está autenticado
     * O al dashboard si YA está autenticado
     */
    public function login()
    {
        // Verificar si ya hay token en localStorage (del lado del cliente)
        // O verificar sesión del servidor
        if (session()->get('jwt_token') || session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    /**
     * Mostrar dashboard (solo si está autenticado)
     */
    public function index()
    {
        // El filtro JWT ya verificó la autenticación
        return view('dashboard/index');
    }

    public function register()
    {
        return view('auth/register');
    }

    public function vinilosView()
    {
        return view('vinilos/index');
    }

    public function usuariosView()
    {
        return view('usuarios/index');
    }

    public function rolesView()
    {
        return view('roles/index');
    }
}
