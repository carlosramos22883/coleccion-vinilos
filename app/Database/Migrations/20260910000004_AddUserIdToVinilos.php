<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToVinilos extends Migration
{
    public function up()
    {
        // 1. Agregar columna user_id
        $this->forge->addColumn('vinilos', [
            'user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => false,
                'after'          => 'id', // Después del ID
            ],
        ]);

        // Agregar clave foránea
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        // Agregar índice para mejorar rendimiento
        $this->forge->addKey('user_id');

        // 2. Nueva lógica: Asignar el permiso vinilos.export al rol Coleccionista (ID 2)
        $db = \Config\Database::connect();

        // Buscamos el registro del permiso por su clave para obtener su ID de forma segura
        $permiso = $db->table('permisos')->where('clave', 'vinilos.export')->get()->getRowArray();

        if ($permiso) {
            // Verificamos primero que el rol 2 no tenga ya asignado este permiso
            $existe = $db->table('rol_permiso')
                ->where('rol_id', 2)
                ->where('permiso_id', $permiso['id'])
                ->countAllResults();

            if ($existe == 0) {
                $db->table('rol_permiso')->insert([
                    'rol_id'     => 2,
                    'permiso_id' => $permiso['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    public function down()
    {
        // 1. Nueva lógica inversa: Quitar el permiso al rol 2 al revertir
        $db = \Config\Database::connect();
        $permiso = $db->table('permisos')->where('clave', 'vinilos.export')->get()->getRowArray();

        if ($permiso) {
            $db->table('rol_permiso')
                ->where('rol_id', 2)
                ->where('permiso_id', $permiso['id'])
                ->delete();
        }

        // 2. Eliminar clave foránea
        $this->forge->dropForeignKey('vinilos', 'vinilos_user_id_foreign');

        // 3. Eliminar columna
        $this->forge->dropColumn('vinilos', 'user_id');
    }
}
