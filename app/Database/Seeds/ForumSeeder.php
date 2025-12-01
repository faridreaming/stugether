<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run()
    {
        // Check if forums already exist
        if ($this->db->table('forums')->countAllResults() > 0) {
            echo "Forums already seeded. Skipping...\n";
            return;
        }

        // Get admin user IDs (assuming users are already seeded)
        $adminIds = $this->db->table('users')->select('user_id')->limit(3)->get()->getResultArray();

        if (empty($adminIds)) {
            return; // Users must be seeded first
        }

        $data = [
            [
                'admin_id'       => $adminIds[0]['user_id'],
                'nama'           => 'Forum Algoritma dan Struktur Data',
                'deskripsi'      => 'Forum diskusi untuk mata kuliah Algoritma dan Struktur Data. Berbagi materi, tugas, dan diskusi seputar algoritma.',
                'kode_undangan'  => 'ALGO2024', // forum privat untuk kelas tertentu
                'jenis_forum'    => 'privat',
                'is_public'      => 0,
            ],
            [
                'admin_id'       => $adminIds[1]['user_id'],
                'nama'           => 'Proyek Aplikasi Web',
                'deskripsi'      => 'Forum untuk kolaborasi proyek pengembangan aplikasi web menggunakan framework modern.',
                'kode_undangan'  => 'WEB2024', // forum privat, hanya anggota proyek
                'jenis_forum'    => 'privat',
                'is_public'      => 0,
            ],
            [
                'admin_id'       => $adminIds[2]['user_id'],
                'nama'           => 'Komunitas Programming',
                'deskripsi'      => 'Komunitas untuk berbagi pengetahuan dan pengalaman dalam programming.',
                'kode_undangan'  => 'PROG2024', // forum publik, bisa ditemukan semua user
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
            [
                'admin_id'       => $adminIds[0]['user_id'],
                'nama'           => 'Database Management System',
                'deskripsi'      => 'Forum diskusi untuk mata kuliah Database Management System.',
                'kode_undangan'  => 'DBMS2024', // forum privat untuk satu kelas
                'jenis_forum'    => 'privat',
                'is_public'      => 0,
            ],
            [
                'admin_id'       => $adminIds[1]['user_id'],
                'nama'           => 'Belajar AI dan Data Science',
                'deskripsi'      => 'Forum publik untuk berbagi referensi, dataset, dan praktik terbaik dalam AI & data science.',
                'kode_undangan'  => 'AIDS2024', // forum publik
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
            [
                'admin_id'       => $adminIds[2]['user_id'],
                'nama'           => 'Komunitas Riset Kampus',
                'deskripsi'      => 'Forum kolaborasi riset lintas fakultas untuk mencari rekan penelitian baru.',
                'kode_undangan'  => 'RSKA2024', // forum publik
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
            [
                'admin_id'       => $adminIds[0]['user_id'],
                'nama'           => 'Forum Startup & Inovasi',
                'deskripsi'      => 'Tempat berbagi ide startup, validasi problem, hingga kolaborasi mencari co-founder.',
                'kode_undangan'  => 'START2024', // forum publik
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
            [
                'admin_id'       => $adminIds[1]['user_id'],
                'nama'           => 'Belajar UI/UX Design',
                'deskripsi'      => 'Forum rekomendasi untuk saling review desain, berbagi asset, dan praktik usability testing.',
                'kode_undangan'  => 'UIUX2024', // forum publik
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
            [
                'admin_id'       => $adminIds[2]['user_id'],
                'nama'           => 'Kelompok Studi Cloud Computing',
                'deskripsi'      => 'Diskusi arsitektur cloud, sertifikasi, dan berbagi infrastruktur percobaan.',
                'kode_undangan'  => 'CLOUD2024', // forum publik
                'jenis_forum'    => 'publik',
                'is_public'      => 1,
            ],
        ];

        $this->db->table('forums')->insertBatch($data);
    }
}
