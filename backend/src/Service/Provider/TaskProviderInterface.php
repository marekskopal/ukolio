<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use DateTimeImmutable;
use Iterator;
use Ukolio\Model\Entity\Enum\TaskPriorityEnum;
use Ukolio\Model\Entity\Project;
use Ukolio\Model\Entity\Status;
use Ukolio\Model\Entity\Task;
use Ukolio\Model\Entity\User;

interface TaskProviderInterface
{
	public function getTask(int $taskId): ?Task;

	/** @return Iterator<Task> */
	public function getTasksByProject(Project $project): Iterator;

	public function createTask(
		User $author,
		Project $project,
		Status $status,
		string $name,
		?string $description,
		TaskPriorityEnum $priority,
		?DateTimeImmutable $dueDate,
	): Task;

	public function updateTask(
		User $author,
		Task $task,
		string $name,
		?string $description,
		TaskPriorityEnum $priority,
		?DateTimeImmutable $dueDate,
		Status $status,
	): Task;

	public function moveTask(User $author, Task $task, Status $newStatus, int $newPosition): Task;

	public function deleteTask(User $author, Task $task): void;
}
