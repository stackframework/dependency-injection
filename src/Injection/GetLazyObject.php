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

/**
 * Returns a Container service when invoked.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class GetLazyObject implements Lazy
{
    /**
     * The service container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * The service name to retrieve.
     *
     * @var string
     */
    private $name;

    /**
     * GetLazyObject constructor.
     *
     * @param ContainerInterface $container
     * @param string             $name
     */
    public function __construct(ContainerInterface $container, string $name)
    {
        $this->container = $container;
        $this->name        = $name;
    }

    /**
     * Invokes the closure to create the instance.
     *
     * @return object The object created by the closure.
     */
    public function __invoke()
    {
        return $this->container->get($this->name);
    }
}
