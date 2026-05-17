<?php

declare(strict_types=1);

namespace Ukolio\Mcp\Dto;

use Ukolio\Model\Entity\Task;

final readonly class McpTaskDto
{
	public function __construct(
		public int $id,
		public int $projectId,
		public int $statusId,
		public string $statusName,
		public string $name,
		public ?string $description,
		public string $priority,
		public ?string $dueDate,
		public int $position,
	) {
	}

	public static function fromEntity(Task $task): self
	{
		return new self(
			id: $task->id,
			projectId: $task->project->id,
			statusId: $task->status->id,
			statusName: $task->status->name,
			name: $task->name,
			description: $task->description,
			priority: $task->priority->value,
			dueDate: $task->dueDate?->format('Y-m-d'),
			position: $task->position,
		);
	}
}
