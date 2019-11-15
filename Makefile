.phony: deps
deps:
	composer install --prefer-source --no-interaction --dev --working-dir=src/

.phony: dev
dev: deps
	docker-compose up -d

.phony: stop
stop:
	docker-compose stop

.phony: logs
logs:
	docker-compose logs -f wordpress

.phony: zip
zip: deps
	zip -r simplyretswp-dev.zip src/
