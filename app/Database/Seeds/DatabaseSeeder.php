<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Matikan FK checks
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // Daftar tabel
        $tables = [
            'users',
            'forums',
            'anggota_forum',
            'kanban',
            'reminders',
            'discussions',
            'notes',
            'media'
        ];

        foreach ($tables as $table) {
            if ($db->tableExists($table)) {
                $db->table($table)->truncate();
            }
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Jalankan seeders
        $this->call('UserSeeder');
        $this->call('ForumSeeder');
        $this->call('AnggotaForumSeeder');
        $this->call('KanbanSeeder');
        $this->call('ReminderSeeder');
        $this->call('DiscussionSeeder');
        $this->call('NoteSeeder');
        $this->call('MediaSeeder');
    }
}
