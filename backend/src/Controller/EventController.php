<?php

declare(strict_types=1);

namespace TaskManager\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use MarekSkopal\Router\Attribute\RouteGet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TaskManager\Dto\EventDto;
use TaskManager\Model\Entity\Event;
use TaskManager\Response\NotFoundResponse;
use TaskManager\Route\Routes;
use TaskManager\Service\Provider\EventProviderInterface;
use TaskManager\Service\Provider\ProjectProviderInterface;
use TaskManager\Service\Request\RequestServiceInterface;

final readonly class EventController
{
	public function __construct(
		private ProjectProviderInterface $projectProvider,
		private EventProviderInterface $eventProvider,
		private RequestServiceInterface $requestService,
	) {
	}

	#[RouteGet(Routes::ProjectEvents->value)]
	public function actionGetEvents(ServerRequestInterface $request, int $projectId): ResponseInterface
	{
		$user = $this->requestService->getUser($request);
		$project = $this->projectProvider->getProject($user, $projectId);
		if ($project === null) {
			return new NotFoundResponse('Project with id "' . $projectId . '" was not found.');
		}

		$query = $request->getQueryParams();
		$limit = is_numeric($query['limit'] ?? null) ? (int) $query['limit'] : 100;
		$offset = is_numeric($query['offset'] ?? null) ? (int) $query['offset'] : 0;

		$events = array_map(
			fn (Event $e): EventDto => EventDto::fromEntity($e),
			iterator_to_array($this->eventProvider->getEvents($project, $limit, $offset), false),
		);

		return new JsonResponse($events);
	}
}
