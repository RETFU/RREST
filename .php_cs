<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'vendor',
    ])
    ->in(__DIR__)
    ->files()->name('*.php')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
    ])
    ->setFinder($finder)
;
