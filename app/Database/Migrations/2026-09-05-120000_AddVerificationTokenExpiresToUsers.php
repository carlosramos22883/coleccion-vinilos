<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVerificationTokenExpiresToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'verification_token_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'verification_token',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'verification_token_expires_at');
    }
}
