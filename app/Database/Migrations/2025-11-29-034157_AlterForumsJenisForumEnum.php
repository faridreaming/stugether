<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterForumsJenisForumEnum extends Migration
{
    public function up()
    {
        // Ubah enum jenis_forum menjadi ['publik', 'privat']
        $this->db->query("
            ALTER TABLE `forums`
            MODIFY `jenis_forum` ENUM('publik', 'privat') NOT NULL DEFAULT 'publik'
        ");
    }

    public function down()
    {
        // Kembalikan ke enum awal, sesuai migration CreateForumsTable
        $this->db->query("
            ALTER TABLE `forums`
            MODIFY `jenis_forum` ENUM('akademik', 'proyek', 'komunitas', 'lainnya') NOT NULL DEFAULT 'akademik'
        ");
    }
}
