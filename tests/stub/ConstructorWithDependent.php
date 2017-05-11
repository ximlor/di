<?php

namespace Tests\Stub;

class ConstructorWithDependent
{
    public function __construct(ConstructorWithParam $dependent)
    {
        $this->inner = $dependent;
    }

}