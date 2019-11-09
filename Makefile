.phony: deps
deps:
	composer --working-dir=src/ install

.phony: dev
dev: deps
	docker-compose up -d

.phony: stop
stop:
	docker-compose stop

.phony: logs
logs:
	docker-compose logs -f wordpress
