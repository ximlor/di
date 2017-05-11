<?php

namespace Tests\Stub;

use DI\Container;

class FullClass extends ConstructorWithDependent
{
    public function __construct(array $arr = [], Common $class)
    {
        parent::__construct(Container::getInstance()->get(ConstructorWithParam::class));
        $this->custom = $arr;
        $this->class = $class;
    }

}