.PHONY: up down logs build migrate test test-backend test-frontend lint lint-backend lint-fix install

## Start stack
up:
	docker compose up -d --build

## Stop stack
down:
	docker compose down

## Tail logs
logs:
	docker compose logs -f

## Rebuild without cache
build:
	docker compose build --no-cache

## Install backend + frontend dependencies (host)
install:
	cd backend && composer install
	cd frontend && pnpm install

## Run database migrations
migrate:
	docker compose exec backend php bin/console migration:run

## Run all tests
test: test-backend test-frontend

## Backend unit tests (PHPUnit)
test-backend:
	cd backend && vendor/bin/phpunit

## Frontend unit tests (Vitest)
test-frontend:
	cd frontend && pnpm run test

## Backend static analysis + code style
lint: lint-backend

lint-backend:
	cd backend && vendor/bin/phpstan analyse --no-progress
	cd backend && vendor/bin/phpcs

## Auto-fix backend code style
lint-fix:
	cd backend && vendor/bin/phpcbf
