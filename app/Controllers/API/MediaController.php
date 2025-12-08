<?php

namespace App\Controllers\API;

use App\Models\MediaModel;
use App\Models\ForumModel;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;

class MediaController extends BaseAPIController
{
	#[OAT\Post(
		path: "/media",
		tags: ["Media"],
		summary: "Upload media",
		security: [["bearerAuth" => []]],
		requestBody: new OAT\RequestBody(
			required: true,
			content: [
				new OAT\MediaType(
					mediaType: "multipart/form-data",
					schema: new OAT\Schema(
						type: "object",
						required: ["forum_id"],
						properties: [
							new OAT\Property(property: "forum_id", type: "integer"),
							new OAT\Property(property: "note_id", type: "integer"),
							new OAT\Property(property: "ref_id", type: "integer"),
							new OAT\Property(property: "file", type: "string", format: "binary")
						]
					)
				),
				new OAT\MediaType(
					mediaType: "application/json",
					schema: new OAT\Schema(
						type: "object",
						required: ["forum_id","file_url"],
						properties: [
							new OAT\Property(property: "forum_id", type: "integer"),
							new OAT\Property(property: "file_url", type: "string", format: "uri"),
							new OAT\Property(property: "note_id", type: "integer"),
							new OAT\Property(property: "ref_id", type: "integer")
						]
					)
				)
			]
		),
		responses: [
			new OAT\Response(response: 201, description: "Created"),
			new OAT\Response(response: 400, description: "Bad Request")
		]
	)]
	public function store()
	{
		// Check if request is JSON based on Content-Type
		$contentType = $this->request->getHeaderLine('Content-Type');
		$isJson = str_contains($contentType, 'application/json');
		$jsonData = $isJson ? ($this->request->getJSON(true) ?? []) : [];

		// Validate via PHP side to allow optional note/ref
		$forumId = (int) ($this->request->getVar('forum_id') ?? $jsonData['forum_id'] ?? 0);
		if ($forumId <= 0) {
			return $this->fail('forum_id is required', 400);
		}
		$current = $this->currentUser();
		$file    = $this->request->getFile('file');
		$fileUrl = null;
		if ($file && $file->isValid()) {
			$fileUrl = $this->moveUploadedFile($file, $forumId);
		} else {
			$body    = $isJson ? $jsonData : $this->request->getVar();
			$fileUrl = $body['file_url'] ?? null;
			if (! $fileUrl) {
				return $this->fail('No file or file_url provided', 400);
			}
		}

		$noteId = (int) ($this->request->getVar('note_id') ?? $jsonData['note_id'] ?? 0);
		$refId  = (int) ($this->request->getVar('ref_id') ?? $jsonData['ref_id'] ?? 0);

		$id = (new MediaModel())->insert([
			'user_id'  => $current->user_id,
			'forum_id' => $forumId,
			'note_id'  => $noteId ?: null,
			'ref_id'   => $refId ?: null,
			'file_url' => $fileUrl,
		], true);

		return $this->success((new MediaModel())->find($id), 'Created', null, 201);
	}

	#[OAT\Get(
		path: "/forums/{id}/media",
		tags: ["Media"],
		summary: "List forum media",
		security: [["bearerAuth" => []]],
		parameters: [
			new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "note_id", in: "query", required: false, schema: new OAT\Schema(type: "integer")),
			new OAT\Parameter(name: "ref_id", in: "query", required: false, schema: new OAT\Schema(type: "integer"))
		],
		responses: [new OAT\Response(response: 200, description: "OK")]
	)]
	public function index(int $forumId)
	{
		$noteId = $this->request->getGet('note_id');
		$refId  = $this->request->getGet('ref_id');
		$builder = (new MediaModel())->builder()->where('forum_id', $forumId)->orderBy('created_at', 'DESC');
		if ($noteId) {
			$builder->where('note_id', (int) $noteId);
		}
		if ($refId) {
			$builder->where('ref_id', (int) $refId);
		}
		$data = $builder->get()->getResult();
		return $this->success($data);
	}

	#[OAT\Get(
		path: "/media/{id}",
		tags: ["Media"],
		summary: "Show media",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "OK"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function show(int $mediaId)
	{
		$media = (new MediaModel())->find($mediaId);
		if (! $media) {
			return $this->fail('Not found', 404);
		}
		return $this->success($media);
	}

	#[OAT\Delete(
		path: "/media/{id}",
		tags: ["Media"],
		summary: "Delete media",
		security: [["bearerAuth" => []]],
		parameters: [new OAT\Parameter(name: "id", in: "path", required: true, schema: new OAT\Schema(type: "integer"))],
		responses: [
			new OAT\Response(response: 200, description: "Deleted"),
			new OAT\Response(response: 404, description: "Not found")
		]
	)]
	public function destroy(int $mediaId)
	{
		$model = new MediaModel();
		$media = $model->find($mediaId);
		if (! $media) {
			return $this->fail('Not found', 404);
		}
		$current = $this->currentUser();
		$forum   = (new ForumModel())->find($media->forum_id);
		$isOwner = (int) $media->user_id === (int) $current->user_id;
		$isAdmin = $forum && (int) $forum->admin_id === (int) $current->user_id;
		if (! $isOwner && ! $isAdmin) {
			return $this->fail('Forbidden', 403);
		}
		$model->delete($mediaId);
		return $this->success(['ok' => true], 'Deleted');
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


