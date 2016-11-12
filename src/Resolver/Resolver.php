<?php
/**
 * This file is part of the Stack package.
 *
 * (c) Andrzej Kostrzewa <andkos11@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Stack\DependencyInjection\Resolver;

/**
 * Resolves class creation specifics based on constructor params and setter
 * definitions, unified across class defaults, inheritance hierarchies, and
 * configuration.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
class Resolver
{
    /**
     * @var array
     */
    protected $definition = [];

    /**
     * Constructor params in the form `$params[$class][$name] = $value`.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Setter definitions in the form of `$setters[$class][$method] = $value`.
     *
     * @var array
     */
    protected $setters = [];

    /**
     * Constructor params and setter definitions, unified across class
     * defaults, inheritance hierarchies, and configuration.
     *
     * @var array
     */
    protected $unifiedClass = [];

    /**
     * Arbitrary values in the form of `$values[$key] = $value`.
     *
     * @var array
     */
    protected $values = [];

    /**
     * A Reflector.
     *
     * @var Reflector
     */
    protected $reflector;

    /**
     * @var ParameterResolver
     */
    protected $parameterResolver;

    /**
     * @var SetterResolver
     */
    protected $setterResolver;

    /**
     * Resolver constructor.
     *
     * @param Reflector $reflector
     */
    public function __construct(Reflector $reflector)
    {
        $this->reflector         = $reflector;
        $this->parameterResolver = new ParameterResolver();
        $this->setterResolver    = new SetterResolver();
    }

    /**
     * Add constructor parameters definition.
     *
     * @param array $params
     */
    public function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Add setter parameters definition.
     *
     * @param array $setters
     */
    public function addSetters(array $setters)
    {
        $this->setters = array_merge($this->setters, $setters);
    }

    /**
     * Creates and returns a new instance of a class using reflection and
     * the configuration parameters, optionally with overrides, invoking Lazy
     * values along the way.
     *
     * @param string $class        The class to instantiate.
     * @param array  $params  An array of override parameters.
     * @param array  $setters An array of override setters.
     *
     * @return \stdClass
     */
    public function resolve(string $class, array $params = [], array $setters = []) : \stdClass
    {
        list($classParams, $classSetters) = $this->unifiedClass($class);
        $params                           = $this->parameterResolver->resolve($class, $classParams, $params);
        $setters                          = $this->setterResolver->resolve($class, $classSetters, $setters);

        return (object) [
            'reflection' => $this->reflector->reflectionClass($class),
            'params'     => $params,
            'setters'    => $setters,
        ];
    }

    /**
     * @param array $definition
     */
    public function set(array $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Returns the unified constructor params and setters for a class.
     *
     * @param string $class The class name to return values for.
     *
     * @return array An array with two elements; 0 is the constructor params
     *               for the class, and 1 is the setter methods and values for the class.
     */
    public function unifiedClass(string $class) : array
    {
        if (isset($this->unifiedClass[$class])) {
            return $this->unifiedClass[$class];
        }

        $unifiedClassElement = [[], []];
        $parent = get_parent_class($class);

        if ($parent) {
            $unifiedClassElement = $this->unifiedClass($parent);
        }

        $this->unifiedClass[$class][0] = $this->unifiedClassParams($class, $unifiedClassElement[0]);
        $this->unifiedClass[$class][1] = $this->unifiedClassSetters($class, $unifiedClassElement[1]);

        return $this->unifiedClass[$class];
    }

    /**
     * Returns the unified constructor params for a class.
     *
     * @param string $class  The class name to return values for.
     * @param array  $parent The parent unified params.
     *
     * @return array The unified params.
     */
    protected function unifiedClassParams(string $class, array $parent) : array
    {
        $unifiedParams = [];
        $classParams   = $this->reflector->parameters($class);
        foreach ($classParams as $classParam) {
            $unifiedParams[$classParam->name] = $this->unifiedClassParam(
                $classParam,
                $class,
                $parent
            );
        }

        return $unifiedParams;
    }

    /**
     * Returns a unified param.
     *
     * @param \ReflectionParameter $param  A parameter reflection.
     * @param string               $class  The class name to return values for.
     * @param array                $parent The parent unified params.
     *
     * @return mixed The unified param value.
     */
    protected function unifiedClassParam(\ReflectionParameter $param, string $class, array $parent)
    {
        $name     = $param->getName();
        $position = $param->getPosition();
        if (isset($this->definition[$name])) {
            return $this->definition[$name];
        }

        $explicitClass = $this->explicitClassParam($this, $class, $position, $name);

        return $explicitClass ? $explicitClass : $this->implicitOrDefaultClassParam($name, $parent, $param);
    }

    /**
     * Returns the unified setters for a class.
     * Class-specific setters take precedence over trait-based setters, which
     * take precedence over interface-based setters.
     *
     * @param string $class  The class name to return values for.
     * @param array  $parent The parent unified setters.
     *
     * @return array The unified setters.
     */
    protected function unifiedClassSetters($class, array $parent)
    {
        $unifiedSetters = $parent;
        $setterFromInterfaces = function ($self, $class, &$unifiedSetters) {
            $interfaces = class_implements($class);
            foreach ($interfaces as $interface) {
                if (isset($self->setters[$interface])) {
                    $unifiedSetters = array_merge(
                        $self->setters[$interface],
                        $unifiedSetters
                    );
                }
            }
        };

        $setterFromTraits = function ($self, $class, &$unifiedSetters) {
            $traits = $self->reflector->traits($class);
            foreach ($traits as $trait) {
                if (isset($self->setters[$trait])) {
                    $unifiedSetters = array_merge(
                        $self->setters[$trait],
                        $unifiedSetters
                    );
                }
            }
        };

        $setterFromInterfaces($this, $class, $unifiedSetters);
        $setterFromTraits($this, $class, $unifiedSetters);

        if (isset($this->setters[$class])) {
            $unifiedSetters = array_merge(
                $unifiedSetters,
                $this->setters[$class]
            );
        }

        return $unifiedSetters;
    }

    /**
     * @param self    $self
     * @param string  $class    The class name to return values for.
     * @param integer $position The class param position.
     * @param string  $name     The class param name.
     *
     * @return mixed The unified param value.
     */
    private function explicitClassParam(Resolver $self, string $class, int $position, string $name)
    {
        $explicitPosition = isset($self->params[$class])
            && array_key_exists($position, $self->params[$class])
            && !$self->params[$class][$position] instanceof UnresolvedParam;

        if ($explicitPosition) {
            return $self->params[$class][$position];
        }

        $explicitNamed = isset($self->params[$class])
            && array_key_exists($name, $self->params[$class])
            && !$self->params[$class][$name] instanceof UnresolvedParam;

        if ($explicitNamed) {
            return $self->params[$class][$name];
        }

        return false;
    }

    /**
     * @param string               $name   The class name to return values for.
     * @param array                $parent The parent unified params.
     * @param \ReflectionParameter $param  A parameter reflection.
     *
     * @return mixed The unified param value, or UnresolvedParam.
     */
    private function implicitOrDefaultClassParam(string $name, array $parent, \ReflectionParameter $param)
    {
        $implicitNamed = array_key_exists($name, $parent)
            && !$parent[$name] instanceof UnresolvedParam;

        if ($implicitNamed) {
            return $parent[$name];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        return new UnresolvedParam($name);
    }
}
