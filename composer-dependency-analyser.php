<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->enableAnalysisOfUnusedDevDependencies()
    // services.yaml wires the controller to the framework's "event_dispatcher" service, and
    // routing.yaml relies on the framework's controller resolution: no PHP class of it is used.
    ->ignoreErrorsOnPackage('symfony/framework-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // the dependency-injection YamlFileLoader needs it to parse services.yaml: no PHP class of it is used.
    ->ignoreErrorsOnPackage('symfony/yaml', [ErrorType::UNUSED_DEPENDENCY])
    // dev-only floor: the lowest install otherwise picks contracts whose dispatch() signature PHP 8.4+ deprecates
    ->ignoreErrorsOnPackage('symfony/event-dispatcher-contracts', [ErrorType::UNUSED_DEPENDENCY])
    // command-line tools and PHPStan extensions, never referenced from the code
    ->ignoreErrorsOnPackages([
        'friendsofphp/php-cs-fixer',
        'infection/infection',
        'phpstan/phpstan',
        'phpstan/phpstan-phpunit',
        'phpstan/phpstan-strict-rules',
        'phpstan/phpstan-symfony',
        'shipmonk/composer-dependency-analyser',
    ], [ErrorType::UNUSED_DEPENDENCY])
;
