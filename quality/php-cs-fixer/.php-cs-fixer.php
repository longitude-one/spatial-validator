<?php

/**
 * This file is part of the spatial project.
 *
 * PHP 8.4 | 8.5
 *
 * Copyright Alexandre Tranchant <alexandre.tranchant@gmail.com> 2024-2026
 * Copyright Longitude One 2024-2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

declare(strict_types=1);

//Replace the value of this variable with the project's launch year.
$firstYear = 2024;

function __copyright(int $launchYear): string
{
    $currentYear = (int)date('Y');
    if ($currentYear === $launchYear) {
        return (string) $currentYear;
    }

    return sprintf('%d-%d', $launchYear, $currentYear);
}

$header = file_get_contents(__DIR__ . '/headers.txt');
$header = str_replace("%year%", __copyright($firstYear), $header);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/../../lib/',
        __DIR__ . '/../../quality/php-cs-fixer/',
        __DIR__ . '/../../tests/',
        __DIR__ . '/../../tools/',
    ]);

$config = new PhpCsFixer\Config();

return $config->setRiskyAllowed(true)->setRules([
    '@DoctrineAnnotation' => true,
    '@PhpCsFixer' => true,
    '@PHPUnit100Migration:risky' => true,
    '@PHP84Migration' => true,
    'concat_space' => ['spacing' => 'one'],
    'declare_strict_types' => true,
    'dir_constant' => true, // Remove when @PHPCsFixerRisky is added
    'ereg_to_preg' => true, // Remove when @PHPCsFixerRisky is added
    'header_comment' => [
        'comment_type' => 'PHPDoc',
        'header' => $header,
        'location' => 'after_open',
        'separate' => 'bottom',
    ],
    //        'date_time_immutable' => true,
    'is_null' => true, // Remove when @PHPCsFixerRisky is added
    'mb_str_functions' => true, //Set to false if we only use multibyte string functions for ClassName
    'modernize_types_casting' => true, // Remove when @PHPCsFixerRisky is added
    'new_expression_parentheses' => [
        'use_parentheses' => true,
    ],
    'no_unneeded_final_method' => true, // Remove when @PHPCsFixerRisky is added
    'ordered_interfaces' => [
        'direction' => 'ascend',
        'order' => 'alpha',
    ],
    'ordered_class_elements' => [
        'order' => [
            'use_trait',
            'case',
            'constant_public', 'constant_protected', 'constant_private', 'constant',
            'property_public_static', 'property_protected_static', 'property_private_static', 'property_static',
            'property_public', 'property_protected', 'property_private', 'property',
            'construct', 'destruct',
            'phpunit',
            'method_public_static', 'method_protected_static', 'method_private_static', 'method_static',
            'method_public', 'method_protected', 'method_private', 'method', 'magic',
        ],
        'sort_algorithm' => 'alpha',
    ],
    'php_unit_test_case_static_method_calls' => true, //FIXME accepted values are self static or this.
    'single_line_throw' => false, //FIXME set to true when all #60 is fixed
    'single_line_empty_body' => false,
])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
