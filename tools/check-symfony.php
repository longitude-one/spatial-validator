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
 */

declare(strict_types=1);
use Composer\InstalledVersions;

require __DIR__ . '/../vendor/autoload.php';

$expected = getenv('EXPECTED_SYMFONY');
$version = InstalledVersions::getVersion('symfony/validator');
if (false === $expected || null === $version || !str_starts_with($version, $expected . '.')) {
    fwrite(STDERR, "Installed Symfony Validator does not match the requested branch.\n");

    exit(1);
}

printf("Verified Symfony Validator %s (branch %s).\n", $version, $expected);
