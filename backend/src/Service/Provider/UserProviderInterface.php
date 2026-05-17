<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use Ukolio\Model\Entity\User;

interface UserProviderInterface
{
	public function getUser(int $userId): ?User;

	public function getUserByEmail(string $email): ?User;

	public function createUser(string $email, string $password, string $name): User;
}
