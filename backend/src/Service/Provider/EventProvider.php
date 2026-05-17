<?php

declare(strict_types=1);

namespace Ukolio\Service\Provider;

use DateTimeImmutable;
use Iterator;
use Ukolio\Model\Entity\Enum\EventTypeEnum;
use Ukolio\Model\Entity\Event;
use Ukolio\Model\Entity\Project;
use Ukolio\Model\Entity\User;
use Ukolio\Model\Entity\Workspace;
use Ukolio\Model\Repository\EventRepository;
use const JSON_THROW_ON_ERROR;

final readonly class EventProvider implements EventProviderInterface
{
	public function __construct(private EventRepository $eventRepository)
	{
	}

	/** @param array<string,mixed> $metadata */
	public function recordEvent(User $author, Project $project, EventTypeEnum $type, array $metadata, ?int $taskId = null): Event
	{
		$now = new DateTimeImmutable();
		$event = new Event(
			author: $author,
			type: $type,
			metadata: json_encode($metadata, JSON_THROW_ON_ERROR),
			project: $project,
			workspaceId: $project->workspace->id,
			taskId: $taskId,
		);
		$event->createdAt = $now;
		$event->updatedAt = $now;

		$this->eventRepository->persist($event);

		return $event;
	}

	/** @param array<string,mixed> $metadata */
	public function recordWorkspaceEvent(User $author, ?Workspace $workspace, EventTypeEnum $type, array $metadata): Event
	{
		$now = new DateTimeImmutable();
		$event = new Event(
			author: $author,
			type: $type,
			metadata: json_encode($metadata, JSON_THROW_ON_ERROR),
			project: null,
			workspaceId: $workspace?->id,
		);
		$event->createdAt = $now;
		$event->updatedAt = $now;

		$this->eventRepository->persist($event);

		return $event;
	}

	/** @return Iterator<Event> */
	public function getEvents(Project $project, int $limit = 100, int $offset = 0): Iterator
	{
		return $this->eventRepository->findByProject($project->id, $limit, $offset);
	}
}
