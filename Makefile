# Load environment variables from .env file
include .env
export

.PHONY: build local env-check stop terminal migrate clear migrate-test test-setup test

## Build application image
build:
	docker build -f container/Dockerfile -t $(PROJECT_NAME) .

## Setup local environment
local: env-check stop build network mariadb-start redis-start test-setup app-start migrate migrate-test

## Check and create .env file if needed
env-check:
	@if [ ! -f .env ]; then \
		echo "Creating .env from .env.example..."; \
		cp .env.example .env; \
	fi

## Create Docker network
network:
	docker network create $(NETWORK_NAME) 2>/dev/null || true

## Stop all containers
stop:
	docker stop $(APP_CONTAINER) 2>/dev/null || true
	docker stop $(MYSQL_CONTAINER) 2>/dev/null || true
	docker stop $(REDIS_CONTAINER) 2>/dev/null || true

## Start MariaDB container
mariadb-start: network
	docker rm -f $(MYSQL_CONTAINER) || true
	docker run -d \
		--name $(MYSQL_CONTAINER) \
		--network $(NETWORK_NAME) \
		-p $(MYSQL_PORT):3306 \
		-e MYSQL_ROOT_PASSWORD=$(DB_ROOT_PASSWORD) \
		-e MYSQL_USER=$(DB_USER) \
		-e MYSQL_PASSWORD=$(DB_PASSWORD) \
		-v $(PROJECT_NAME)_mysql_data:/var/lib/mysql \
		-v "$(PWD)/container/init-db.sql:/docker-entrypoint-initdb.d/init-db.sql" \
		mariadb:10.11 \
		--character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
## Start Redis container
redis-start: network
	docker rm -f $(REDIS_CONTAINER) || true
	docker run -d \
		--name $(REDIS_CONTAINER) \
		--network $(NETWORK_NAME) \
		-p $(REDIS_PORT):6379 \
		redis:7-alpine

## Start application container
app-start: network
	docker rm -f $(APP_CONTAINER) || true
	docker run -d \
		--name $(APP_CONTAINER) \
		--network $(NETWORK_NAME) \
		-p $(APP_PORT):80 \
		-v "$(PWD)/config:/app/config" \
		-v "$(PWD)/controllers:/app/controllers" \
		-v "$(PWD)/mail:/app/mail" \
		-v "$(PWD)/migrations:/app/migrations" \
		-v "$(PWD)/models:/app/models" \
		-v "$(PWD)/tests:/app/tests" \
		-v "$(PWD)/views:/app/views" \
		-v "$(PWD)/web:/app/web" \
		-v "$(PWD)/widgets:/app/widgets" \
		--tmpfs /app/runtime \
		--tmpfs /app/web/assets \
		-e DB_HOST=$(DB_HOST) \
		-e DB_PORT=$(DB_PORT) \
		-e DB_NAME=$(DB_NAME) \
		-e DB_USER=$(DB_USER) \
		-e DB_PASSWORD=$(DB_PASSWORD) \
		-e REDIS_HOST=$(REDIS_HOST) \
		-e REDIS_PORT=$(REDIS_PORT) \
		$(PROJECT_NAME)
	docker exec $(APP_CONTAINER) chown -R www-data:www-data /app/runtime /app/web/assets

## Enter application container terminal
terminal:
	docker exec -it $(APP_CONTAINER) /bin/bash

## Run database migrations
migrate:
	docker exec $(APP_CONTAINER) php yii migrate --interactive=0

## Clear application cache
clear:
	docker exec -it $(APP_CONTAINER) php yii cache/flush-all

## Run test database migrations
migrate-test:
	docker exec $(APP_CONTAINER) bash -c 'export YII_ENV=test && php yii migrate --interactive=0'

## Run tests
test:
	docker exec $(APP_CONTAINER) ./vendor/bin/phpunit