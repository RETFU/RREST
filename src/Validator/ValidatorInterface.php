<?php

namespace RREST\Validator;

interface ValidatorInterface
{
    /**
     * @return bool
     */
    public function fails();

    /**
     * @return void
     */
    public function validate();
}
