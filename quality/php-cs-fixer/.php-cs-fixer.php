<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(['@Symfony' => true, 'declare_strict_types' => true, 'concat_space' => ['spacing' => 'one']])
    ->setFinder(PhpCsFixer\Finder::create()->in([$root.'/lib', $root.'/tests', $root.'/tools']));
