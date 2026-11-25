.DEFAULT_GOAL := help

.PHONY: help install dev test test-js test-cov test-e2e stan lint lint-check build audit up down logs

## Show available targets
help:
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

## Install PHP & JS dependencies, prepare .env and the sqlite database
install:
	composer install --no-interaction
	npm ci
	php -r "file_exists('.env') || copy('.env.example', '.env');"
	php artisan key:generate --force
	php artisan migrate --graceful --seed --force

## Run server, queue, log tail and vite together (composer dev)
dev:
	composer dev

## Run the Pest suite
test:
	php artisan test

## Run the Vitest suite (frontend units)
test-js:
	npm run test:unit

## Run the Pest suite with coverage (fails below 85%)
test-cov:
	vendor/bin/pest --coverage --min=85

## Run the Playwright E2E suite (e2e/serve.sh seeds a server on :8080)
test-e2e:
	npm run test:e2e

## Static analysis (PHPStan + Larastan, level 6)
stan:
	vendor/bin/phpstan analyse --no-progress --memory-limit=1G

## Auto-fix code style (Pint, ESLint, Prettier)
lint:
	vendor/bin/pint
	npm run lint
	npm run format

## Check code style without fixing
lint-check:
	vendor/bin/pint --test
	npm run lint:check
	npm run format:check

## Build production frontend assets
build:
	npm run build

## Security audit: composer lock + production npm dependencies
audit:
	composer audit
	npm audit --omit=dev

## Start the docker stack (app, web, db, redis)
up:
	docker compose up -d

## Stop the docker stack
down:
	docker compose down

## Tail the docker stack logs
logs:
	docker compose logs -f
