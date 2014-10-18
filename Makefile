COMPOSER=./composer.phar
DOCTRINE=vendor/bin/doctrine
all: composer.lock

.PHONY: schema
schema: composer.lock src/Models/*.php
	$(DOCTRINE) orm:schema-tool:create

$(COMPOSER):
	wget -N https://getcomposer.org/composer.phar -O $(COMPOSER)
	chmod +x $(COMPOSER)

composer.lock: composer.json composer.phar
	$(COMPOSER) update
