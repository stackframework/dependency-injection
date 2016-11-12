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

namespace Stack\DependencyInjection\Injection;

use Interop\Container\ContainerInterface;
use Stack\DependencyInjection\Resolver\Resolver;

/**
 * A factory to create objects and values for injection into the Container.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class InjectionFactory
{
    /**
     * A Resolver to provide class-creation specifics.
     *
     * @var Resolver
     */
    private $resolver;

    /**
     * InjectionFactory constructor.
     *
     * @param Resolver $resolver
     */
    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Returns a new LazyObject.
     *
     * @param callable $callable The callable to invoke.
     * @param array    $params   Arguments for the callable.
     *
     * @return LazyObject
     */
    public function lazyObject($callable, array $params = []) : LazyObject
    {
        return new LazyObject($callable, $params);
    }

    /**
     * Returns a new NewLazyObject.
     *
     * @param string $class   The class to instantiate.
     * @param array  $params  Params for the instantiation.
     * @param array  $setters Setters for the instantiation.
     *
     * @return NewLazyObject
     */
    public function newLazyObject(string $class, array $params = [], array $setters = []) : NewLazyObject
    {
        return new NewLazyObject($this->resolver, $class, $params, $setters);
    }

    /**
     * Returns a new LazyGetObject.
     *
     * @param ContainerInterface $container The service container.
     * @param string             $name        The name of service to retrieve.
     *
     * @return GetLazyObject
     */
    public function getLazyObject(ContainerInterface $container, string $name)
    {
        return new GetLazyObject($container, $name);
    }
}
