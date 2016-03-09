<?php

use \mageekguy\atoum;
use mageekguy\atoum\reports;

$runner->addTestsFromDirectory('tests/units');

//coveralls.io only from travis-ci
if(getenv('TRAVIS')) {
    $coveralls = new reports\asynchronous\coveralls('src', 'JbFBPr0Mz22rU3mCsvQhfs0ruvMzldcrk');
    $defaultFinder = $coveralls->getBranchFinder();
    $coveralls
            ->setBranchFinder(function() use ($defaultFinder) {
                    if (($branch = getenv('TRAVIS_BRANCH')) === false)
                    {
                            $branch = $defaultFinder();
                    }

                    return $branch;
            })
            ->setServiceName(getenv('TRAVIS') ? 'travis-ci' : null)
            ->setServiceJobId(getenv('TRAVIS_JOB_ID') ?: null)
            ->addDefaultWriter()
    ;
    $runner->addReport($coveralls);
}


$report = $script->addDefaultReport();
