<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Normalizer;

use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class GetSetMethodNormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->normalizer = new GetSetMethodNormalizer;
        $this->normalizer->setSerializer($this->getMock('Symfony\Component\Serializer\Serializer'));
    }

    public function testNormalize()
    {
        $obj = new GetSetDummy;
        $obj->setFoo('foo');
        $obj->setBar('bar');
        $this->assertEquals(
            array('foo' => 'foo', 'bar' => 'bar', 'fooBar' => 'foobar'),
            $this->normalizer->normalize($obj, 'any')
        );
    }

    public function testDenormalize()
    {
        $obj = $this->normalizer->denormalize(
            array('foo' => 'foo', 'bar' => 'bar', 'fooBar' => 'foobar'),
            __NAMESPACE__.'\GetSetDummy',
            'any'
        );
        $this->assertEquals('foo', $obj->getFoo());
        $this->assertEquals('bar', $obj->getBar());
    }

    public function testConstructorDenormalize()
    {
        $obj = $this->normalizer->denormalize(
            array('foo' => 'foo', 'bar' => 'bar', 'fooBar' => 'foobar'),
            __NAMESPACE__.'\GetConstructorDummy', 'any');
        $this->assertEquals('foo', $obj->getFoo());
        $this->assertEquals('bar', $obj->getBar());
    }
}

class GetSetDummy
{
    protected $foo;
    private $bar;

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }

    public function getFooBar()
    {
        return $this->foo . $this->bar;
    }

    public function otherMethod()
    {
        throw new \RuntimeException("Dummy::otherMethod() should not be called");
    }
}

class GetConstructorDummy
{
    protected $foo;
    private $bar;

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function otherMethod()
    {
        throw new \RuntimeException("Dummy::otherMethod() should not be called");
    }
}
