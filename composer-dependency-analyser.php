<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->enableAnalysisOfUnusedDevDependencies()
    // services.xml wires the controller to the framework's "event_dispatcher" service, and
    // routing.yaml relies on the framework's controller resolution: no PHP class of it is used.
    ->ignoreErrorsOnPackage('symfony/framework-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // phpunit.xml.dist loads its SymfonyTestsListener, which applies SYMFONY_DEPRECATIONS_HELPER.
    ->ignoreErrorsOnPackage('symfony/phpunit-bridge', [ErrorType::UNUSED_DEPENDENCY])
    // command-line tools run by CI, never referenced from the code
    ->ignoreErrorsOnPackages([
        'infection/infection',
        'phpstan/phpstan',
        'phpstan/phpstan-strict-rules',
        'phpstan/phpstan-symfony',
        'shipmonk/composer-dependency-analyser',
    ], [ErrorType::UNUSED_DEPENDENCY]);
