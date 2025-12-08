<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int         $forum_id
 * @property int|null    $admin_id
 * @property string|null $nama
 * @property string|null $deskripsi
 * @property string|null $kode_undangan
 * @property string      $jenis_forum
 * @property int         $allow_media_upload
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * Relations:
 * - members(): join anggota_forum + users
 * - tasks(): kanbans by forum_id
 * - discussions(), notes(), media()
 */
class Forum extends Entity
{
	protected $dates = ['created_at', 'updated_at'];
	protected $casts = [
		'forum_id'  => 'integer',
		'admin_id'  => 'integer',
		'allow_media_upload' => 'integer',
	];
}


