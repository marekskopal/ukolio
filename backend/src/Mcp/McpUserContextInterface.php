<?php

declare(strict_types=1);

namespace TaskManager\Mcp;

use TaskManager\Model\Entity\User;

interface McpUserContextInterface
{
    public function setUser(User $user): void;

    public function getUser(): User;
}
