<?php

namespace App\Controllers\API;

use App\Models\ForumModel;
use App\Models\AnggotaForumModel;
use App\Models\KanbanModel;
use CodeIgniter\Database\BaseBuilder;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;

class ForumController extends BaseAPIController
{
	#[OAT\Post(
		path: "/forums",
		tags: ["Forums"],
		summary: "Create forum",
		security: [["bearerAuth" => []]],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(
				required: ["nama"],
				properties: [
					new OAT\Property(property: "nama", type: "string"),
					new OAT\Property(property: "deskripsi", type: "string"),
					new OAT\Property(property: "jenis_forum", type: "string", enum: ["publik", "privat"]),
				]
			)
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request"),
			new OAT\Response(response: 401, description: "Unauthorized")
		]
	)]
	public function store()
	{
		$rules = config('Validation')->forumStore;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$data       = $this->request->getJSON(true) ?? $this->request->getPost();
		$current    = $this->currentUser();
		$model      = new ForumModel();
		$jenisForum = $data['jenis_forum'] ?? 'publik';

		$payload = [
			'admin_id'    => $current->user_id,
			'nama'        => $data['nama'],
			'deskripsi'   => $data['deskripsi'] ?? null,
			'jenis_forum' => $jenisForum,
		];
		if ($jenisForum === 'privat') {
			$payload['kode_undangan'] = $this->generateUniqueKode();
		}

		$forumId = $model->insert($payload, true);
		$forum = $model->find($forumId);

		// auto-join admin
		(new AnggotaForumModel())->insert([
			'forum_id'       => $forumId,
			'user_id'        => $current->user_id,
			'allowed_upload' => 1,
		]);

		return $this->success($forum, 'Created', null, 201);
	}

	#[OAT\Get(
		path: "/forums",
		tags: ["Forums"],
		summary: "List forums",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "scope", in: "query", required: false, schema: new OAT\Schema(type: "string", enum: ["mine", "public", "all"])),
			new OAT\Parameter(name: "q", in: "query", required: false, schema: new OAT\Schema(type: "string")),
			new OAT\Parameter(name: "sort", in: "query", required: false, schema: new OAT\Schema(type: "string", enum: ["created_at", "nama"])),
			new OAT\Parameter(name: "page", in: "query", required: false, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "per_page", in: "query", required: false, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function index()
	{
		$current    = $this->currentUser();
		$scope      = $this->request->getGet('scope') ?? 'all';
		$q          = trim((string) ($this->request->getGet('q') ?? ''));
		$sort       = $this->request->getGet('sort') ?? 'created_at';
		$page       = max(1, (int) ($this->request->getGet('page') ?? 1));
		$perPage    = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 10)));

		$builder = (new ForumModel())->builder();
		if ($scope === 'mine') {
			$builder->join('anggota_forum af', 'af.forum_id = forums.forum_id', 'inner')
				->where('af.user_id', $current->user_id);
		} elseif ($scope === 'public') {
			$builder->where('is_public', 1);
		}
		if ($q !== '') {
			$builder->groupStart()
				->like('nama', $q)
				->orLike('deskripsi', $q)
				->groupEnd();
		}
		$allowedSort = ['created_at', 'nama'];
		if (! in_array($sort, $allowedSort, true)) {
			$sort = 'created_at';
		}
		$builder->orderBy($sort, 'DESC');

		// Pagination
		$total   = (clone $builder)->countAllResults(false);
		$results = $builder->get(($page - 1) * $perPage, $perPage)->getResult();

		$meta = service('paginationSvc')->buildMeta($page, $perPage, $total);
		return $this->success($results, null, $meta);
	}

	#[OAT\Get(
		path: "/forums/recommended",
		tags: ["Forums"],
		summary: "Recommended public forums",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "limit", in: "query", required: false, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function recommended()
	{
		$limit   = min(50, max(1, (int) ($this->request->getGet('limit') ?? 10)));
		$builder = (new ForumModel())->builder()->where('is_public', 1)->orderBy('created_at', 'DESC');
		$forums  = $builder->get($limit)->getResult();

		$anggotaModel = new AnggotaForumModel();
		foreach ($forums as $forum) {
			$memberCount = $anggotaModel->where('forum_id', $forum->forum_id)->countAllResults();
			$forum->jumlah_anggota = $memberCount;
		}

		return $this->success($forums);
	}

	#[OAT\Get(
		path: "/forums/{id}",
		tags: ["Forums"],
		summary: "Show forum",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))
		],
		responses: [
			new OAT\Response(response: 200, description: "OK"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function show(int $forumId)
	{
		$forum = (new ForumModel())->find($forumId);
		if (! $forum) {
			return $this->fail('Forum not found', 404);
		}
		$membersCount = (new AnggotaForumModel())->where('forum_id', $forumId)->countAllResults();
		$tasksCount   = (new KanbanModel())->where('forum_id', $forumId)->countAllResults();

		return $this->success([
			'forum'         => $forum,
			'counts'        => [
				'members' => $membersCount,
				'tasks'   => $tasksCount,
			],
		]);
	}

	#[OAT\Patch(
		path: "/forums/{id}",
		tags: ["Forums"],
		summary: "Update forum (admin)",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))
		],
		requestBody: new OAT\RequestBody(
			required: false,
			content: new OAT\JsonContent(
				properties: [
					new OAT\Property(property: "nama", type: "string"),
					new OAT\Property(property: "deskripsi", type: "string"),
					new OAT\Property(property: "jenis_forum", type: "string", enum: ["publik", "privat"]),
				]
			)
		),
		responses: [
			new OAT\Response(response: 200, description: "Updated"),
			new OAT\Response(response: 400, description: "Bad Request"),
			new OAT\Response(response: 403, description: "Forbidden")
		]
	)]
	public function update(int $forumId)
	{
		$rules = config('Validation')->forumUpdate;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$data  = $this->request->getJSON(true) ?? $this->request->getRawInput();
		$patch = array_intersect_key($data, array_flip(['nama', 'deskripsi', 'jenis_forum']));
		$model = new ForumModel();
		$model->update($forumId, $patch);
		$forum = $model->find($forumId);
		return $this->success($forum, 'Updated');
	}

	#[OAT\Delete(
		path: "/forums/{id}",
		tags: ["Forums"],
		summary: "Delete forum (admin)",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "Deleted")]
	)]
	public function destroy(int $forumId)
	{
		$model = new ForumModel();
		$model->delete($forumId);
		return $this->success(['ok' => true], 'Deleted');
	}

	private function generateUniqueKode(): string
	{
		$model = new ForumModel();
		for ($i = 0; $i < 5; $i++) {
			$code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
			if (! $model->where('kode_undangan', $code)->first()) {
				return $code;
			}
		}
		return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
	}
}
