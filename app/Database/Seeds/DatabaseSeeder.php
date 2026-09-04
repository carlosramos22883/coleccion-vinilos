<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Cargar RBAC (Permisos, Roles y su pivote)
        $this->call('RbacSeeder');

        // 2. Cargar Usuarios Semilla asociados a sus Roles
        $this->call('UserSeeder');
    }
}
