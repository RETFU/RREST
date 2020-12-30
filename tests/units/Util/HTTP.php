<?php

namespace RREST\tests\units\Util;

require_once __DIR__ . '/../boostrap.php';

use atoum;
use RREST\Util\HTTP as UTILHTTP;

class HTTP extends atoum
{
    public function testGetProtocolFromSERVER()
    {
        $this
            ->string(UTILHTTP::getProtocol())
            ->isEqualTo('HTTP')
        ;

        $_SERVER['HTTPS'] = 'on';
        $this
            ->string(UTILHTTP::getProtocol())
            ->isEqualTo('HTTPS')
        ;

        $_SERVER['HTTPS'] = 'x';
        $this
            ->string(UTILHTTP::getProtocol())
            ->isEqualTo('HTTP')
        ;
    }

    public function testGetProtocolFromHTTPX()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this
            ->string(UTILHTTP::getProtocol())
            ->isEqualTo('HTTPS')
        ;

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $_SERVER['HTTP_X_FORWARDED_SSL'] = 'on';
        $this
            ->string(UTILHTTP::getProtocol())
            ->isEqualTo('HTTPS')
        ;
    }

    public function testGetHeaderFromSERVER()
    {
        $_SERVER['content-type'] = 'x';
        $this
            ->string(UTILHTTP::getHeader('Content-Type'))
            ->isEqualTo('x')
        ;

        $this
            ->variable(UTILHTTP::getHeader('X-HEADER'))
            ->isNull()
        ;
    }
}
