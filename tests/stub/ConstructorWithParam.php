<?php

namespace Tests\Stub;

class ConstructorWithParam
{
    public function __construct(\DI\Reference\Container $app, Reference $implementation = null)
    {
        $this->app = $app;
        $this->impl = $implementation;
    }

}