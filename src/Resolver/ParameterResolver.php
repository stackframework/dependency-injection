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

use Stack\DependencyInjection\Exception;
use Stack\DependencyInjection\Injection\Lazy;

/**
 * Class ParameterResolver
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class ParameterResolver
{
    /**
     * Resolve class constructor params
     *
     * @param string $class
     * @param array  $params
     * @param array  $mergeParams
     *
     * @throws Exception\MissingParam
     *
     * @return array
     */
    public function resolve(string $class, array $params, array $mergeParams = []) : array
    {
        if (empty($mergeParams)) {
            $this->mergeParamsEmpty($class, $params);

            return $params;
        }

        $this->mergeParams($class, $params, $mergeParams);

        return $params;
    }

    /**
     * Merges the params with overrides; also invokes Lazy values.
     *
     * @param string $class       The params are on this class.
     * @param array  $params      The constructor parameters.
     * @param array  $mergeParams An array of override parameters.
     *
     * @throws Exception\MissingParam
     */
    private function mergeParams(string $class, array &$params, array $mergeParams = [])
    {
        $positionOfParam = 0;
        foreach ($params as $key => $value) {
            if (array_key_exists($positionOfParam, $mergeParams)) {
                $value = $mergeParams[$positionOfParam];
            } elseif (array_key_exists($key, $mergeParams)) {
                $value = $mergeParams[$key];
            }

            if ($value instanceof UnresolvedParam) {
                throw Exception::missingParam($class, $value->name());
            }

            if ($value instanceof Lazy) {
                $value = $value();
            }

            $params[$key] = $value;
            $positionOfParam++;
        }
    }

    /**
     * Load the Lazy values in params when the mergeParams are empty.
     *
     * @param string $class  The params are on this class.
     * @param array  $params The constructor parameters.
     *
     * @throws Exception\MissingParam
     */
    private function mergeParamsEmpty($class, &$params)
    {
        foreach ($params as $key => $value) {
            if ($value instanceof UnresolvedParam) {
                throw Exception::missingParam($class, $value->name());
            }

            if ($value instanceof Lazy) {
                $params[$key] = $value();
            }
        }
    }
}
