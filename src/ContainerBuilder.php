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

namespace Stack\DependencyInjection;

use Interop\Container\ContainerInterface;
use Stack\DependencyInjection\Injection\InjectionFactory;
use Stack\DependencyInjection\Resolver\AnnotationResolver;
use Stack\DependencyInjection\Resolver\AutoResolver;
use Stack\DependencyInjection\Resolver\Reflector;
use Stack\DependencyInjection\Resolver\Resolver;

/**
 * Helper to create and configure a Container.
 *
 * With the default options, the container created is appropriate for the development environment.
 *
 * Example:
 *
 *     $builder   = new ContainerBuilder();
 *     $container = $builder->build();
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
class ContainerBuilder
{
    /**
     * @var string
     */
    private $containerClass;

    /**
     * @var array
     */
    private $definitionSources = [];

    /**
     * @var ContainerInterface
     */
    private $delegateContainer;

    /**
     * @var bool
     */
    private $useAutowiring = true;

    /**
     * @var bool
     */
    private $useAnnotation = false;

    /**
     * ContainerBuilder constructor.
     *
     * @param string $containerClass
     */
    public function __construct($containerClass = 'Stack\DependencyInjection\Container')
    {
        $this->containerClass = $containerClass;
    }

    /**
     * Add definitions to the container.
     *
     * @param array $definitions
     *
     * @return self
     */
    public function definition(array $definitions) : self
    {
        $this->definitionSources = $definitions;

        return $this;
    }

    /**
     * Build and return a container.
     *
     * @return Container
     */
    public function build() : Container
    {
        $resolver = $this->newResolver();
        $resolver->set($this->definitionSources);
        $containerClass = $this->containerClass;

        return new $containerClass(new InjectionFactory($resolver), $this->delegateContainer);
    }

    /**
     * Build a container configured for the dev environment.
     *
     * @return Container
     */
    public static function buildDevContainer() : Container
    {
        $builder = new self();

        return $builder->build();
    }

    /**
     * Enable or disable the use of autowiring to guess injections.
     * Enabled by default.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function useAutowiring(bool $bool) : self
    {
        $this->useAutowiring = $bool;

        return $this;
    }

    /**
     * Enable or disable the use of annotations to guess injections.
     * Disabled by default.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function useAnnotation(bool $bool) : self
    {
        $this->useAnnotation = $bool;

        return $this;
    }

    /**
     * Delegate the container for dependencies.
     *
     * @param ContainerInterface $delegateContainer
     *
     * @return self
     */
    public function delegateContainer(ContainerInterface $delegateContainer) : self
    {
        $this->delegateContainer = $delegateContainer;

        return $this;
    }

    /**
     * Returns a new Resolver instance.
     *
     * @return AnnotationResolver|AutoResolver|Resolver
     */
    protected function newResolver() : Resolver
    {
        if ($this->useAnnotation) {
            return new AnnotationResolver(new Reflector());
        } elseif ($this->useAutowiring) {
            return new AutoResolver(new Reflector());
        }

        return new Resolver(new Reflector());
    }
}
