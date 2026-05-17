# Ukolio

Minimalistic, multi-tenant Kanban task manager designed to be driven primarily
by AI agents over MCP, with a lightweight web UI for human overview.
Architecture mirrors [FinGather](https://github.com/marekskopal/fingather).

## Stack

| Layer    | Tech |
|----------|------|
| Proxy    | nginx |
| Frontend | Angular 21 (standalone components + signals), SCSS, ngx-translate |
| Backend  | FrankenPHP, PHP 8.5, [`marekskopal/orm`](https://github.com/marekskopal/orm), [`marekskopal/router`](https://github.com/marekskopal/router), Symfony Mailer |
| Database | MariaDB 11.4 |
| Mail     | Mailpit (dev) / any SMTP (prod) |
| Auth     | JWT for web, OAuth 2.1 + PKCE for MCP |

## Quick start

```bash
cp .env.example .env             # adjust ports / secrets as needed
make up                          # build & start the full stack
make migrate                     # run database migrations
open http://localhost:4300/      # default proxy port
```

A SystemAdmin is seeded automatically: `admin@ukolio.com` / `admin`.
**Rotate this password before exposing the instance.**

Anyone can also sign up at `/sign-up`; the first registration auto-creates a
personal workspace.

## Domain

- **Workspace** — top-level tenant; users belong to one or more workspaces.
- **WorkspaceUser** — membership with a role (`Owner` / `Admin` / `Member`).
- **Invitation** — pending email invite, signed token, expires after 7 days.
- **User** — `email`, `password`, `name`, `currentWorkspaceId`, `systemRole`
  (`User` / `SystemAdmin`). `currentWorkspaceId` scopes every web request.
- **Project** — workspace-scoped; auto-seeds a `Workflow` of
  `To Do → In Progress → Done` on creation.
- **Workflow** → **Status** (`Start` / `Normal` / `Finish`, with name + color +
  position).
- **Task** — project-scoped, lives in a Status, has name / Markdown
  description / priority / due date / position / custom-field values.
- **Field / ProjectField / TaskFieldValue** — per-workspace catalog of custom
  fields (`Text` / `Textarea` / `Select` / `Version` semver). Projects opt-in
  to fields; their values are persisted per task.
- **Event** — append-only audit log keyed to workspace / project / task.

## Roles & permissions

Authorization is centralized in `Ukolio\Service\Auth\PermissionChecker`. Every
mutating controller routes through it.

- **SystemAdmin** — global; passes every `can*` check. Operates on workspaces
  they don't belong to via `/api/admin/*` endpoints (separate frontend at
  `/admin/users` and `/admin/workspaces`). Inside their own workspaces they act
  as a normal member.
- **Owner** — workspace-scoped, one per workspace. Renames / deletes the
  workspace, manages all members, transfers ownership.
- **Admin** — workspace-scoped. Manages members (Member ↔ Admin), invites
  Members, full CRUD on projects, workflows, statuses, custom fields, tasks.
- **Member** — workspace-scoped. Full CRUD on tasks; read-only on the rest.

Ownership transfer (`POST /api/workspaces/{id}/transfer-ownership`) is atomic
— the old Owner becomes Admin. Workspace owner removal is blocked; transfer
first.

## Web UI

| Route | Purpose |
|-------|---------|
| `/login`, `/sign-up`, `/invitations/accept` | Public auth pages |
| `/projects` | Project list (workspace-scoped) |
| `/projects/:id/board` | Kanban board with drag-and-drop and task drawer |
| `/projects/:id/workflow` | Workflow editor |
| `/projects/:id/events` | Project activity log |
| `/tasks` | Workspace-wide task grid — search, multi-status filter, sortable columns, pagination |
| `/workspaces` | Membership + invitations |
| `/admin/users`, `/admin/workspaces` | SystemAdmin tools |

i18n: EN + CS, switchable from the topbar. Choice is persisted to the user via
`PATCH /api/current-user` so transactional emails arrive in the right
language. Frontend uses `@ngx-translate/core`; backend renders emails via
`TranslatorService` loading `backend/translations/{en,cs}.json`.

## MCP server

Exposed at `POST/GET/DELETE /api/mcp` over Streamable HTTP (using `mcp/sdk`).
Sessions persist to `MCP_SESSION_DIR` (defaults to `<tmp>/ukolio-mcp-sessions`).

**Auth: OAuth 2.1 + PKCE** (mirrors fingather/backend/src/OAuth/). Discovery
endpoints:

- `GET /.well-known/oauth-authorization-server/api/mcp`
- `GET /.well-known/oauth-protected-resource/api/mcp`
- `POST /api/mcp/oauth/register` — dynamic client registration (open)
- `POST /api/mcp/oauth/authorize` — user approval (requires user JWT)
- `POST /api/mcp/oauth/token` — code/refresh-token exchange (open)
- `GET /api/mcp/oauth/client-info` — display name lookup (open)

401 responses include `WWW-Authenticate: Bearer resource_metadata="…"` per
RFC 9728. PKCE `S256` only; no client secret. Access token TTL 1 h, refresh
30 d. Tokens are stored as SHA-256 hashes in `oauth_clients` and
`oauth_authorizations`.

Auto-discovered tools (`backend/src/Mcp/Tool/`):

- `ProjectTools` — list / find / get / create / delete projects.
- `WorkflowTools` — list / find statuses for a project's workflow.
- `TaskTools` — list / find / get / create / update / move / delete tasks
  (move accepts `statusId` *or* `statusName`).
- `FieldTools` — manage the workspace's custom-field catalog and per-project
  attachments.

All MCP tools are scoped to the calling user's `currentWorkspace`. SystemAdmins
must use the web admin UI for cross-workspace work.

## Project layout

```
proxy/      nginx reverse proxy (/api/* → backend, /* → frontend)
backend/    FrankenPHP + PHP 8.5
  src/
    Controller/       HTTP endpoints (attribute-routed via marekskopal/router)
    Dto/              Wire-level DTOs for requests / responses
    Model/Entity/     ORM entities + enums
    Model/Repository/ Repository classes (+ Enum/ for query enums)
    Service/          Providers, auth, request, translator, etc.
    Mcp/              MCP tools, DTOs, user context
    OAuth/            OAuth 2.1 + PKCE flow for MCP clients
    Middleware/       Authorization, CORS, error handler
    PhpStan/          Custom PHPStan extension for ORM property semantics
  migrations/         marekskopal/orm-migrations
  translations/       en.json, cs.json — backend (email) strings
  tests/              PHPUnit
frontend/   Angular 21 SPA
  src/app/
    authentication/   Login, sign-up
    projects/         Project list + CRUD
    board/            Kanban board + task drawer
    workflow-editor/  Workflow + status editing
    tasks/            Workspace-wide tasks grid
    events/           Activity log
    workspaces/       Workspace management, invitations
    admin/            SystemAdmin pages
    invitations/      Invitation accept flow
    services/         API clients
    models/           TypeScript interfaces
    shared/components/ Layout, alert, pagination
    core/             Guards, interceptors
  src/i18n/           en.json, cs.json — frontend strings
  src/styles/         SCSS design tokens + mixins
log/        Backend log mount
```

## Common commands

| Command | What it does |
|---------|--------------|
| `make up` | Build & start the full stack |
| `make down` | Stop the stack |
| `make logs` | Tail container logs |
| `make migrate` | Run database migrations |
| `make test` | All tests (backend + frontend) |
| `make test-backend` | PHPUnit only |
| `make test-frontend` | Vitest only |
| `make lint` | PHPStan (max) + PHPCS |
| `make lint-fix` | phpcbf auto-fix |
| `make install` | `composer install` + `pnpm install` on host |
| `docker compose --profile dev up -d` | Stack + Adminer at the proxy |

### Direct frontend commands

From `frontend/`:

```bash
pnpm start         # ng serve (proxies API via dev server config)
pnpm build         # production build
pnpm test          # vitest run
pnpm run lint      # ng lint --max-warnings=0
```

### Direct backend commands

From `backend/`:

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/phpcs
vendor/bin/phpcbf
php bin/console migration:run
```

## Linting

- **Backend**: PHPStan at `max` level with `bleedingEdge.neon` + strict /
  deprecation / phpunit / shipmonk / cognitive-complexity / unused-public
  rules. PHPCS uses the slevomat ruleset (tabs, single-line method signatures
  ≤ 140 chars). A custom PHPStan extension
  (`Ukolio\PhpStan\OrmReadWritePropertiesExtension`) marks `#[Column]` /
  `#[ManyToOne]` / `#[ColumnEnum]` properties as ORM-managed (always read,
  always written, always initialized).
- **Frontend**: angular-eslint + `@typescript-eslint`, `simple-import-sort`,
  `unused-imports`. `pnpm run lint` enforces zero warnings.

## Environment variables

| Variable | Purpose |
|----------|---------|
| `PROXY_PORT` | Host port the nginx proxy binds to |
| `MYSQL_*` | Database credentials |
| `AUTHORIZATION_TOKEN_KEY` | 32-char secret used to sign JWTs |
| `BACKEND_FRANKENPHP_WORKERS` | FrankenPHP worker count |
| `BACKEND_CORS_ALLOWED_ORIGIN` | CORS allow-list (default `*` for dev) |
| `BACKEND_LOG_LEVEL` | `development` / `production` |
| `SMTP_HOST` / `SMTP_PORT` / `SMTP_USER` / `SMTP_PASSWORD` | Outbound mail |
| `EMAIL_FROM` | Sender used by invitation emails |
| `APP_URL` | Base URL embedded in email links |

`mailpit` is wired into `docker-compose.yml` so local invitations are captured
at the SMTP layer instead of being sent.
