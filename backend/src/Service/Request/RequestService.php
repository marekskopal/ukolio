<?php

declare(strict_types=1);

namespace Ukolio\Service\Request;

use Nette\Utils\Json;
use Psr\Http\Message\ServerRequestInterface;
use Ukolio\Dto\ArrayFactoryInterface;
use Ukolio\Middleware\AuthorizationMiddleware;
use Ukolio\Model\Entity\User;

final readonly class RequestService implements RequestServiceInterface
{
	public function getUser(ServerRequestInterface $request): User
	{
		$user = $request->getAttribute(AuthorizationMiddleware::AttributeUser);
		assert($user instanceof User);
		return $user;
	}

	/** @return array<mixed> */
	public function getRequestBody(ServerRequestInterface $request): array
	{
		/** @var array<mixed> $decodedBody */
		$decodedBody = Json::decode($request->getBody()->getContents(), forceArrays: true);
		return $decodedBody;
	}

	/**
	 * @param class-string<T> $dtoClass
	 * @return T
	 * @template T of ArrayFactoryInterface
	 */
	public function getRequestBodyDto(ServerRequestInterface $request, string $dtoClass): object
	{
		return $dtoClass::fromArray($this->getRequestBody($request));
	}
}
