<?php

namespace App\Controllers\API;

use App\Models\ForumModel;
use App\Models\AnggotaForumModel;
use App\Models\UserModel;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;

class ForumMemberController extends BaseAPIController
{
	#[OAT\Post(
		path: "/forums/{id}/join",
		tags: ["Forums"],
		summary: "Join forum (kode_undangan required for private forum)",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))
		],
		requestBody: new OAT\RequestBody(
			required: false,
			content: new OAT\JsonContent(
				properties: [new OAT\Property(property: "kode_undangan", type: "string")]
			)
		),
		responses: [
			new OAT\Response(response: 200, description: "Joined"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
	public function join(int $forumId)
	{
		$data  = $this->request->getJSON(true) ?? $this->request->getPost();
		$kode  = $data['kode_undangan'] ?? null;
		$forum = (new ForumModel())->find($forumId);
		if (! $forum) {
			return $this->fail('Forum not found', 404);
		}
		
		// Determine join method based on forum type and provided kode
		$joinMethod = 'public';
		
		// Private forum requires kode_undangan
		if ($forum->jenis_forum === 'privat') {
			if (empty($kode)) {
				return $this->fail('Kode undangan diperlukan untuk forum privat', 400);
			}
			if ($forum->kode_undangan !== $kode) {
				return $this->fail('Kode undangan tidak valid', 400);
			}
			$joinMethod = 'invitation_code';
		} else {
			// Public forum - check if kode_undangan was provided (optional)
			if (!empty($kode) && $forum->kode_undangan === $kode) {
				$joinMethod = 'invitation_code';
			}
		}
		
		$user   = $this->currentUser();
		$model  = new AnggotaForumModel();
		$exists = $model->where(['forum_id' => $forumId, 'user_id' => $user->user_id])->first();
		if (! $exists) {
			$insertResult = $model->insert([
				'forum_id'       => $forumId,
				'user_id'        => $user->user_id,
				'allowed_upload' => 0,
				'join_method'    => $joinMethod,
			]);
			
			if ($insertResult === false) {
				$errors = $model->errors();
				log_message('error', 'Failed to join forum: ' . implode('; ', $errors ?: ['Unknown error']));
				return $this->fail('Gagal bergabung ke forum: ' . implode('; ', $errors ?: ['Error tidak diketahui']), 400);
			}
			
			// Verify the user was actually registered (use fresh model instance to avoid query builder conflicts)
			$verifyModel = new AnggotaForumModel();
			$verified = $verifyModel->where(['forum_id' => $forumId, 'user_id' => $user->user_id])->first();
			if (! $verified) {
				log_message('error', 'User was not registered to forum after insert. Forum ID: ' . $forumId . ', User ID: ' . $user->user_id);
				return $this->fail('Gagal memverifikasi keanggotaan forum', 500);
			}
		}
		
		// Get member count
		$memberCount = $model->where('forum_id', $forumId)->countAllResults();
		
		// Return complete forum data
		return $this->success([
			'ok' => true,
			'forum_id' => $forum->forum_id,
			'forum_nama' => $forum->nama,
			'join_method' => $joinMethod,
			'forum' => [
				'forum_id' => $forum->forum_id,
				'nama' => $forum->nama,
				'deskripsi' => $forum->deskripsi,
				'jenis_forum' => $forum->jenis_forum,
				'kode_undangan' => $forum->kode_undangan,
				'allow_media_upload' => $forum->allow_media_upload,
				'jumlah_anggota' => $memberCount,
				'created_at' => $forum->created_at
			]
		], 'Joined');
	}

	#[OAT\Post(
		path: "/join/forum",
		tags: ["Forums"],
		summary: "Join forum by kode_undangan (find forum first)",
		security: [["bearerAuth" => []]],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(
				required: ["kode_undangan"],
				properties: [new OAT\Property(property: "kode_undangan", type: "string")]
			)
		),
		responses: [
			new OAT\Response(response: 200, description: "Joined"),
			new OAT\Response(response: 400, description: "Bad Request"),
			new OAT\Response(response: 404, description: "Forum not found")
		]
	)]
	public function joinByCode()
	{
		$rules = config('Validation')->forumJoin;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$data = $this->request->getJSON(true) ?? $this->request->getPost();
		$kode = $data['kode_undangan'];
		
		// Find forum by kode_undangan
		$forumModel = new ForumModel();
		$forum = $forumModel->where('kode_undangan', $kode)->first();
		
		if (! $forum) {
			return $this->fail('Forum tidak ditemukan dengan kode undangan tersebut', 404);
		}
		
		// Check if user is already a member
		$user   = $this->currentUser();
		$model  = new AnggotaForumModel();
		$exists = $model->where(['forum_id' => $forum->forum_id, 'user_id' => $user->user_id])->first();
		
		if ($exists) {
			// Get member count
			$memberCount = $model->where('forum_id', $forum->forum_id)->countAllResults();
			
			// Return complete forum data even if already a member
			return $this->success([
				'ok' => true,
				'forum_id' => $forum->forum_id,
				'forum_nama' => $forum->nama,
				'forum' => [
					'forum_id' => $forum->forum_id,
					'nama' => $forum->nama,
					'deskripsi' => $forum->deskripsi,
					'jenis_forum' => $forum->jenis_forum,
					'kode_undangan' => $forum->kode_undangan,
					'allow_media_upload' => $forum->allow_media_upload,
					'jumlah_anggota' => $memberCount,
					'created_at' => $forum->created_at
				]
			], 'Anda sudah menjadi anggota forum ini');
		}
		
		// Join the forum
		$insertResult = $model->insert([
			'forum_id'       => $forum->forum_id,
			'user_id'        => $user->user_id,
			'allowed_upload' => 0,
			'join_method'    => 'invitation_code', // Bergabung via kode undangan
		]);
		
		if ($insertResult === false) {
			$errors = $model->errors();
			$errorMessage = !empty($errors) ? implode('; ', $errors) : 'Error tidak diketahui';
			log_message('error', 'Failed to join forum by code. Forum ID: ' . $forum->forum_id . ', User ID: ' . $user->user_id . ', Errors: ' . $errorMessage);
			return $this->fail('Gagal bergabung ke forum: ' . $errorMessage, 400);
		}
		
		// Verify the user was actually registered to the forum table (use fresh model instance to avoid query builder conflicts)
		$verifyModel = new AnggotaForumModel();
		$verified = $verifyModel->where(['forum_id' => $forum->forum_id, 'user_id' => $user->user_id])->first();
		if (! $verified) {
			log_message('error', 'User was not registered to forum after insert. Forum ID: ' . $forum->forum_id . ', User ID: ' . $user->user_id);
			return $this->fail('Gagal memverifikasi keanggotaan forum. Silakan coba lagi.', 500);
		}
		
		// Get member count
		$memberCount = $verifyModel->where('forum_id', $forum->forum_id)->countAllResults();
		
		// Return complete forum data
		return $this->success([
			'ok' => true,
			'forum_id' => $forum->forum_id,
			'forum_nama' => $forum->nama,
			'forum' => [
				'forum_id' => $forum->forum_id,
				'nama' => $forum->nama,
				'deskripsi' => $forum->deskripsi,
				'jenis_forum' => $forum->jenis_forum,
				'kode_undangan' => $forum->kode_undangan,
				'allow_media_upload' => $forum->allow_media_upload,
				'jumlah_anggota' => $memberCount,
				'created_at' => $forum->created_at
			]
		], 'Berhasil bergabung dengan forum');
	}

	#[OAT\Post(
		path: "/forums/{id}/leave",
		tags: ["Forums"],
		summary: "Leave forum",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "Left"),
			new OAT\Response(response: 403, description: "Forbidden")
		]
	)]
	public function leave(int $forumId)
	{
		$current = $this->currentUser();
		$forum   = (new ForumModel())->find($forumId);
		if (! $forum) {
			return $this->fail('Forum not found', 404);
		}
		if ((int) $forum->admin_id === (int) $current->user_id) {
			return $this->fail('Admin cannot leave the forum', 403);
		}
		$model = new AnggotaForumModel();
		$model->where(['forum_id' => $forumId, 'user_id' => $current->user_id])->delete();
		return $this->success(['ok' => true], 'Left forum');
	}

	#[OAT\Get(
		path: "/forums/{id}/members",
		tags: ["Forums"],
		summary: "List forum members",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function members(int $forumId)
	{
		$builder = (new AnggotaForumModel())->builder()
			->select('u.user_id, u.nama, u.email, af.allowed_upload, af.join_method, af.joined_at')
			->from('anggota_forum af')
			->join('users u', 'u.user_id = af.user_id', 'inner')
			->where('af.forum_id', $forumId)
			->orderBy('u.nama', 'ASC');
		$rows = $builder->get()->getResultArray();
		return $this->success($rows);
	}

	#[OAT\Get(
		path: "/forums/{id}/membership",
		tags: ["Forums"],
		summary: "Get current user membership status in forum",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function membership(int $forumId)
	{
		$user  = $this->currentUser();
		$forum = (new ForumModel())->find($forumId);
		
		if (! $forum) {
			return $this->fail('Forum not found', 404);
		}
		
		$model  = new AnggotaForumModel();
		$member = $model->where(['forum_id' => $forumId, 'user_id' => $user->user_id])->first();
		
		$isCreator = ((int) $forum->admin_id === (int) $user->user_id);
		$isMember  = $member !== null;
		$joinMethod = $member ? ($member->join_method ?? 'public') : null;
		
		// User dapat menambah tugas jika:
		// 1. User adalah creator forum
		// 2. User bergabung via invitation_code
		$canAddTask = $isCreator || $joinMethod === 'invitation_code' || $joinMethod === 'creator';
		
		return $this->success([
			'forum_id'     => $forumId,
			'user_id'      => $user->user_id,
			'is_member'    => $isMember,
			'is_creator'   => $isCreator,
			'join_method'  => $joinMethod,
			'can_add_task' => $canAddTask,
			'allowed_upload' => $member ? (bool) $member->allowed_upload : false,
			'joined_at'    => $member ? $member->joined_at : null,
		]);
	}

	#[OAT\Patch(
		path: "/forums/{id}/members/{userId}",
		tags: ["Forums"],
		summary: "Update member allowed_upload (admin)",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "userId", in: "path", required: true, schema: new OAT\Schema(type: "integer")),
		],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(
				required: ["allowed_upload"],
				properties: [new OAT\Property(property: "allowed_upload", type: "integer", enum: [0,1])]
			)
		),
		responses: [new OAT\Response(response: 200, description: "Updated")]
	)]
	public function update(int $forumId, int $userId)
	{
		$rules = config('Validation')->memberUpdate;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$data = $this->request->getJSON(true) ?? $this->request->getRawInput();
		(new AnggotaForumModel())->where(['forum_id' => $forumId, 'user_id' => $userId])
			->set(['allowed_upload' => (int) $data['allowed_upload']])
			->update();

		return $this->success(['ok' => true], 'Updated');
	}
}


