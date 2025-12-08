<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Forum;

/**
 * @extends Model<Forum>
 */
class ForumModel extends Model
{
	protected $table          = 'forums';
	protected $primaryKey     = 'forum_id';
	protected $returnType     = Forum::class;
	protected $useTimestamps  = false;
	protected $allowedFields  = [
		'admin_id', 'nama', 'deskripsi', 'kode_undangan', 'jenis_forum', 'allow_media_upload',
	];
}


