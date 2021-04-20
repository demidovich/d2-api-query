SHELL = /bin/bash -o pipefail
APP_NAME = d2_api_query
.DEFAULT_GOAL := help

ifeq ($(CI_JOB_ID),)
    CI_JOB_ID := local
endif

export APP_RUN_NAME = $(APP_NAME)-$(CI_JOB_ID)

docker_bin := $(shell command -v docker 2> /dev/null)
docker_compose_bin := $(shell command -v docker-compose 2> /dev/null)

all_images = ${APP_RUN_NAME}-fpm \
			 ${APP_RUN_NAME}-postgres

# ---------------------------------------------------------------------------------------------------------------------

help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build docker images
	@mkdir -p var/postgres
	$(docker_compose_bin) build

clean: ## Remove images
	$(docker_compose_bin) down -v
	$(foreach image,$(all_images),$(docker_bin) rmi -f $(image);)

# --- [ Development tasks ] -------------------------------------------------------------------------------------------

up: ## Up application
	@mkdir -p var/postgres
	$(docker_compose_bin) up -d

down: ## Down application
	$(docker_compose_bin) down

fpm: ## Shell of php-fpm container
	$(docker_compose_bin) exec --user root fpm /bin/sh

test-psalm: ## Run psalm tests
	$(docker_compose_bin) exec fpm /app/vendor/bin/psalm

test-phpunit: ## Run phpunit tests
	$(docker_compose_bin) exec fpm php /app/vendor/bin/phpunit

test-coverage: ## Run phpunit coverage tests
	$(docker_compose_bin) exec fpm php -dextension=xdebug.so -dxdebug.mode=coverage /app/vendor/bin/phpunit --colors=always --coverage-text --coverage-clover coverage.clover
	$(docker_compose_bin) exec fpm sed -i 's/\/app\/src/.\/src/' ./coverage.clover

postgres: ## Shell of postgresql container
	$(docker_compose_bin) exec --user root postgres /bin/bash

ps: ## Status containers
	$(docker_compose_bin) ps

log: ## Container output logs
	$(docker_compose_bin) logs --follow

default: help
