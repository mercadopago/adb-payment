-include build/.env

DOCKER_COMPOSE := $(shell if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then echo "docker compose"; elif command -v docker-compose >/dev/null 2>&1; then echo "docker-compose"; else echo "ERROR"; fi)

# Fail if neither docker compose nor docker-compose is available
ifeq ($(DOCKER_COMPOSE),ERROR)
$(error "Neither 'docker compose' nor 'docker-compose' is available. Please install Docker Compose.")
endif

install:
	if ! openssl version >/dev/null 2>&1; then \
		echo "Error: OpenSSL is not installed. Please install OpenSSL and try again."; \
		exit 1; \
	fi && \
	if [ ! -f build/.env ]; then \
		echo "Unable to find .env file on build/ folder, please copy it from .env.sample using: 'cp build/.env.sample build/.env'."; \
		exit 1; \
	fi && \
	if [ ! -f build/credentials/auth.json ]; then \
		echo "Error: auth.json file not found in build/credentials directory. Please create it first."; \
		exit 1; \
	fi && \
	if [ -f ./build/certs/magento.local.key ] && [ -f ./build/certs/magento.local.csr ] && [ -f ./build/certs/magento.local.pem ]; then \
		echo "SSL certificates already exist. Skipping generation."; \
	else \
		echo "Generating SSL certificates..."; \
		openssl genpkey -algorithm RSA -out ./build/certs/magento.local.key && \
		openssl req -new -key ./build/certs/magento.local.key -out ./build/certs/magento.local.csr && \
		openssl x509 -req -days 365 -in ./build/certs/magento.local.csr -signkey ./build/certs/magento.local.key -out ./build/certs/magento.local.pem; \
	fi && \
	$(DOCKER_COMPOSE) -f build/docker-compose.yaml up -d && \
	sleep 8 && \
	docker exec -it magento_php composer require mp-plugins/php-sdk ^3.3 && \
	docker exec -it magento_php php ./bin/magento setup:install \
		--base-url=http://magento.local \
		--db-host=database \
		--db-name=magento \
		--db-user=magento \
		--db-password=magento \
		--admin-firstname=$(ADMIN_FIRSTNAME) \
		--admin-lastname=$(ADMIN_LASTNAME) \
		--admin-email=$(ADMIN_EMAIL) \
		--admin-user=$(ADMIN_USER) \
		--admin-password=$(ADMIN_PASSWORD) \
		--backend-frontname=admin \
		--elasticsearch-host=elasticsearch \
		--elasticsearch-index-prefix=m_ \
		--elasticsearch-port=9200 \
		--search-engine=elasticsearch7 \
		--elasticsearch-enable-auth=false \
		--cache-backend=redis \
		--cache-backend-redis-server=redis \
		--cache-backend-redis-db=0 \
		--session-save=redis \
		--session-save-redis-host=redis \
		--session-save-redis-port=6379 \
		--session-save-redis-db=1 \
		--page-cache=redis \
		--page-cache-redis-server=redis \
		--page-cache-redis-db=2 \
		--language=pt_BR \
		--currency=BRL \
		--timezone=America/Sao_Paulo \
		--use-rewrites=1 \
		--cleanup-database && \
	docker exec -it magento_php bin/magento s:di:c && \
	docker exec magento_php bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth || true && \
	docker exec magento_php bin/magento module:disable Magento_TwoFactorAuth || true && \
	docker exec magento_php bin/magento cache:flush && \
	docker exec -it magento_php bin/magento sampledata:deploy && \
	docker exec -it magento_php bin/magento setup:upgrade && \
	docker exec -it magento_php bin/magento cache:clean && \
	docker exec -it magento_php bin/magento s:di:c && \
	docker exec -it magento_php bin/magento deploy:mode:set developer

uninstall:
	$(DOCKER_COMPOSE) -f build/docker-compose.yaml down --volumes --remove-orphans

run:
	$(DOCKER_COMPOSE) -f build/docker-compose.yaml up -d

stop:
	$(DOCKER_COMPOSE) -f build/docker-compose.yaml down

remove-images:
	docker image rm nginx-magento-mp nginx-php-mp
