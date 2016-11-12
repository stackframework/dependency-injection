<?php
/**
 * Created by PhpStorm.
 * User: Andrzej
 * Date: 2016-11-12
 * Time: 20:24
 */

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
