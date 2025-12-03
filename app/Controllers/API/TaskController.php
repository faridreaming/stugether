<?php

namespace App\Controllers\API;

use App\Models\KanbanModel;
use App\Models\ForumModel;
use App\Models\MediaModel;
use CodeIgniter\Files\File;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;

class TaskController extends BaseAPIController
{
	#[OAT\Post(
		path: "/forums/{id}/tasks",
		tags: ["Tasks"],
		summary: "Create task in forum",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: true,
			content: new OAT\JsonContent(
				required: ["judul"],
				properties: [
					new OAT\Property(property: "judul", type: "string"),
					new OAT\Property(property: "deskripsi", type: "string"),
					new OAT\Property(property: "tenggat_waktu", type: "string", format: "date-time"),
					new OAT\Property(property: "file_url", type: "string", format: "uri")
				]
			)
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
public function store(int $forumId)
{
    $rules = config('Validation')->taskStore;
    if (! $this->validate($rules)) {
        return $this->fail(implode('; ', $this->validator->getErrors()), 400);
    }

    // JANGAN pakai getJSON(true) langsung, karena Android kirim form-urlencoded
    $data = $this->request->getPost(); // ambil dari form (Android)

    // Optional: kalau suatu saat ada client lain kirim JSON, tetap support
    if ($data === [] || $data === null) {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            $data = $json;
        }
    }

    $current = $this->currentUser();
    $model   = new KanbanModel();

    $taskId  = $model->insert([
        'forum_id'      => $forumId,
        'judul'         => $data['judul'] ?? null,
        'deskripsi'     => $data['deskripsi'] ?? null,
        'tenggat_waktu' => $data['tenggat_waktu'] ?? null,
        'file_url'      => $data['file_url'] ?? null,
        'status'        => 'todo',
        'created_by'    => $current->user_id,
    ], true);

    $task = $model->find($taskId);
    return $this->success($task, 'Created', null, 201);
}

	#[OAT\Get(
		path: "/forums/{id}/tasks",
		tags: ["Tasks"],
		summary: "List tasks in forum",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "status", in: "query", required: false, schema: new OAT\Schema(type: "string", enum: ["todo","doing","done"])),
			new OAT\Parameter(name: "q", in: "query", required: false, schema: new OAT\Schema(type: "string")),
			new OAT\Parameter(name: "sort", in: "query", required: false, schema: new OAT\Schema(type: "string", enum: ["deadline","created_at"])),
			new OAT\Parameter(name: "page", in: "query", required: false, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "per_page", in: "query", required: false, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
		public function index(int $forumId)
	{
		$status    = $this->request->getGet('status');
		$q         = trim((string) ($this->request->getGet('q') ?? ''));
		$sort      = $this->request->getGet('sort') ?? 'created_at';
		$page      = max(1, (int) ($this->request->getGet('page') ?? 1));
		$perPage   = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 10)));

		$builder = (new KanbanModel())->builder()->where('forum_id', $forumId);

		if (in_array($status, ['todo', 'doing', 'done'], true)) {
			$builder->where('status', $status);
		}

		if ($q !== '') {
			$builder->groupStart()
				->like('judul', $q)
				->orLike('deskripsi', $q)
			->groupEnd();
		}

		$sortMap = ['deadline' => 'tenggat_waktu', 'created_at' => 'created_at'];
		$orderBy = $sortMap[$sort] ?? 'created_at';
		$builder->orderBy($orderBy, 'DESC');

		$total   = (clone $builder)->countAllResults(false);
		$data    = $builder->get(($page - 1) * $perPage, $perPage)->getResult();
		$meta    = service('paginationSvc')->buildMeta($page, $perPage, $total);

		return $this->success($data, null, $meta);
	}

	#[OAT\Get(
		path: "/tasks/{id}",
		tags: ["Tasks"],
		summary: "Show task",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "OK"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function show(int $taskId)
	{
		$task = (new KanbanModel())->find($taskId);
		if (! $task) {
			return $this->fail('Task not found', 404);
		}
		return $this->success($task);
	}

	#[OAT\Patch(
		path: "/tasks/{id}",
		tags: ["Tasks"],
		summary: "Update task",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: false,
			content: new OAT\JsonContent(
				properties: [
					new OAT\Property(property: "judul", type: "string"),
					new OAT\Property(property: "deskripsi", type: "string"),
					new OAT\Property(property: "tenggat_waktu", type: "string", format: "date-time"),
					new OAT\Property(property: "status", type: "string", enum: ["todo","doing","done"]),
					new OAT\Property(property: "file_url", type: "string", format: "uri")
				]
			)
		),
		responses: [
			new OAT\Response(response: 200, description: "Updated"),
			new OAT\Response(response: 400, description: "Bad Request"),
			new OAT\Response(response: 403, description: "Forbidden")
		]
	)]
public function update(int $id)
{
    // JANGAN pakai getJSON(true) langsung, karena Android kirim form-urlencoded
    // Coba getJSON dulu, kalau null berarti form-urlencoded, pakai getPost()
    $data = $this->request->getJSON(true);
    if ($data === null) {
        $data = $this->request->getPost();
    }

    $current = $this->currentUser();
    if ($current === null) {
        return $this->response
            ->setStatusCode(401)
            ->setJSON(['message' => 'User tidak terautentikasi']);
    }

    $model = new KanbanModel();
    $task = $model->find($id);

    if ($task === null) {
        return $this->fail('Task tidak ditemukan', 404);
    }

    // Update hanya field yang dikirim
    $updateData = [];
    if (isset($data['judul'])) {
        $updateData['judul'] = $data['judul'];
    }
    if (isset($data['deskripsi'])) {
        $updateData['deskripsi'] = $data['deskripsi'];
    }
    if (isset($data['status'])) {
        $updateData['status'] = $data['status'];
    }
    if (isset($data['tenggat_waktu'])) {
        $updateData['tenggat_waktu'] = $data['tenggat_waktu'];
    }
    if (isset($data['file_url'])) {
        $updateData['file_url'] = $data['file_url'];
    }

    if (empty($updateData)) {
        return $this->fail('Tidak ada data yang diupdate', 400);
    }

    $model->update($id, $updateData);
    $updated = $model->find($id);

    return $this->success($updated, 'Updated');
}

	#[OAT\Delete(
		path: "/tasks/{id}",
		tags: ["Tasks"],
		summary: "Delete task",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "Deleted"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function destroy(int $taskId)
	{
		$model = new KanbanModel();
		$task  = $model->find($taskId);
		if (! $task) {
			return $this->fail('Task not found', 404);
		}
		if (! $this->canManageTask($task->forum_id, $task->created_by)) {
			return $this->fail('Forbidden', 403);
		}
		$model->delete($taskId);
		return $this->success(['ok' => true], 'Deleted');
	}

	#[OAT\Post(
		path: "/tasks/{id}/attachments",
		tags: ["Tasks"],
		summary: "Attach file or link to task",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		requestBody: new OAT\RequestBody(
			required: true,
			content: [
				new OAT\MediaType(
					mediaType: "multipart/form-data",
					schema: new OAT\Schema(
						type: "object",
						properties: [
							new OAT\Property(property: "file", type: "string", format: "binary"),
							new OAT\Property(property: "file_url", type: "string", format: "uri")
						]
					)
				),
				new OAT\MediaType(
					mediaType: "application/json",
					schema: new OAT\Schema(
						type: "object",
						properties: [new OAT\Property(property: "file_url", type: "string", format: "uri")]
					)
				)
			]
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
	public function attach(int $taskId)
	{
		$task = (new KanbanModel())->find($taskId);
		if (! $task) {
			return $this->fail('Task not found', 404);
		}
		$current = $this->currentUser();
		$mediaModel = new MediaModel();

		$file = $this->request->getFile('file');
		$fileUrl = null;
		if ($file && $file->isValid()) {
			$fileUrl = $this->moveUploadedFile($file, (int) $task->forum_id);
		} else {
			$body = $this->request->getJSON(true) ?? $this->request->getPost();
			$fileUrl = $body['file_url'] ?? null;
			if (! $fileUrl) {
				return $this->fail('No file or file_url provided', 400);
			}
		}

		$mediaId = $mediaModel->insert([
			'user_id'  => $current->user_id,
			'forum_id' => $task->forum_id,
			'ref_id'   => $taskId,
			'file_url' => $fileUrl,
		], true);

		return $this->success($mediaModel->find($mediaId), 'Attached', null, 201);
	}

	private function canManageTask(int $forumId, int $createdBy): bool
	{
		$current = $this->currentUser();
		if (! $current) {
			return false;
		}
		if ((int) $createdBy === (int) $current->user_id) {
			return true;
		}
		$forum = (new ForumModel())->find($forumId);
		return $forum && (int) $forum->admin_id === (int) $current->user_id;
	}

	private function moveUploadedFile(\CodeIgniter\HTTP\Files\UploadedFile $file, int $forumId): string
	{
		$sanitized = $this->sanitizeFilename($file->getClientName());
		$subdir = 'uploads/forums/' . $forumId . '/' . gmdate('Y/m');
		$targetDir = FCPATH . $subdir;
		if (! is_dir($targetDir)) {
			mkdir($targetDir, 0775, true);
		}
		$newName = uniqid('', true) . '_' . $sanitized;
		$file->move($targetDir, $newName, true);
		return base_url($subdir . '/' . $newName);
	}

	private function sanitizeFilename(string $name): string
	{
		$name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
		return trim($name, '_');
	}
}


