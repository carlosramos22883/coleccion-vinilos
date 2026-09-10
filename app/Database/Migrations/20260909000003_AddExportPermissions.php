<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExportPermissions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Permisos de exportación
        $exportPermissions = [
            ['clave' => 'vinilos.export', 'descripcion' => 'Exportar catálogo de vinilos', 'modulo' => 'vinilos'],
            ['clave' => 'usuarios.export', 'descripcion' => 'Exportar lista de usuarios', 'modulo' => 'usuarios'],
            ['clave' => 'roles.export', 'descripcion' => 'Exportar roles y permisos', 'modulo' => 'roles'],
        ];

        foreach ($exportPermissions as $permission) {
            $exists = $db->table('permisos')->where('clave', $permission['clave'])->countAllResults() > 0;
            if (!$exists) {
                $db->table('permisos')->insert(array_merge($permission, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]));
            }
        }

        // Asignar permisos al rol Administrador(ID 1)
        $allPermissions = $db->table('permisos')->get()->getResultArray();
        $adminPermissions = $db->table('rol_permiso')->where('rol_id', 1)->get()->getResultArray();
        $adminPermissionIds = array_column($adminPermissions, 'permiso_id');

        foreach ($allPermissions as $permission) {
            if (!in_array($permission['id'], $adminPermissionIds)) {
                $db->table('rol_permiso')->insert([
                    'rol_id' => 1,
                    'permiso_id' => $permission['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Asignar permisos al rol Coleccionista (ID 2)
        $allPermissions = $db->table('permisos')->get()->getResultArray();
        $adminPermissions = $db->table('rol_permiso')->where('rol_id', 1)->get()->getResultArray();
        $adminPermissionIds = array_column($adminPermissions, 'permiso_id');

        foreach ($allPermissions as $permission) {
            if (!in_array($permission['id'], $adminPermissionIds)) {
                $db->table('rol_permiso')->insert([
                    'rol_id' => 1,
                    'permiso_id' => $permission['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->table('permisos')->whereIn('clave', ['vinilos.export', 'usuarios.export', 'roles.export'])->delete();
    }
}
