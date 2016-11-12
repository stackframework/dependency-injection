<?php
/**
 * Created by PhpStorm.
 * User: Andrzej
 * Date: 2016-11-12
 * Time: 20:44
 */

namespace Stack\DependencyInjection;

use Stack\DependencyInjection\Fixtures\OtherClassFixture;
use Stack\DependencyInjection\Fixtures\ParentClassFixture;
use Stack\DependencyInjection\Injection\InjectionFactory;
use Stack\DependencyInjection\Resolver\Reflector;
use Stack\DependencyInjection\Resolver\Resolver;

/**
 * Class ContainerTest.
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;
    
    public function setUp()
    {
        parent::setUp();
        $this->container = ContainerBuilder::buildDevContainer();
    }
    
    public function testHasGet()
    {
        $expect = (object) [];
        $this->container->set('foo', $expect);
        $this->assertTrue($this->container->has('foo'));
        $this->assertFalse($this->container->has('bar'));

        $actual = $this->container->get('foo');
        $this->assertSame($expect, $actual);

        $again = $this->container->get('foo');
        $this->assertSame($actual, $again);
    }

    /**
     * @expectedException \Stack\DependencyInjection\Exception\ServiceNotFound
     */
    public function testGetNoSuchService()
    {
        $this->container->get('foo');
    }
    
    public function testGetServiceInsideClosure()
    {
        $di = $this->container;
        $di->set('foo', function () use ($di) {
            return new ParentClassFixture();
        });

        $actual = $this->container->get('foo');
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ParentClassFixture', $actual);
    }
    
    public function testGetLazy()
    {
        $this->container->set('foo', function () {
            return new OtherClassFixture();
        });

        $lazy = $this->container->getLazy('foo');
        $this->assertInstanceOf('Stack\DependencyInjection\Injection\GetLazyObject', $lazy);
        $foo = $lazy();
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\OtherClassFixture', $foo);
    }
    
    public function testGetWithDefaults()
    {
        $instance = $this->container->get('Stack\DependencyInjection\Fixtures\ParentClassFixture');
        $expect = 'bar';
        $actual = $instance->getFoo();
        $this->assertSame($expect, $actual);
    }
    
    public function testMake()
    {
        $instance = $this->container->make(
            'Stack\DependencyInjection\Fixtures\ParentClassFixture',
            ['foo' => 'other']
        );

        $expect = 'other';
        $actual = $instance->getFoo();
        $this->assertSame($expect, $actual);
    }
    
    public function testMakeWithSetter()
    {
        $instance = $this->container->make(
            'Stack\DependencyInjection\Fixtures\ChildClassFixture',
            [
                'foo'   => 'other',
                'other' => new OtherClassFixture(),
            ],
            [
                'setFake' => 'fake',
            ]
        );

        $expect = 'fake';
        $actual = $instance->getFake();
        $this->assertSame($expect, $actual);
    }
    
    public function testMakeWithLazySetter()
    {
        $di     = $this->container;
        $actual = $this->container->make(
            'Stack\DependencyInjection\Fixtures\ChildClassFixture',
            [
                'foo' => 'bar',
                new OtherClassFixture(),
            ],
            [
                'setFake' => $di->getLazy('Stack\DependencyInjection\Fixtures\OtherClassFixture'),
            ]
        );
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\OtherClassFixture', $actual->getFake());
    }

    /**
     * @expectedException \Stack\DependencyInjection\Exception\SetterMethodNotFound
     */
    public function testMakeWithNonExistentSetter()
    {
        $this->container->make(
            'Stack\DependencyInjection\Fixtures\OtherClassFixture',
            [],
            ['setFakeNotExists' => 'fake']
        );
    }
    
    public function testMakeWithPositionalParams()
    {
        $other  = $this->container->get('Stack\DependencyInjection\Fixtures\OtherClassFixture');
        $actual = $this->container->make('Stack\DependencyInjection\Fixtures\ChildClassFixture', [
            'foofoo',
            $other,
        ]);

        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ChildClassFixture', $actual);
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\OtherClassFixture', $actual->getOther());
        $this->assertSame('foofoo', $actual->getFoo());

        $actual = $this->container->make('Stack\DependencyInjection\Fixtures\ChildClassFixture', [
            0     => 'keepme',
            'foo' => 'bad',
            $other,
        ]);

        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ChildClassFixture', $actual);
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\OtherClassFixture', $actual->getOther());
        $this->assertSame('keepme', $actual->getFoo());
    }
    
    public function testCall()
    {
        $lazy = $this->container->call('Stack\DependencyInjection\Fixtures\ParentClassFixture', 'mirror', 'foo');
        $this->assertInstanceOf('Stack\DependencyInjection\Injection\LazyObject', $lazy);
        $actual = $lazy();
        $expect = 'foo';
        $this->assertSame($expect, $actual);

        $di   = $this->container;
        $lazy = $this->container->call(
            'Stack\DependencyInjection\Fixtures\ParentClassFixture',
            'mirror',
            $di->getLazy('Stack\DependencyInjection\Fixtures\OtherClassFixture')
        );

        $this->assertInstanceOf('Stack\DependencyInjection\Injection\LazyObject', $lazy);
        $actual = $lazy();
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\OtherClassFixture', $actual);
    }
    
    public function testResolveWithMissingParam()
    {
        $this->setExpectedException(
            'Stack\DependencyInjection\Exception\MissingParam',
            'Stack\DependencyInjection\Fixtures\ResolveClassFixture::$fake'
        );

        $builder = new ContainerBuilder();
        $builder->useAutowiring(false);

        $container = $builder->build();
        $container->get('Stack\DependencyInjection\Fixtures\ResolveClassFixture');
    }
    
    public function testResolveWithMissingParams()
    {
        $this->setExpectedException(
            'Stack\DependencyInjection\Exception\MissingParam',
            'Stack\DependencyInjection\Fixtures\ResolveClassFixture1::$foo'
        );

        $di      = $this->container;
        $builder = new ContainerBuilder();
        $builder->definition([
            'Stack\DependencyInjection\Fixtures\OtherClassFixture' => $di->getLazy('Stack\DependencyInjection\Fixtures\OtherClassFixture'),
        ]);

        $container = $builder->build();
        $container->make(
            'Stack\DependencyInjection\Fixtures\ResolveClassFixture1',
            ['fake' => $di->getLazy('Stack\DependencyInjection\Fixtures\ParentClassFixture')]
        );
    }
    
    public function testResolveWithoutMissingParam()
    {
        $di      = $this->container;
        $builder = new ContainerBuilder();
        $builder->definition([
            'fake' => $di->getLazy('Stack\DependencyInjection\Fixtures\ParentClassFixture'),
        ]);

        $builder->useAutowiring(false);

        $container = $builder->build();
        $actual = $container->get('Stack\DependencyInjection\Fixtures\ResolveClassFixture');
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ResolveClassFixture', $actual);
    }
    
    public function testUseAnnotation()
    {
        $di      = $this->container;
        $builder = new ContainerBuilder();
        $builder->definition([
            'fake' => $di->getLazy('Stack\DependencyInjection\Fixtures\ParentClassFixture'),
        ]);

        $builder->useAnnotation(true);

        $container = $builder->build();
        $actual = $container->get('Stack\DependencyInjection\Fixtures\ResolveClassFixture');
        $this->assertInstanceOf('Stack\DependencyInjection\Fixtures\ResolveClassFixture', $actual);
    }
    
    public function testDependencyLookupSimple()
    {
        $delegateContainer = ContainerBuilder::buildDevContainer();
        $delegateContainer->set('foo', function () {
            $obj = new \stdClass();
            $obj->foo = 'bar';
            return $obj;
        });

        $container = new Container(new InjectionFactory(new Resolver(new Reflector())), $delegateContainer);
        $lazy      = $container->getLazy('foo');
        $this->assertInstanceOf('Stack\DependencyInjection\Injection\GetLazyObject', $lazy);
        $foo = $lazy();
        $this->assertInstanceOf('stdClass', $foo);
        $this->assertEquals('bar', $foo->foo);
        $actual = $container->delegateContainer();
        $this->assertSame($delegateContainer, $actual);

        $builder = new ContainerBuilder();
        $builder->delegateContainer($delegateContainer);

        $container = $builder->build();
        $actual    = $container->delegateContainer();
        $this->assertSame($delegateContainer, $actual);
    }
    
    public function testHonorsInterfacesAndOverrides()
    {
        $resolver = new Resolver(new Reflector());
        $resolver->addSetters(['Stack\DependencyInjection\Fixtures\InterfaceFixture' => ['setFoo' => 'initial']]);
        $resolver->addSetters(['Stack\DependencyInjection\Fixtures\InterfaceClass2Fixture' => ['setFoo' => 'override']]);
        $container = new Container(new InjectionFactory($resolver));

        $actual    = $container->get('Stack\DependencyInjection\Fixtures\InterfaceClassFixture');
        $this->assertSame('initial', $actual->getFoo());

        $actual = $container->get('Stack\DependencyInjection\Fixtures\InterfaceClass1Fixture');
        $this->assertSame('initial', $actual->getFoo());

        $actual = $container->get('Stack\DependencyInjection\Fixtures\InterfaceClass2Fixture');
        $this->assertSame('override', $actual->getFoo());

        $actual = $container->get('Stack\DependencyInjection\Fixtures\InterfaceClass3Fixture');
        $this->assertSame('override', $actual->getFoo());
    }
}
