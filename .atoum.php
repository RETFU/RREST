<?php

use \mageekguy\atoum;

$report = $script->addDefaultReport();

// fun
// $report->addField(new atoum\report\fields\runner\atoum\logo());
// $report->addField(new atoum\report\fields\runner\result\logo());

// error
// $coverageField = new atoum\report\fields\runner\coverage\html('RREST', 'coverage');
// $coverageField->setRootUrl('https://github.com/RETFU/RREST');
// $report->addField($coverageField);

$runner->addTestsFromDirectory('tests/units');
