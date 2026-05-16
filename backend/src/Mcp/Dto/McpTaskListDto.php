<?php

declare(strict_types=1);

namespace TaskManager\Mcp\Dto;

final readonly class McpTaskListDto
{
    /** @param list<McpTaskDto> $tasks */
    public function __construct(public array $tasks)
    {
    }
}
