<?php

declare(strict_types=1);

namespace Ukolio\Model\Repository;

use Iterator;
use MarekSkopal\ORM\Query\Select;
use MarekSkopal\ORM\Repository\AbstractRepository;
use Ukolio\Model\Entity\Enum\StatusTypeEnum;
use Ukolio\Model\Entity\Task;
use Ukolio\Model\Repository\Enum\OrderDirectionEnum;
use Ukolio\Model\Repository\Enum\TaskOrderByEnum;

/** @extends AbstractRepository<Task> */
final class TaskRepository extends AbstractRepository
{
	public function findById(int $taskId): ?Task
	{
		return $this->findOne(['id' => $taskId]);
	}

	/** @return Iterator<Task> */
	public function findByProject(int $projectId): Iterator
	{
		return $this->select()
			->where(['project_id' => $projectId])
			->orderBy('status_id', 'ASC')
			->orderBy('position', 'ASC')
			->fetchAll();
	}

	/** @return Iterator<Task> */
	public function findByStatus(int $statusId): Iterator
	{
		return $this->select()
			->where(['status_id' => $statusId])
			->orderBy('position', 'ASC')
			->fetchAll();
	}

	/**
	 * @param list<int>|null $statusIds
	 * @return Iterator<Task>
	 */
	public function findInWorkspace(
		int $workspaceId,
		int $limit,
		int $offset,
		TaskOrderByEnum $orderBy,
		OrderDirectionEnum $direction,
		?string $search,
		?array $statusIds,
		bool $onlyActive,
	): Iterator {
		$select = $this->buildWorkspaceSelect($workspaceId, $search, $statusIds, $onlyActive);

		$select->orderBy($orderBy->value, $direction->value);

		// Secondary deterministic order so equal-key rows stay stable across pages.
		if ($orderBy !== TaskOrderByEnum::CreatedAt) {
			$select->orderBy('created_at', OrderDirectionEnum::Desc->value);
		}
		$select->orderBy('id', OrderDirectionEnum::Desc->value);

		return $select
			->limit($limit)
			->offset($offset)
			->fetchAll();
	}

	/** @param list<int>|null $statusIds */
	public function countInWorkspace(int $workspaceId, ?string $search, ?array $statusIds, bool $onlyActive,): int
	{
		return $this->buildWorkspaceSelect($workspaceId, $search, $statusIds, $onlyActive)->count();
	}

	/**
	 * @param list<int>|null $statusIds
	 * @return Select<Task>
	 */
	private function buildWorkspaceSelect(int $workspaceId, ?string $search, ?array $statusIds, bool $onlyActive,): Select
	{
		$select = $this->select()
			->where(['project.workspace_id' => $workspaceId]);

		if ($search !== null && $search !== '') {
			$select->where(['name', 'LIKE', '%' . $search . '%']);
		}
		if ($statusIds !== null && $statusIds !== []) {
			$select->where(['status_id', 'IN', $statusIds]);
		}
		if ($onlyActive) {
			$select->where(['status.type', '!=', StatusTypeEnum::Finish]);
		}

		return $select;
	}
}
