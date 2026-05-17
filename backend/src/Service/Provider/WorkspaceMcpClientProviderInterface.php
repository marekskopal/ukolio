<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use Ukolio\Dto\WorkspaceMcpClientDto;
use Ukolio\Model\Entity\Workspace;

interface WorkspaceMcpClientProviderInterface
{
	/** @return list<WorkspaceMcpClientDto> */
	public function getClientsForWorkspace(Workspace $workspace): array;
}
