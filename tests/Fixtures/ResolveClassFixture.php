<?php
/**
 * This file is part of the Stack package.
 *
 * (c) Andrzej Kostrzewa <andkos11@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stack\DependencyInjection\Fixtures;

class ResolveClassFixture
{
    public $fake;

    public function __construct(ParentClassFixture $fake)
    {
        $this->fake = $fake;
    }
}
