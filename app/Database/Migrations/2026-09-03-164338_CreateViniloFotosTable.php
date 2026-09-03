<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViniloFotosTable extends Migration
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
            'vinilo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ruta_foto' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'es_portada' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('vinilo_id', 'vinilos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vinilo_fotos');
    }

    public function down()
    {
        $this->forge->dropTable('vinilo_fotos');
    }
}
