<?php

namespace RREST\Exception;

interface ErrorExceptionInterface
{
    /**
     * @return RREST\Error[]
     */
    public function getErrors();
}
