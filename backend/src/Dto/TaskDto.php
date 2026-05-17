<?php

declare(strict_types=1);

namespace Ukolio\Dto;

use Ukolio\Model\Entity\Task;
use const DATE_ATOM;

final readonly class TaskDto
{
	/** @param list<TaskFieldValueDto> $fieldValues */
	public function __construct(
		public int $id,
		public int $projectId,
		public int $statusId,
		public string $name,
		public ?string $description,
		public string $priority,
		public ?string $dueDate,
		public int $position,
		public string $createdAt,
		public string $updatedAt,
		public array $fieldValues,
	) {
	}

	/** @param array<int, ?string> $fieldValues */
	public static function fromEntity(Task $task, array $fieldValues = []): self
	{
		$values = [];
		foreach ($fieldValues as $fieldId => $value) {
			$values[] = new TaskFieldValueDto(fieldId: $fieldId, value: $value);
		}

		return new self(
			id: $task->id,
			projectId: $task->project->id,
			statusId: $task->status->id,
			name: $task->name,
			description: $task->description,
			priority: $task->priority->value,
			dueDate: $task->dueDate?->format('Y-m-d'),
			position: $task->position,
			createdAt: $task->createdAt->format(DATE_ATOM),
			updatedAt: $task->updatedAt->format(DATE_ATOM),
			fieldValues: $values,
		);
	}
}
