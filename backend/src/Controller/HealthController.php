<?php

declare(strict_types=1);

namespace Ukolio\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use MarekSkopal\Router\Attribute\RouteGet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ukolio\Route\Routes;

final readonly class HealthController
{
	#[RouteGet(Routes::Health->value)]
	public function actionHealth(ServerRequestInterface $request): ResponseInterface
	{
		return new JsonResponse(['status' => 'ok']);
	}
}
