<?php
namespace RREST\tests\units;

require_once __DIR__ . '/../boostrap.php';

use atoum;
use RREST\Provider\Silex;
use Silex\Application;

class Response extends atoum
{
    public function testSetFormat()
    {
        $this
            ->exception(
                function() {
                    $app = new Application();
                    $provider = new Silex($app);
                    $this->newTestedInstance($provider,'json',200);
                    $this->testedInstance->setFormat('xxx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;
    }
}
