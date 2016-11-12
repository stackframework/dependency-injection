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

use Stack\DependencyInjection\Injection\NewLazyObject;

/**
 * This extension of the Resolver additionally auto-resolves unspecified
 * constructor params according to their typehints.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class AutoResolver extends Resolver
{
    /**
     * Auto-resolves params typehinted to classes.
     *
     * @param \ReflectionParameter $param  A parameter reflection.
     * @param string               $class  The class name to return values for.
     * @param array                $parent The parent unified params.
     *
     * @return mixed The auto-resolved param value, or UnresolvedParam.
     */
    protected function unifiedClassParam(\ReflectionParameter $param, string $class, array $parent)
    {
        $unifiedClassParam = parent::unifiedClassParam($param, $class, $parent);
        if (!$unifiedClassParam instanceof UnresolvedParam) {
            return $unifiedClassParam;
        }

        /*
         * @param AutoResolver         $self
         * @param \ReflectionParameter $param
         * @param mixed                $unifiedClassParam
         *
         * @return mixed
         */
        $resolveDefinition = function (AutoResolver $self, \ReflectionParameter $param, $unifiedClassParam) {
            $definition = $param->getClass();
            if ($definition && isset($this->definition[$definition->name])) {
                return $this->definition[$definition->name];
            }

            if ($definition && $definition->isInstantiable()) {
                return new NewLazyObject($self, $definition->name);
            }

            return $unifiedClassParam;
        };

        return $resolveDefinition($this, $param, $unifiedClassParam);
    }
}
