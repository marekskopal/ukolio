<?php

declare(strict_types=1);

namespace TaskManager\Controller;

use MarekSkopal\Router\Attribute\RouteDelete;
use MarekSkopal\Router\Attribute\RouteGet;
use MarekSkopal\Router\Attribute\RoutePost;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TaskManager\Mcp\McpUserContextInterface;
use TaskManager\Mcp\Server\TaskManagerServer;
use TaskManager\Response\ErrorResponse;
use TaskManager\Route\Routes;
use TaskManager\Service\Authentication\AuthenticationServiceInterface;

final readonly class McpController
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private McpUserContextInterface $userContext,
        private TaskManagerServer $server,
    ) {
    }

    #[RouteGet(Routes::Mcp->value)]
    public function actionGetMcp(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handleMcp($request);
    }

    #[RoutePost(Routes::Mcp->value)]
    public function actionPostMcp(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handleMcp($request);
    }

    #[RouteDelete(Routes::Mcp->value)]
    public function actionDeleteMcp(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handleMcp($request);
    }

    private function handleMcp(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return new ErrorResponse('Missing or invalid Authorization header. Expected: Bearer <access_token>', 401);
        }

        try {
            $user = $this->authenticationService->validateAccessToken($token);
        } catch (RuntimeException) {
            return new ErrorResponse('Invalid or expired access token.', 401);
        }

        $this->userContext->setUser($user);

        $sessionStore = new FileSessionStore($this->sessionDirectory());
        $mcpServer = $this->server->build($sessionStore);
        $transport = new StreamableHttpTransport($request);

        return $mcpServer->run($transport);
    }

    private function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        return $token !== '' ? $token : null;
    }

    private function sessionDirectory(): string
    {
        $dir = (string) getenv('MCP_SESSION_DIR');
        if ($dir === '') {
            $dir = sys_get_temp_dir() . '/task-manager-mcp-sessions';
        }

        return $dir;
    }
}
