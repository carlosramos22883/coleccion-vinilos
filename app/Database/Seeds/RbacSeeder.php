<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Catálogo Completo de Permisos Iniciales
        $permisosData = [
            // Módulo Vinilos
            ['clave' => 'vinilos.view',   'descripcion' => 'Ver catálogo de vinilos',          'modulo' => 'vinilos'],
            ['clave' => 'vinilos.create', 'descripcion' => 'Crear vinilos y subir fotos',        'modulo' => 'vinilos'],
            ['clave' => 'vinilos.edit',   'descripcion' => 'Editar datos de vinilos',           'modulo' => 'vinilos'],
            ['clave' => 'vinilos.delete', 'descripcion' => 'Eliminar vinilos y fotos',          'modulo' => 'vinilos'],

            // Módulo Usuarios
            ['clave' => 'usuarios.view',   'descripcion' => 'Ver lista y detalles de usuarios', 'modulo' => 'usuarios'],
            ['clave' => 'usuarios.create', 'descripcion' => 'Registrar nuevos usuarios',        'modulo' => 'usuarios'],
            ['clave' => 'usuarios.edit',   'descripcion' => 'Editar datos y roles de usuarios', 'modulo' => 'usuarios'],
            ['clave' => 'usuarios.delete', 'descripcion' => 'Eliminar o desactivar usuarios',   'modulo' => 'usuarios'],

            // Módulo Roles y Permisos (RBAC)
            ['clave' => 'roles.view',   'descripcion' => 'Ver lista de roles y sus permisos',   'modulo' => 'roles'],
            ['clave' => 'roles.create', 'descripcion' => 'Crear nuevos roles de usuario',       'modulo' => 'roles'],
            ['clave' => 'roles.edit',   'descripcion' => 'Editar roles y sus permisos',         'modulo' => 'roles'],
            ['clave' => 'roles.delete', 'descripcion' => 'Eliminar roles',                      'modulo' => 'roles'],

            // Módulo Perfil Propio
            ['clave' => 'perfil.view', 'descripcion' => 'Ver información de perfil propio',     'modulo' => 'perfil'],
            ['clave' => 'perfil.edit', 'descripcion' => 'Editar contraseña y datos propios',    'modulo' => 'perfil'],
        ];

        foreach ($permisosData as $permiso) {
            $db->table('permisos')->ignore(true)->insert([
                'clave'       => $permiso['clave'],
                'descripcion' => $permiso['descripcion'],
                'modulo'      => $permiso['modulo'],
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Insertar Roles Iniciales
        $rolesData = [
            ['nombre' => 'Administrador', 'descripcion' => 'Control total del sistema'],
            ['nombre' => 'Coleccionista', 'descripcion' => 'Puede ver, crear y editar su colección de vinilos'],
            ['nombre' => 'Lector',        'descripcion' => 'Solo consulta de información del catálogo'],
        ];

        foreach ($rolesData as $rol) {
            $db->table('roles')->ignore(true)->insert([
                'nombre'      => $rol['nombre'],
                'descripcion' => $rol['descripcion'],
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // Obtener todos los permisos guardados
        $todosPermisos = $db->table('permisos')->get()->getResultArray();

        // 3. Asignar TODOS los permisos al Administrador (rol_id = 1)
        foreach ($todosPermisos as $p) {
            $db->table('rol_permiso')->ignore(true)->insert([
                'rol_id'     => 1,
                'permiso_id' => $p['id']
            ]);
        }

        // 4. Asignar permisos al Coleccionista (rol_id = 2): Vinilos (view, create, edit) + Perfil
        foreach ($todosPermisos as $p) {
            if (in_array($p['clave'], ['vinilos.view', 'vinilos.create', 'vinilos.edit', 'perfil.view', 'perfil.edit'])) {
                $db->table('rol_permiso')->ignore(true)->insert([
                    'rol_id'     => 2,
                    'permiso_id' => $p['id']
                ]);
            }
        }

        // 5. Asignar permisos al Lector (rol_id = 3): Vinilos (view) + Perfil
        foreach ($todosPermisos as $p) {
            if (in_array($p['clave'], ['vinilos.view', 'perfil.view', 'perfil.edit'])) {
                $db->table('rol_permiso')->ignore(true)->insert([
                    'rol_id'     => 3,
                    'permiso_id' => $p['id']
                ]);
            }
        }
    }
}
