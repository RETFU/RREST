<?php

namespace RREST\Exception;

/**
 * @deprecated use ErrorException instead.
 */
interface ErrorExceptionInterface
{
    /**
     * @return \RREST\Error[]
     */
    public function getErrors();
}
