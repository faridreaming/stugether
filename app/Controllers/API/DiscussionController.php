<?php

namespace App\Controllers\API;

use App\Models\DiscussionModel;
use App\Models\ForumModel;
use App\Models\UserModel;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;

class DiscussionController extends BaseAPIController
{
	#[OAT\Post(
		path: "/forums/{id}/discussions",
		tags: ["Discussions"],
		summary: "Create discussion",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(required: ["isi"], properties: [new OAT\Property(property: "isi", type: "string")])
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
	public function store(int $forumId)
	{
		$rules = config('Validation')->discussionStore;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$data    = $this->request->getJSON(true) ?? $this->request->getPost();
		$current = $this->currentUser();
		$model   = new DiscussionModel();
		$id = $model->insert([
			'forum_id'  => $forumId,
			'user_id'   => $current->user_id,
			'parent_id' => null,
			'isi'       => $data['isi'],
		], true);
		$discussion = $this->enrichWithUserData($model->find($id));
		return $this->success($discussion, 'Created', null, 201);
	}

	#[OAT\Post(
		path: "/discussions/{id}/replies",
		tags: ["Discussions"],
		summary: "Reply to discussion",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(required: ["isi"], properties: [new OAT\Property(property: "isi", type: "string")])
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
	public function reply(int $discussionId)
	{
		$rules = config('Validation')->discussionReply;
		if (! $this->validate($rules)) {
			return $this->fail(implode('; ', $this->validator->getErrors()), 400);
		}
		$parent = (new DiscussionModel())->find($discussionId);
		if (! $parent) {
			return $this->fail('Discussion not found', 404);
		}
		$data    = $this->request->getJSON(true) ?? $this->request->getPost();
		$current = $this->currentUser();
		$model   = new DiscussionModel();
		$id = $model->insert([
			'forum_id'  => $parent->forum_id,
			'user_id'   => $current->user_id,
			'parent_id' => $discussionId,
			'isi'       => $data['isi'],
		], true);
		$reply = $this->enrichWithUserData($model->find($id));
		return $this->success($reply, 'Created', null, 201);
	}

	#[OAT\Get(
		path: "/forums/{id}/discussions",
		tags: ["Discussions"],
		summary: "List discussions (threaded by default)",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "threaded", in: "query", required: false, schema: new OAT\Schema(type: "boolean")),
			new OAT\Parameter(name: "q", in: "query", required: false, schema: new OAT\Schema(type: "string")),
			new OAT\Parameter(name: "page", in: "query", required: false, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "per_page", in: "query", required: false, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function index(int $forumId)
	{
		$threaded = filter_var($this->request->getGet('threaded') ?? 'true', FILTER_VALIDATE_BOOLEAN);
		$q        = trim((string) ($this->request->getGet('q') ?? ''));
		$model    = new DiscussionModel();
		$db       = \Config\Database::connect();
		$builder  = $db->table('discussions d')
			->select('d.*, u.nama as user_name, u.user_id as user_user_id, u.email as user_email')
			->join('users u', 'u.user_id = d.user_id', 'left')
			->where('d.forum_id', $forumId)
			->orderBy('d.created_at', 'DESC');
		if ($q !== '') {
			$builder->like('d.isi', $q);
		}
		if ($threaded) {
			$rows = $builder->get()->getResultArray();
			$data = service('discussionTree')->buildTree($rows);
			// Enrich tree nodes with user data
			$data = $this->enrichTreeWithUserData($data);
			return $this->success($data);
		}
		$page    = max(1, (int) ($this->request->getGet('page') ?? 1));
		$perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 10)));
		$total   = (clone $builder)->countAllResults(false);
		$rows    = $builder->get(($page - 1) * $perPage, $perPage)->getResultArray();
		$enrichedRows = array_map(function($row) {
			return $this->enrichArrayWithUserData($row);
		}, $rows);
		$meta    = service('paginationSvc')->buildMeta($page, $perPage, $total);
		return $this->success($enrichedRows, null, $meta);
	}

	#[OAT\Get(
		path: "/discussions/{id}",
		tags: ["Discussions"],
		summary: "Show discussion",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "OK"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function show(int $discussionId)
	{
		$disc = (new DiscussionModel())->find($discussionId);
		if (! $disc) {
			return $this->fail('Not found', 404);
		}
		$discussion = $this->enrichWithUserData($disc);
		return $this->success($discussion);
	}

	#[OAT\Patch(
		path: "/discussions/{id}",
		tags: ["Discussions"],
		summary: "Update discussion",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: false,
			content: new OAT\JsonContent(properties: [new OAT\Property(property: "isi", type: "string")])
		),
		responses: [
			new OAT\Response(response: 200, description: "Updated"),
			new OAT\Response(response: 403, description: "Forbidden")
		]
	)]
	public function update(int $discussionId)
	{
		$model = new DiscussionModel();
		$disc  = $model->find($discussionId);
		if (! $disc) {
			return $this->fail('Not found', 404);
		}
		if (! $this->canManage($disc->forum_id, $disc->user_id)) {
			return $this->fail('Forbidden', 403);
		}
		$data = $this->request->getJSON(true) ?? $this->request->getRawInput();
		$model->update($discussionId, ['isi' => $data['isi'] ?? $disc->isi]);
		$updated = $this->enrichWithUserData($model->find($discussionId));
		return $this->success($updated, 'Updated');
	}

	#[OAT\Delete(
		path: "/discussions/{id}",
		tags: ["Discussions"],
		summary: "Delete discussion",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "Deleted"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function destroy(int $discussionId)
	{
		$model = new DiscussionModel();
		$disc  = $model->find($discussionId);
		if (! $disc) {
			return $this->fail('Not found', 404);
		}
		if (! $this->canManage($disc->forum_id, $disc->user_id)) {
			return $this->fail('Forbidden', 403);
		}
		$model->delete($discussionId);
		return $this->success(['ok' => true], 'Deleted');
	}

	private function canManage(int $forumId, int $ownerId): bool
	{
		$current = $this->currentUser();
		if (! $current) {
			return false;
		}
		if ($ownerId === (int) $current->user_id) {
			return true;
		}
		$forum = (new ForumModel())->find($forumId);
		return $forum && (int) $forum->admin_id === (int) $current->user_id;
	}

	/**
	 * Enrich discussion entity with user data
	 */
	private function enrichWithUserData($discussion)
	{
		if (! $discussion) {
			return null;
		}
		
		// Convert entity to array if needed
		if (is_object($discussion)) {
			$discArray = [];
			$discArray['discussion_id'] = $discussion->discussion_id ?? null;
			$discArray['forum_id'] = $discussion->forum_id ?? null;
			$discArray['user_id'] = $discussion->user_id ?? null;
			$discArray['parent_id'] = $discussion->parent_id ?? null;
			$discArray['isi'] = $discussion->isi ?? null;
			$discArray['created_at'] = $discussion->created_at ?? null;
		} else {
			$discArray = $discussion;
		}
		
		// Get user data
		$userModel = new UserModel();
		$user = $userModel->find($discArray['user_id'] ?? null);
		
		if ($user) {
			$discArray['user'] = [
				'user_id' => $user->user_id,
				'nama' => $user->nama,
				'email' => $user->email ?? null,
			];
			$discArray['user_name'] = $user->nama;
		} else {
			$discArray['user'] = null;
			$discArray['user_name'] = null;
		}
		
		return $discArray;
	}

	/**
	 * Enrich discussion array with user data
	 */
	private function enrichArrayWithUserData(array $discArray): array
	{
		// If user data is already joined (from query), use it
		if (isset($discArray['user_name'])) {
			$discArray['user'] = [
				'user_id' => $discArray['user_user_id'] ?? $discArray['user_id'] ?? null,
				'nama' => $discArray['user_name'],
				'email' => $discArray['user_email'] ?? null,
			];
			// Ensure user_name is preserved for backward compatibility
			return $discArray;
		}
		
		// Otherwise, fetch user data
		$userModel = new UserModel();
		$userId = $discArray['user_id'] ?? null;
		
		if ($userId) {
			$user = $userModel->find($userId);
			if ($user) {
				$discArray['user'] = [
					'user_id' => $user->user_id,
					'nama' => $user->nama,
					'email' => $user->email ?? null,
				];
				$discArray['user_name'] = $user->nama;
			}
		}
		
		return $discArray;
	}

	/**
	 * Recursively enrich tree nodes with user data
	 */
	private function enrichTreeWithUserData(array $tree): array
	{
		$result = [];
		foreach ($tree as $node) {
			$enriched = $this->enrichArrayWithUserData($node);
			// Recursively enrich children if they exist
			if (isset($enriched['children']) && is_array($enriched['children'])) {
				$enriched['replies'] = $this->enrichTreeWithUserData($enriched['children']);
				// Keep both 'children' and 'replies' for compatibility
				unset($enriched['children']); // Remove children to avoid confusion, use replies instead
			}
			// Also check for replies directly
			if (isset($enriched['replies']) && is_array($enriched['replies'])) {
				$enriched['replies'] = $this->enrichTreeWithUserData($enriched['replies']);
			}
			$result[] = $enriched;
		}
		return $result;
	}
}


