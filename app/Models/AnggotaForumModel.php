<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\AnggotaForum;

/**
 * @extends Model<AnggotaForum>
 */
class AnggotaForumModel extends Model
{
	protected $table         = 'anggota_forum';
	protected $primaryKey    = 'anggota_id';
	protected $returnType    = AnggotaForum::class;
	protected $useTimestamps = false;
	protected $allowedFields = [
		'forum_id', 'user_id', 'allowed_upload', 'join_method', 'joined_at',
	];

	public function isMember(int $forumId, int $userId): bool
	{
		return (bool) $this->where(['forum_id' => $forumId, 'user_id' => $userId])->countAllResults();
	}
}


