<?php

namespace App\Services;

class DiscussionTreeService
{
	/**
	 * Build a nested tree from a flat list of discussions (each with discussion_id and parent_id).
	 *
	 * @param array<int, array<string, mixed>> $items
	 * @return array<int, array<string, mixed>>
	 */
	public function buildTree(array $items): array
	{
		$byId = [];
		foreach ($items as $item) {
			$item['children'] = [];
			$item['replies'] = [];
			$byId[$item['discussion_id']] = $item;
		}

		$roots = [];
		foreach ($byId as $id => &$node) {
			$parentId = $node['parent_id'] ?? null;
			if ($parentId && isset($byId[$parentId])) {
				$byId[$parentId]['children'][] = &$node;
				$byId[$parentId]['replies'][] = &$node;
			} else {
				$roots[] = &$node;
			}
		}
		unset($node); // break reference

		return $roots;
	}
}


