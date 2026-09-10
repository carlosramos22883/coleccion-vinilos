<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddGranularPermissions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ============================================
        // 1. Agregar permisos faltantes para PERFIL
        // ============================================
        $perfilPermissions = [
            [
                'clave' => 'perfil.avatar.update',
                'descripcion' => 'Cambiar foto de perfil',
                'modulo' => 'perfil'
            ],
            [
                'clave' => 'perfil.password.update',
                'descripcion' => 'Cambiar contraseña propia',
                'modulo' => 'perfil'
            ],
            [
                'clave' => 'perfil.delete',
                'descripcion' => 'Eliminar cuenta propia',
                'modulo' => 'perfil'
            ]
        ];

        foreach ($perfilPermissions as $permission) {
            // Verificar si ya existe
            $exists = $db->table('permisos')
                ->where('clave', $permission['clave'])
                ->countAllResults() > 0;

            if (!$exists) {
                $db->table('permisos')->insert([
                    'clave' => $permission['clave'],
                    'descripcion' => $permission['descripcion'],
                    'modulo' => $permission['modulo'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // ============================================
        // 5. Asignar TODOS los permisos al rol Administrador (ID 1)
        // ============================================

        // Obtener todos los permisos
        $allPermissions = $db->table('permisos')->get()->getResultArray();

        // Obtener permisos actuales del administrador
        $adminPermissions = $db->table('rol_permiso')
            ->where('rol_id', 1)
            ->get()->getResultArray();

        $adminPermissionIds = array_column($adminPermissions, 'permiso_id');

        // Asignar permisos faltantes
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

        // ============================================
        // 6. Asignar permisos específicos a los demás roles
        // ============================================

        // Obtener IDs de los nuevos permisos de perfil
        $profilePerms = $db->table('permisos')
            ->whereIn('clave', ['perfil.avatar.update', 'perfil.password.update'])
            ->get()->getResultArray();
        $profilePermIds = array_column($profilePerms, 'id');

        // Mapeo de qué permisos (IDs) van a qué rol
        // Coleccionista (ID 2) recibe: perfil.avatar.update, perfil.password.update y vinilos.delete (ID 4)
        // Lector (ID 3) recibe: perfil.avatar.update y perfil.password.update
        $rolePermissionsMapping = [
            2 => array_merge($profilePermIds, [4]),
            3 => $profilePermIds
        ];

        foreach ($rolePermissionsMapping as $rolId => $permissionIds) {
            // Obtener lo que ya tiene asignado el rol actual para no duplicar
            $currentRolePermissions = $db->table('rol_permiso')
                ->where('rol_id', $rolId)
                ->get()->getResultArray();

            $currentPermissionIds = array_column($currentRolePermissions, 'permiso_id');

            foreach ($permissionIds as $permId) {
                if (!in_array($permId, $currentPermissionIds)) {
                    $db->table('rol_permiso')->insert([
                        'rol_id' => $rolId,
                        'permiso_id' => $permId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Eliminar permisos agregados (solo los nuevos)
        $permissionsToDelete = [
            'perfil.avatar.update',
            'perfil.password.update',
            'perfil.delete',
        ];

        foreach ($permissionsToDelete as $clave) {
            // Primero eliminar de rol_permiso de cualquier rol que lo tenga
            $permission = $db->table('permisos')->where('clave', $clave)->get()->getRow();
            if ($permission) {
                $db->table('rol_permiso')->where('permiso_id', $permission->id)->delete();
                $db->table('permisos')->where('clave', $clave)->delete();
            }
        }

        // Nota: No eliminamos el permiso 4 (vinilos.delete) en el down 
        // porque asumimos que existía previamente en la base de datos.
    }
}
