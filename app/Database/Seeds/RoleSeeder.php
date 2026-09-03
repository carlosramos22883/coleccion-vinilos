<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nombre'      => 'Admin',
                'descripcion' => 'Administrador con acceso total al sistema',
            ],
            [
                'nombre'      => 'Coleccionista',
                'descripcion' => 'Usuario registrado con permisos de gestión sobre la colección',
            ],
        ];

        $this->db->table('roles')->ignore(true)->insertBatch($data);
    }
}
