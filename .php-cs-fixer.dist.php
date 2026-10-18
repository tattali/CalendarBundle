<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = new Finder()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->append([__FILE__])
;

return new Config()
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        // PER-CS plus the PHP and PHPUnit migration rules for the versions in composer.json
        '@auto' => true,
        '@auto:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        // @Symfony:risky removes it since php-cs-fixer 3.95
        'declare_strict_types' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_order' => true,
        'single_line_comment_style' => true,
        'strict_comparison' => true,
        'strict_param' => true,
    ])
    ->setFinder($finder)
;
