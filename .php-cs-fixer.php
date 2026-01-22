<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRules(["no_unused_imports" => true])
    // by default, Fixer looks for `*.php` files excluding `./vendor/`
    ->setFinder((new Finder())->in(__DIR__)->exclude([__DIR__ . "/resources/lib/phpopenldaper"]));
