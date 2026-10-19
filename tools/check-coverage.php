<?php

declare(strict_types=1);

$report = simplexml_load_file(__DIR__.'/../build/clover.xml');
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
