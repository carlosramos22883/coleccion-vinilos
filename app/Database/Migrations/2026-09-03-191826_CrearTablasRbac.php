<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablasRbac extends Migration
{
    public function up()
    {
        // 1. Tabla 'permisos'
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'clave' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'descripcion' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'modulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('permisos', true);

        // 2. Tabla pivote 'rol_permiso'
        $this->forge->addField([
            'rol_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permiso_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey(['rol_id', 'permiso_id'], true);
        $this->forge->addForeignKey('rol_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permiso_id', 'permisos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rol_permiso', true);
    }

    public function down()
    {
        $this->forge->dropTable('rol_permiso', true);
        $this->forge->dropTable('permisos', true);
    }
}
