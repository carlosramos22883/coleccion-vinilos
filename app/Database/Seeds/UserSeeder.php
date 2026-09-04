<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // 1. Insertar el usuario (sin 'role_id')
        $userData = [
            'nombre'       => 'Administrador General',
            'email'        => 'admin@ejemplo.com',
            'password'     => password_hash('admin123', PASSWORD_BCRYPT),
            'email_verified' => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        // Se inserta en la tabla 'users'
        $db->table('users')->ignore(true)->insert($userData);

        // Obtener el ID del usuario insertado
        $userId = $db->insertID();

        // Si el usuario ya existía y no generó un insertID nuevo, buscamos su ID
        if (! $userId) {
            $user = $db->table('users')->where('email', $userData['email'])->get()->getRow();
            $userId = $user ? $user->id : null;
        }

        // 2. Asignar el rol en la tabla pivote 'user_roles'
        if ($userId) {
            $db->table('user_roles')->ignore(true)->insert([
                'user_id'    => $userId,
                'role_id'    => 1, // Rol Administrador
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
