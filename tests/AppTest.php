<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\App;

/**
 * @coversDefaultClass \App\App
 */
class AppTest extends TestCase
{
    public function testGetInstance()
    {
        $app = new App();
        $instance = App::getInstance();

        $this->assertTrue($instance instanceof App);
        $this->assertTrue($instance === $app);

        return $app;
    }

    /**
     * @depends testGetInstance
     */
    public function test(App $app)
    {
        $app->add(Stub\Reference::class, Stub\Implementation::class);

        $class = $app->get(Stub\ConstructorWithParam::class, ['app' => $app]);
        $this->assertTrue($class->app instanceof App);
        $this->assertTrue($class->impl instanceof Stub\Implementation);
    }

    /**
     * @depends testGetInstance
     */
    public function testDynamicMethods(App $app)
    {
    }

}
