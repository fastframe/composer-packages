<?php

namespace FastFrame\Composer\Packages;

use PHPUnit\Framework\TestCase;

class ContainerTest
	extends TestCase
{
	/**
	 * @var Container
	 */
	protected $container;

	protected function setUp(): void
	{
		$this->container = new Container(
			null,
			array('test/test' => array('test/test', 'library', '/somewhere', '1.0.0.0', array())),
			array('library' => array('test/test'))
		);
	}

	public function testHas()
	{
		self::assertTrue($this->container->has('test/test'));
		self::assertFalse($this->container->has('test/nope'));
	}

	public function testGet()
	{
		$pkg = $this->container->get('test/test');
		self::assertInstanceOf(Package::class, $pkg);
	}

	public function testGetThrowsNotFoundExceptionOnInvalidPackage()
	{
		$this->expectException(NotFoundException::class);
		$this->container->get('test/nope');
	}

	public function testGetByType()
	{
		$libs = $this->container->getByType('library');
		self::assertIsArray($libs);
		self::assertEquals(array('test/test'), $libs);
	}

	public function testGetByTypeReturnsEmptyArrayWhenNotFound()
	{
		$ary = $this->container->getByType('no-lib-here');
		self::assertIsArray($ary);
		self::assertEmpty($ary);
	}

	public function testExtraKeysUseUnderscores()
	{
		$this->container = new Container(
			null,
			array('test/test' => array('test/test', 'library', '/somewhere', '1.0.0.0', array('test-key' => 'test'))),
			array('library' => array('test/test'))
		);
		$extra           = $this->container->get('test/test')->extra();
		self::assertTrue(isset($extra->test_key));
	}

	public function testExtraReturnsExtraAsIs()
	{
		$this->container = new Container(
			null,
			array('test/test' => array('test/test', 'library', '/somewhere', '1.0.0.0', 'kakaw')),
			array('library' => array('test/test'))
		);
		self::assertEquals('kakaw', $this->container->get('test/test')->extra());
	}
}