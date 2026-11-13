<?php
/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
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
