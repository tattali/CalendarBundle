all: install test

install:
	composer install

test:
	vendor/bin/phpunit

lint_php:
	vendor/bin/php-cs-fixer fix --ansi -v

lint_php_dry:
	vendor/bin/php-cs-fixer fix --ansi -v --dry-run
