<?php
/**
 * This file is part of the Stack package.
 *
 * (c) Andrzej Kostrzewa <andkos11@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Stack\DependencyInjection\Resolver;

use Interop\Container\ContainerInterface;

/**
 * Resolves an alias definition to a value.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class AliasResolver
{
    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * AliasResolver constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function get(string $name) : string
    {
        return $this->aliases[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name) : bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Check if a definition can be resolved.
     *
     * @param string $name Entry name.
     *
     * @return bool
     */
    public function isResolvable(string $name) : bool
    {
        if (!$this->has($name)) {
            return false;
        }

        $name = $this->get($name);

        return $this->container->has($name);
    }

    /**
     * Resolve an alias definition to a value.
     * This will return the entry the alias points to.
     *
     * @param string $name Entry name.
     *
     * @return mixed
     */
    public function resolve(string $name)
    {
        $name = $this->get($name);

        return $this->container->get($name);
    }

    /**
     * @param Alias $alias
     */
    public function set(Alias $alias)
    {
        $this->aliases[$alias->name()] = $alias->targetName();
    }
}
