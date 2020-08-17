start:
	docker-compose up -d

start-build:
	docker-compose up -d --build

stop:
	docker-compose down

restart:
	docker-compose restart

update:
	@bash ./update.sh

bash:
	docker-compose exec app bash

build:
	docker-compose exec app npm run build

watch:
	docker-compose exec app npm run watch

npm-install:
	docker-compose exec app npm install

ps:
	docker ps

cache-clear:
	docker-compose exec app bin/console cache:clear

cron:
	docker-compose exec app service cron start