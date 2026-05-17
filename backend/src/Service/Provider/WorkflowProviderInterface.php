<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use Ukolio\Model\Entity\Project;
use Ukolio\Model\Entity\Workflow;

interface WorkflowProviderInterface
{
	public function getWorkflow(int $workflowId): ?Workflow;

	public function getWorkflowByProject(Project $project): ?Workflow;

	public function createDefaultWorkflow(Project $project): Workflow;

	public function updateWorkflow(Workflow $workflow, string $name): Workflow;
}
