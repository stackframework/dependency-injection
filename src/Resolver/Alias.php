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
 * Defines an alias from an entry to another.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class Alias
{
    /**
     * Entry name.
     *
     * @var string
     */
    private $name;

    /**
     * name of the target entry.
     *
     * @var string
     */
    private $targetName;

    /**
     * Alias constructor.
     *
     * @param string      $targetName
     * @param null|string $name
     */
    public function __construct($targetName, $name = null)
    {
        if ($name === null) {
            $name = self::nameFromTarget($targetName);
        }

        $this->name = $name;
        $this->targetName = $targetName;
    }

    /**
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function targetName(): string
    {
        return $this->targetName;
    }

    /**
     * Extract name from target.
     *
     * @param string $targetName
     *
     * @return string
     */
    private static function nameFromTarget(string $targetName) : string
    {
        $name = explode('\\', $targetName);

        return end($name);
    }
}
