COMPOSER=./composer.phar
DOCTRINE=vendor/bin/doctrine
all: schema composer.lock

.PHONY: schema
schema: composer.lock $(wildcard src/Models/*.php)
	$(DOCTRINE) orm:schema-tool:update --force --dump-sql

.PHONY: drop-schema
drop-schema: composer.lock
	$(DOCTRINE) orm:schema-tool:drop --force

$(COMPOSER):
	wget -N https://getcomposer.org/composer.phar -O $(COMPOSER)
	chmod +x $(COMPOSER)

composer.lock: composer.json composer.phar
	$(COMPOSER) update
	touch composer.lock
