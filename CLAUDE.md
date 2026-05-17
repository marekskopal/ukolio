# Ukolio

Minimalistic Kanban task manager. Multi-user JWT auth. Architecture clones FinGather (`/Users/marek/web/www/fingather/`).

## Services

- `proxy/` — nginx reverse proxy (`/api/*` → backend, `/` → frontend)
- `backend/` — FrankenPHP + PHP 8.5 + `marekskopal/orm` + MariaDB
- `frontend/` — Angular 21 (standalone, signals) + Tailwind v4

## Domain

- `Workspace` (owner, name) — top-level container; users belong to one or more workspaces
- `WorkspaceUser` (workspace, user, role ∈ Owner/Admin/Member) — membership
- `Invitation` (workspace, inviter, email, tokenHash, role, expiresAt, acceptedAt?) — pending invites
- `User` (email, password, name, currentWorkspaceId?, systemRole ∈ User/SystemAdmin) — `currentWorkspaceId` is the active workspace used to scope data; `systemRole = SystemAdmin` grants global admin access
- `Project` (workspace, name, description) → has one `Workflow`, many `Tasks`
- `Workflow` (project, name) → has many `Status`
- `Status` (workflow, name, color, position, type ∈ start/normal/finish)
- `Task` (project, status, name, description [markdown], priority, dueDate, position)
- `Event` (author, type, metadata JSON, project?, workspaceId?, taskId?) — append-only audit log; `project`/`workspaceId` are nullable to allow workspace-level and admin events alongside project events

On sign-up a personal `Workspace` is auto-created and the user becomes its owner. New `Project` auto-seeds workflow `To Do → In Progress → Done`. Inviting a member sends an email via Symfony Mailer (SMTP env: `SMTP_HOST/PORT/USER/PASSWORD`, `EMAIL_FROM`); `mailpit` is wired in `docker-compose.yml` for local capture.

## Roles & permissions

Authorization is centralized in `Ukolio\Service\Auth\PermissionChecker` (interface + impl). Every mutating controller and the SystemAdmin endpoints route their decisions through it.

- **SystemAdmin** (`User.systemRole`): global; passes every `can*` check. Operates on workspaces they don't belong to via dedicated `/api/admin/*` endpoints (see `Ukolio\Controller\Admin\`) with a separate frontend at `/admin/users` and `/admin/workspaces`. Inside their own workspaces they act as a normal member of whatever role they hold.
- **Owner** (workspace‑scoped): one per workspace. Rename/delete workspace, manage all members, transfer ownership (sole way to assign a new Owner).
- **Admin** (workspace‑scoped): manage members (Member ↔ Admin), invite Members (cannot invite Admins or Owners), full CRUD on projects, statuses, and tasks. Cannot remove or demote the Owner.
- **Member** (workspace‑scoped): full CRUD on tasks; read‑only on projects, workflows, and statuses.

Ownership transfer (`POST /api/workspaces/{id}/transfer-ownership`) atomically updates `Workspace.owner` and both `WorkspaceUser` rows (old Owner becomes Admin). Workspace owner removal is blocked — transfer first.

The first SystemAdmin is seeded by the init migration as `admin@ukolio.com` / `admin`. **Rotate this password immediately in any non-dev environment.**

MCP tools remain scoped to `currentWorkspace` — sysadmins must use the web admin UI for cross-workspace management.

## i18n

- Backend: `Ukolio\Service\Translator\TranslatorService` mirrors fingather's pattern; loads `backend/translations/{en,cs}.json`. `EmailFactory` renders subject + section per `User.locale`; invitee's locale falls back to the inviter when they don't yet have an account.
- Frontend: `@ngx-translate/core` + `@ngx-translate/http-loader`. JSONs live in `frontend/src/i18n/{en,cs}.json`, served from `/i18n/` via `angular.json` assets. `LanguageService` initialises from `?lang=`, then localStorage, then `navigator.language`. `PATCH /api/current-user` syncs the user's choice to the backend so emails arrive in the right language. The topbar has a language switcher.

## Docker

```bash
docker compose up -d --build              # Full stack
docker compose --profile dev up -d        # +Adminer
make migrate                               # Apply migrations
```

## MCP server

Exposed at `POST/GET/DELETE /api/mcp` (Streamable HTTP transport, `mcp/sdk`). Mirrors `fingather/backend/src/Mcp/`. Sessions persisted to `MCP_SESSION_DIR` (defaults to `<tmp>/ukolio-mcp-sessions`).

Auth is **OAuth 2.1 with PKCE** (mirrors fingather/backend/src/OAuth/). Discovery endpoints:
- `GET /.well-known/oauth-authorization-server/api/mcp` — issuer/authz/token/registration URLs
- `GET /.well-known/oauth-protected-resource/api/mcp` — resource metadata
- `POST /api/mcp/oauth/register` — dynamic client registration (open)
- `POST /api/mcp/oauth/authorize` — user approval (requires user JWT)
- `POST /api/mcp/oauth/token` — code/refresh-token exchange (open)
- `GET /api/mcp/oauth/client-info` — display name lookup (open)

401 responses include `WWW-Authenticate: Bearer resource_metadata="…"` per RFC 9728 so MCP clients can auto-discover. PKCE `S256` only; no client secret. Access token lifetime 1h, refresh 30d. Storage: `oauth_clients` and `oauth_authorizations` tables (tokens stored as SHA-256 hashes).

Tools live in `backend/src/Mcp/Tool/` (auto-discovered by basePath/scanDirs):
- `ProjectTools` — list/find/get/create/delete projects
- `WorkflowTools` — list/find statuses for a project's workflow
- `TaskTools` — list/find/get/create/update/move/delete tasks (move accepts `statusId` or `statusName`)

Designed for AI-agent-driven flows; the frontend stays for human overview.

## Testing

```bash
make test           # All tests (backend + frontend)
make test-backend   # PHPUnit
make test-frontend  # Vitest
```

## Linting

Backend uses PHPStan at `max` level (with `bleedingEdge.neon` + strict/deprecation/phpunit/shipmonk rules + cognitive-complexity + unused-public) and PHPCS with the slevomat ruleset (ported from fingather; tabs, single-line method signatures ≤140 chars). Custom PHPStan extension `Ukolio\PhpStan\OrmReadWritePropertiesExtension` marks `Column`/`ManyToOne`/`ColumnEnum`-attributed properties as ORM-managed (always read, always written, always initialized).

```bash
make lint           # PHPStan + PHPCS
make lint-fix       # phpcbf auto-fix
```
