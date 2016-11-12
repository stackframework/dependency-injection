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

namespace Stack\DependencyInjection\Exception;

/**
 * A constructor parameter is missing.
 *
 * @author Andrzej Kostrzewa <andkos11@gmail.com>
 */
final class MissingParam extends \InvalidArgumentException
{
}
