<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ActualizarCamposVinilos extends Migration
{
    public function up()
    {
        // 1. Modificar el campo 'formato' a ENUM
        $modifyFormato = [
            'formato' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'LP',
                    'Single',
                    'Maxi Single',
                    'EP',
                    '10 pulgadas',
                    '78 RPM',
                    'Picture Disc',
                    'Vinilo de Color',
                    'Flexi Disc',
                    'Shaped Disc'
                ],
                'default'    => 'LP'
            ]
        ];
        $this->forge->modifyColumn('vinilos', $modifyFormato);

        // 2. Modificar el campo 'estado_conservacion' para usar los códigos de la escala estándar ENUM
        $modifyEstado = [
            'estado_conservacion' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'M',
                    'NM',
                    'EX',
                    'VG+',
                    'VG',
                    'G',
                    'F/P'
                ],
                'default'    => 'VG+'
            ]
        ];
        $this->forge->modifyColumn('vinilos', $modifyEstado);
    }

    public function down()
    {
        // Revertir a VARCHAR en caso de deshacer la migración
        $revertFields = [
            'formato' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true
            ],
            'estado_conservacion' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true
            ]
        ];
        $this->forge->modifyColumn('vinilos', $revertFields);
    }
}
