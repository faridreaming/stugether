<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAllowMediaUploadToForums extends Migration
{
    public function up()
    {
        $fields = [
            'allow_media_upload' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
        ];

        $this->forge->addColumn('forums', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('forums', 'allow_media_upload');
    }
}
