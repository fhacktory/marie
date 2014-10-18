COMPOSER=./composer.phar
all: composer.lock

$(COMPOSER):
	wget -N https://getcomposer.org/composer.phar -O $(COMPOSER)
	chmod +x $(COMPOSER)

composer.lock: composer.json composer.phar
	$(COMPOSER) update
