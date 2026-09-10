<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    // ✅ CONFIGURACIÓN DE SOFT DELETE
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'nombre',
        'email',
        'password',
        'avatar',
        'email_verified',
        'verification_token',
        'verification_token_expires_at',
        'reset_token',
        'reset_token_expires_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';  // ← Única declaración aquí

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }
        return $data;
    }

    public function getRoles(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('user_roles')
            ->select('roles.nombre')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    public function getPermissions(int $userId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('user_roles')
            ->join('rol_permiso', 'rol_permiso.rol_id = user_roles.role_id')
            ->join('permisos', 'permisos.id = rol_permiso.permiso_id')
            ->select('permisos.clave')
            ->where('user_roles.user_id', $userId)
            ->groupBy('permisos.clave');

        $results = $builder->get()->getResultArray();
        return array_column($results, 'clave');
    }
}
