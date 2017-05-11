<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use DI\Container;
use DI\Exception\NotFoundException;
use stdClass;

/**
 * @coversDefaultClass \DI\Container
 */
class ContainerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::testGetInstance
     */
    public function testGetInstance()
    {
        $container = new Container();
        $instance = Container::getInstance();
        $className = Container::class;

        $this->assertTrue($instance instanceof $className);
        $this->assertTrue($instance == $container);
    }

    /**
     * @covers ::get
     * @expectedException DI\Exception\NotFoundException
     */
    public function testGetNotFoundException()
    {
        $container = new Container();
        $container->get('abc');
    }

    public function testClosureResolution()
    {
        $container = new Container;
        $container->add('status', function () {
            return 'ok';
        });
        $this->assertEquals('ok', $container->get('status'));
    }

    public function testSharedClosureResolution()
    {
        $container = new Container;
        $class = new stdClass;
        $container->singleton('class', function () use ($class) {
            return $class;
        });
        $this->assertTrue($class === $container->get('class'));
    }

    public function testAutoConcreteResolution()
    {
        $container = new Container;
        $this->assertTrue($container->get(Stub\Common::class) instanceof Stub\Common);
    }

    public function testSharedConcreteResolution()
    {
        $container = new Container;
        $classname = Stub\Common::class;
        $container->singleton($classname);
        $var1 = $container->get($classname);
        $var2 = $container->get($classname);
        $this->assertTrue($var1 === $var2);
    }

    public function testConstructorParamAutoResolution()
    {
        $container = new Container;

        $class = $container->get(Stub\ConstructorWithParam::class);
        $this->assertTrue(is_null($class->impl));

        $container->add(Stub\Reference::class, Stub\Implementation::class);
        $class = $container->get(Stub\ConstructorWithParam::class);
        $this->assertTrue($class->impl instanceof Stub\Implementation);
    }

    public function testConstructorDependentResolution()
    {
        $container = new Container;
        $container->add(Stub\Reference::class, Stub\Implementation::class);
        $class = $container->get(Stub\ConstructorWithDependent::class);
        $this->assertTrue($class->inner instanceof Stub\ConstructorWithParam);
        $this->assertTrue($class->inner->impl instanceof Stub\Implementation);
    }

    public function testFullResolution()
    {
        $container = new Container;
        $container->add(Stub\Reference::class, Stub\Implementation::class);

        $class = $container->get(Stub\FullClass::class);
        $this->assertTrue($class->class instanceof Stub\Common);
        $this->assertTrue($class->inner instanceof Stub\ConstructorWithParam);
        $this->assertTrue(count($class->custom) === 0);
        $this->assertTrue($class->inner->impl instanceof Stub\Implementation);
    }

    /**
     * @covers ::has
     * @covers ::addInstance
     */
    public function testHas()
    {
        $container = new Container();

        $this->assertTrue($container->has('stdClass'));
        $this->assertTrue($container->has(Stub\Common::class));
        $this->assertFalse($container->has('abc'));

        $class = new stdClass;
        $container->addInstance('dog', $class);
        $container->get('dog');
        $this->assertTrue($container->has('dog'));
    }

    public function testAlias()
    {
        $container = new Container();

        $container->add(Stub\Reference::class, Stub\Implementation::class);
        $container->add('class', Stub\FullClass::class);
        $container->add('classAlias', 'class');
        $class = $container->get('classAlias');
        $this->assertTrue($class instanceof Stub\FullClass);
        $this->assertTrue($class->class instanceof Stub\Common);
        $this->assertTrue($class->inner instanceof Stub\ConstructorWithParam);
        $this->assertTrue($class->inner->impl instanceof Stub\Implementation);
    }

    public function testArrayAccess()
    {
        $container = new Container;
        $container['something'] = function () {
            return 'foo';
        };
        $this->assertTrue(isset($container['something']));
        $this->assertEquals('foo', $container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));
    }

    public function testSharedArrayAccess()
    {
        $container = new Container;
        $container->singleton('something', function () {
            return 'foo';
        });
        $this->assertEquals('foo', $container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));
    }

    public function testSolvedArrayAccess()
    {
        $container = new Container;
        $container[Stub\Reference::class] = Stub\Implementation::class;
        $class = $container[Stub\FullClass::class];

        $this->assertTrue($class->class instanceof Stub\Common);
        $this->assertTrue($class->inner instanceof Stub\ConstructorWithParam);
        $this->assertTrue($class->inner->impl instanceof Stub\Implementation);
        unset($container[Stub\Reference::class]);
        $this->assertFalse(isset($container[Stub\Reference::class]));
    }

    public function testRemove()
    {
        $container = new Container;
        $container->add(Stub\Reference::class, Stub\Implementation::class);
        $this->assertTrue($container->get(Stub\Reference::class) instanceof Stub\Implementation);

        $container->remove(Stub\Reference::class);
        $this->assertFalse($container->has(Stub\Reference::class));
    }

    /**
     */
    public function testAddGlobalListen()
    {

    }

    /**
     * @covers ::exist
     */
    public function testExist()
    {
        $container = new Container;
        $container->add('common', stdClass::class);
        $container->add('dog', (new stdClass()));

        $this->assertTrue($container->exist('common'));
        $this->assertTrue($container->exist('dog'));
        $this->assertFalse($container->exist(stdClass::class));
    }

    /*
    public function testLoopAdd()
    {
        $container = new Container();
        $container->add('class', 'classAlias');
        $container->add('classAlias', 'class');
        $class = $container->get('classAlias');
    }
    */
}
