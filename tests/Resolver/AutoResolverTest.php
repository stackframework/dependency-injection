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

use Stack\DependencyInjection\Injection\NewLazyObject;

/**
 * Class AutoResolverTest.
 */
class AutoResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AutoResolver
     */
    protected $resolver;

    public function setUp()
    {
        parent::setUp();
        $this->resolver = new AutoResolver(new Reflector());
    }

    public function testMissingParam()
    {
        $actual = $this->resolver->resolve('Stack\DependencyInjection\Fixtures\ResolveClassFixture');
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ParentClassFixture', $actual->params['fake']);
    }

    public function testAutoResolveExplicit()
    {
        $this->resolver->set([
            'Stack\DependencyInjection\Fixtures\ParentClassFixture' => new newLazyObject(
                $this->resolver,
                'Stack\DependencyInjection\Fixtures\ChildClassFixture'
            ),
        ]);

        $actual = $this->resolver->resolve('Stack\DependencyInjection\Fixtures\ResolveClassFixture');
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ChildClassFixture', $actual->params['fake']);
    }

    /**
     * @expectedException \Stack\DependencyInjection\Exception\MissingParam
     */
    public function testAutoResolveMissingParam()
    {
        $this->resolver->resolve('Stack\DependencyInjection\Fixtures\ParamsClassFixture');
    }
}
