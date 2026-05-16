<?php

declare(strict_types=1);

namespace TaskManager\Mcp\Dto;

final readonly class McpStatusListDto
{
    /** @param list<McpStatusDto> $statuses */
    public function __construct(public array $statuses)
    {
    }
}
