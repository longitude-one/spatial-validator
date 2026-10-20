<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$expected = getenv('EXPECTED_SYMFONY');
$version = Composer\InstalledVersions::getVersion('symfony/validator');
if (false === $expected || null === $version || !str_starts_with($version, $expected . '.')) {
    fwrite(STDERR, "Installed Symfony Validator does not match the requested branch.\n");
    exit(1);
}

printf("Verified Symfony Validator %s (branch %s).\n", $version, $expected);
