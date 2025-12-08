<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJoinMethodToAnggotaForum extends Migration
{
    public function up()
    {
        // Tambah kolom join_method untuk melacak cara user bergabung ke forum
        // Values: 'creator' (pembuat forum), 'invitation_code' (via kode undangan), 'public' (forum publik)
        $this->forge->addColumn('anggota_forum', [
            'join_method' => [
                'type'       => 'ENUM',
                'constraint' => ['creator', 'invitation_code', 'public'],
                'default'    => 'public',
                'null'       => false,
                'after'      => 'allowed_upload',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('anggota_forum', 'join_method');
    }
}
