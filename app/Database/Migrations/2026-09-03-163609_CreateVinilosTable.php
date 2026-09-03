<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVinilosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'artista' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'anio_lanzamiento' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
            ],
            'genero' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'formato' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => '12" LP',
            ],
            'estado_conservacion' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'Very Good (VG)',
            ],
            'estado_adquisicion' => [
                'type'       => 'ENUM',
                'constraint' => ['Comprado Nuevo', 'Comprado Usado', 'Regalado Nuevo', 'Regalado Usado', 'Heredado', 'Intercambio'],
                'default'    => 'Comprado Nuevo',
            ],
            'fecha_adquisicion' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'precio' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
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
        $this->forge->createTable('vinilos');
    }

    public function down()
    {
        $this->forge->dropTable('vinilos');
    }
}
