<?php

namespace App\Controllers;

class ViewController extends BaseController
{
    public function login()    { return view('auth/login'); }
    public function register() { return view('auth/register'); }
    public function usuarios() { return view('usuarios/index'); }
    public function roles()    { return view('roles/index'); }
    public function vinilos()  { return view('vinilos/index'); }
}