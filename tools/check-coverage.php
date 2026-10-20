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

$report = simplexml_load_file(__DIR__ . '/../build/clover.xml');
if (false === $report) {
    fwrite(STDERR, "Cannot read the Clover coverage report.\n");

    exit(1);
}

$metrics = $report->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percentage = $statements > 0 ? 100 * $covered / $statements : 0;
printf("Line coverage: %.2f%% (required: 100%%)\n", $percentage);

exit($statements > 0 && $covered === $statements ? 0 : 1);
