<?php

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,

        '@PSR1' => true,
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,

        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
        ->in(__DIR__)
        ->exclude([
            'vendor',
        ])
    )
;
