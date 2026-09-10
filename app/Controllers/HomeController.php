<?php

namespace App\Controllers;

use App\Models\UserModel;

class HomeController extends BaseController
{
    /**
     * Redirigir al login si NO está autenticado
     * O al dashboard si YA está autenticado
     */
    public function login()
    {
        // Verificar si hay un usuario REAL y válido
        $user = session()->get('auth_user');

        if ($user) {
            return redirect()->to('/dashboard');
        }

        // Si llegamos aquí, NO hay usuario válido. 
        // Destruimos la sesión COMPLETA para romper cualquier bucle de redirección.
        session()->destroy();

        return view('auth/login');
    }

    /**
     * Mostrar dashboard (solo si está autenticado)
     */
    public function index()
    {
        $user = session()->get('auth_user');

        if (!$user) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $permisos = $userModel->getPermissions($user['id']);

        return view('dashboard/index', [
            'permisos' => $permisos
        ]);
    }

    public function register()
    {
        return view('auth/register');
    }

    public function vinilosView()
    {
        $user = session()->get('auth_user');

        if (!$user) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $permisos = $userModel->getPermissions($user['id']);

        return view('vinilos/index', [
            'permisos' => $permisos
        ]);
    }

    public function usuariosView()
    {
        $user = session()->get('auth_user');

        if (!$user) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $permisos = $userModel->getPermissions($user['id']);

        return view('usuarios/index', [
            'permisos' => $permisos
        ]);
    }

    public function rolesView()
    {
        $user = session()->get('auth_user');

        if (!$user) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $permisos = $userModel->getPermissions($user['id']);

        return view('roles/index', [
            'permisos' => $permisos
        ]);
    }
}
