<?php

declare(strict_types=1);

namespace TaskManager\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use TaskManager\Mcp\Dto\McpProjectDto;
use TaskManager\Mcp\Dto\McpProjectListDto;
use TaskManager\Mcp\McpUserContextInterface;
use TaskManager\Service\Provider\ProjectProviderInterface;

final readonly class ProjectTools
{
    public function __construct(
        private McpUserContextInterface $userContext,
        private ProjectProviderInterface $projectProvider,
    ) {
    }

    /** List all projects belonging to the authenticated user. */
    #[McpTool(name: 'list_projects', description: 'List all projects for the user')]
    public function listProjects(): McpProjectListDto
    {
        $projects = [];
        foreach ($this->projectProvider->getProjects($this->userContext->getUser()) as $project) {
            $projects[] = McpProjectDto::fromEntity($project);
        }

        return new McpProjectListDto($projects);
    }

    /**
     * Find a project by case-insensitive name match. Returns null if not found.
     * Use this before creating a project to avoid duplicates.
     *
     * @param string $name Project name to search for (case-insensitive, exact match)
     */
    #[McpTool(name: 'find_project_by_name', description: 'Find a project by name (case-insensitive, exact match). Returns null if not found.')]
    public function findProjectByName(string $name): ?McpProjectDto
    {
        $needle = mb_strtolower($name);
        foreach ($this->projectProvider->getProjects($this->userContext->getUser()) as $project) {
            if (mb_strtolower($project->name) === $needle) {
                return McpProjectDto::fromEntity($project);
            }
        }

        return null;
    }

    /**
     * Get a single project by ID.
     *
     * @param int $projectId Project ID
     */
    #[McpTool(name: 'get_project', description: 'Get a single project by ID')]
    public function getProject(int $projectId): McpProjectDto
    {
        $project = $this->projectProvider->getProject($this->userContext->getUser(), $projectId);
        if ($project === null) {
            throw new RuntimeException(sprintf('Project %d not found.', $projectId));
        }

        return McpProjectDto::fromEntity($project);
    }

    /**
     * Create a new project. A default workflow with statuses "To Do", "In Progress", "Done"
     * is automatically created. Call find_project_by_name first to avoid duplicates.
     *
     * @param string $name Project name
     * @param string|null $description Optional project description
     */
    #[McpTool(name: 'create_project', description: 'Create a new project with the default To Do / In Progress / Done workflow')]
    public function createProject(string $name, ?string $description = null): McpProjectDto
    {
        $project = $this->projectProvider->createProject($this->userContext->getUser(), $name, $description);

        return McpProjectDto::fromEntity($project);
    }

    /**
     * Delete a project and all its tasks and workflow data.
     *
     * @param int $projectId Project ID
     */
    #[McpTool(name: 'delete_project', description: 'Delete a project (irreversible — removes all its tasks)')]
    public function deleteProject(int $projectId): string
    {
        $project = $this->projectProvider->getProject($this->userContext->getUser(), $projectId);
        if ($project === null) {
            throw new RuntimeException(sprintf('Project %d not found.', $projectId));
        }

        $this->projectProvider->deleteProject($project);

        return 'Project deleted.';
    }
}
