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

use Stack\DependencyInjection\Fixtures\ChildClassFixture;
use Stack\DependencyInjection\Fixtures\OtherClassFixture;

/**
 * Class ResolverTest.
 */
class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Resolver
     */
    protected $resolver;

    public function setUp()
    {
        parent::setUp();
        $this->resolver = new Resolver(new Reflector());
    }

    public function testReadsConstructorDefaults()
    {
        $expect        = ['foo' => 'bar'];
        $actual_params = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $this->assertSame($expect, $actual_params[0]);
    }

    public function testTwiceForMerge()
    {
        $expect = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $actual = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $this->assertSame($expect, $actual);
    }

    public function testHonorsParentParams()
    {
        $expect = [
            'foo'   => 'bar',
            'other' => null,
        ];
        $actual_params = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ChildClassFixture');
        $this->assertSame($expect, $actual_params[0]);
    }

    public function testHonorsExplicitParamsName()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addParams(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['foo' => 'zim']]);

        $expect        = ['foo' => 'zim'];
        $actual_params = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $this->assertSame($expect, $actual_params[0]);
    }

    public function testHonorsExplicitParamsNumber()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addParams(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['bar']]);

        $expect        = ['foo' => 'bar'];
        $actual_params = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $this->assertSame($expect, $actual_params[0]);
    }

    public function testHonorsExplicitParentParams()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addParams(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['dib']]);

        $expect = [
            'foo'   => 'dib',
            'other' => null,
        ];
        $actual_params = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ChildClassFixture');
        $this->assertSame($expect, $actual_params[0]);

        $child = new ChildClassFixture('bar', new OtherClassFixture());
        $child->getFoo();
    }

    public function testHonorsParentSetter()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addSetters(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['setFake' => 'fake1']]);
        $actual_setter = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ChildClassFixture');
        $expect        = ['setFake' => 'fake1'];
        $this->assertSame($expect, $actual_setter[1]);
    }

    public function testHonorsOverrideSetter()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addSetters(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['setFake' => 'fake1']]);
        $this->resolver->addSetters(['Stack\DependencyInjection\Fixtures\ParentClassFixture' => ['setFake' => 'fake2']]);
        $actual_setter = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ChildClassFixture');
        $expect        = ['setFake' => 'fake2'];
        $this->assertSame($expect, $actual_setter[1]);
    }
    
    public function testHonorsTraitSetter()
    {
        $this->resolver = new Resolver(new Reflector());
        $this->resolver->addSetters(['Stack\DependencyInjection\Fixtures\FakeTrait' => ['setFake' => 'fake1']]);
        $actual_setter = $this->resolver->unifiedClass('Stack\DependencyInjection\Fixtures\ClassWithTraitFixture');
        $expect        = ['setFake' => 'fake1'];
        $this->assertSame($expect, $actual_setter[1]);
    }
}
