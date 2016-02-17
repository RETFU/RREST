<?php
namespace RREST\tests\units;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class RREST extends atoum
{
    public function testGetActionMethodName()
    {
        //FIXME: add a raml file for test purpose & fix this test
        // $app = new \Mock\Silex\Application;
        // $ramlCacheFile = '/home/iwd/app/git/iwd-bran/config/api/api.raml.cache';
        // if ( file_exists($ramlCacheFile) ) {
        //     $apiDefinition = unserialize(file_get_contents($ramlCacheFile));
        // } else {
        //     throw new \RuntimeException($ramlCacheFile.' is missing: execute php ./cli/cli.php build');
        // }
        //
        // $apiSpec = new \Mock\RREST\APISpec\RAML($apiDefinition, 'POST', '/api/v1/authenticate');
        // $provider = new \Mock\RREST\Provider\Silex($app);
        //
        // $this
        //     ->given($this->newTestedInstance($apiSpec, $provider))
        //     ->string($this->testedInstance->getActionMethodName('post'))
        //     ->isEqualTo('postAction')
        // ;
    }
}
