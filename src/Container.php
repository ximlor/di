<?php

namespace DI;

use DI\Exception\BindingResolutionException;
use DI\Reference\Container as ContainerInterface;
use Closure;
use ReflectionClass;
use DI\Exception\NotFoundException;
use ArrayAccess;
use ReflectionParameter;

class Container implements ContainerInterface, ArrayAccess
{

    protected static $instance;

    protected $entries = [];

    protected $singletons = [];

    protected $solved = [];

    protected $resolvingCallbacks = [];

    protected $globalResolvingCallbacks = [];

    public function __construct()
    {
        static::$instance = $this;
        $this->singletons[self::class] = $this;
        $this->singletons[ContainerInterface::class] = $this;
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    public function add($id, $value = null, $share = false)
    {
        if (is_null($value)) {
            $value = $id;
        }

        $this->remove($id);

        $this->entries[$id] = [$value, $share];
    }

    public function singleton($id, $value = null)
    {
        $this->add($id, $value, true);
    }

    public function addInstance($id, $instance)
    {
        $this->singletons[$id] = $instance;
    }

    public function get($id, $parameters = [])
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        $value = $this->entries[$id][0] ?? $id;

        if ($value === $id || $value instanceof Closure) {
            $obj = $this->make($value, $parameters);
        } else {
            $obj = $this->get($value, $parameters);
        }

        if (!empty($this->entries[$id][1])) {
            $this->singletons[$id] = $obj;
        }

        return $obj;
    }

    /**
     * 判断是否添加过该条目
     *
     * @param $id
     *
     * @return bool
     */
    public function exist($id)
    {
        return (isset($this->entries[$id]) || isset($this->singletons[$id])) ? true : false;
    }

    /**
     * 如果该标识符可以被成功解析，那么返回 true
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        try {
            $this->get($id);
        } catch (\Exception $e) {
            return false;
        }
        return true;
//        return (isset($this->entries[$id]) || isset($this->singletons[$id])) ? true : false;
    }

    public function remove($id)
    {
        unset($this->singletons[$id], $this->entries[$id]);
    }

    public function make($target, $parameters)
    {
        if ($target instanceof Closure) {
            return call_user_func($target, $this);
        }

//        if (isset($this->solved[$target])) {
//            return call_user_func($this->solved[$target], $this);
//        }

        $callback = $this->getDefinition($target, $parameters);
        $this->solved[$target] = $callback;
        return call_user_func($callback, $this);
    }

    public function getDefinition($name, $custom)
    {
        if (!class_exists($name) && !interface_exists($name)) {
            throw new NotFoundException("class {$name} is not found");
        }

        $reflectionClass = new ReflectionClass($name);

        if (!$reflectionClass->isInstantiable()) {
            throw new BindingResolutionException("Target [$name] is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();

        if (is_null($constructor)) {
            return (function () use ($reflectionClass) {
                return $reflectionClass->newInstanceWithoutConstructor();
            });
        }

        $constructorParameters = $constructor->getParameters();

        $params = [];
        foreach ($constructorParameters as $v) {
            $params[$v->name] = $this->getDependent($v);
        }
        $params = array_merge($custom, array_diff_key($params, $custom));

        return (function () use ($reflectionClass, $params) {
            return $reflectionClass->newInstanceArgs($params);
        });
    }

    protected function getDependent(ReflectionParameter $parameter)
    {
        if ($parameter->hasType() && !is_null($default = $parameter->getClass())) {
            try {
                return $this->get($default->name);
            } catch (\Exception $e) {

            }
        }

        if ($parameter->isDefaultValueAvailable()) {  // isOptional()
            return $parameter->getDefaultValue();
        }
        throw new BindingResolutionException("Unresolparameterable dependency resolparametering [$parameter].");
    }

    public function addGlobalListen(callable $callback)
    {
        $this->globalResolvingCallbacks[] = $callback;
    }

    public function addListen($id, callable $callback)
    {
        $this->resolvingCallbacks[$id][] = $callback;
    }

    public function fireResolvingCallbacks($id, $obj)
    {
        if (isset($this->resolvingCallbacks[$id])) {
            $this->fireCallbackArray($obj, $this->resolvingCallbacks[$id]);
        }
        $this->fireCallbackArray($obj, $this->globalResolvingCallbacks);
    }

    public function fireCallbackArray($obj, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $obj);
        }
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value, false);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

}
