<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use Ukolio\Model\Entity\Task;
use Ukolio\Model\Entity\User;
use Ukolio\Model\Entity\Workspace;
use Ukolio\Model\Repository\ProjectRepository;
use Ukolio\Model\Repository\TaskRepository;

final readonly class TaskCodeResolver implements TaskCodeResolverInterface
{
	public function __construct(
		private ProjectRepository $projectRepository,
		private TaskRepository $taskRepository,
		private WorkspaceProviderInterface $workspaceProvider,
	) {
	}

	public function resolveForUser(User $user, string $idOrCode): ?Task
	{
		$workspace = $this->workspaceProvider->getCurrentWorkspace($user);
		if ($workspace === null) {
			return null;
		}
		$task = $this->resolve($workspace, $idOrCode);
		if ($task === null || !$this->workspaceProvider->isMember($user, $task->project->workspace)) {
			return null;
		}
		return $task;
	}

	public function findByCode(Workspace $workspace, string $code): ?Task
	{
		if (preg_match('/^([A-Z0-9]+)-(\d+)$/', strtoupper(trim($code)), $matches) !== 1) {
			return null;
		}
		$project = $this->projectRepository->findByWorkspaceAndPrefix($workspace->id, $matches[1]);
		return $project === null ? null : $this->taskRepository->findByProjectAndSequence($project->id, (int) $matches[2]);
	}

	public function resolve(Workspace $workspace, string $idOrCode): ?Task
	{
		if (ctype_digit($idOrCode)) {
			return $this->taskRepository->findById((int) $idOrCode);
		}
		return $this->findByCode($workspace, $idOrCode);
	}
}
