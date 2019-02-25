<?php

namespace FastFrame\Composer\Packages;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class PluginTest
	extends TestCase
{
	/**
	 * @var vfsStreamDirectory
	 */
	protected $vfs;

	/**
	 * @var Plugin
	 */
	protected $plugin;

	public function setUp()
	{
		$this->plugin = new Plugin();

		$this->vfs = vfsStream::setup(
			'root',
			null,
			array(
				'.env'   => 'config=1',
				'vendor' => array(
					'fastframe' => array(
						'composer-packages' => array(
							'src' => array()
						)
					)
				)
			)
		);

		$this->io       = $this->createMock(IOInterface::class);
		$this->composer = $this->createMock(Composer::class);
		$this->events   = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();

		$this->composer->expects(self::any())->method('getEventDispatcher')->willReturn(EventDispatcher::class);
	}

	protected function generatePackage($name, $type = 'library')
	{
		$pkg = new \Composer\Package\Package($name, '1.0.0', 'v1.0');
		$pkg->setType($type);

		return $pkg;
	}

	public function testGetSubscribedEvents()
	{
		self::assertEquals(
			array(
				'post-install-cmd' => 'dumpPackages',
				'post-update-cmd'  => 'dumpPackages',
			),
			Plugin::getSubscribedEvents()
		);
	}

	public function testDumpPackages()
	{
		$config         = $this->createMock(Config::class);
		$repo           = $this->createMock(RepositoryManager::class);
		$installManager = $this->createMock(Installer\InstallationManager::class);
		$repository     = $this->createMock(InstalledRepositoryInterface::class);

		$t2 = $this->generatePackage("test/test-two");
		$t2->setExtra(array('branch-alias' => array('test' => 'key')));
		$rootPackage = $this->generatePackage('test/test');
		$packages    = array(
			$this->generatePackage("test/test-one"),
			$t2,
			$this->generatePackage("fastframe/composer-packages", 'composer-plugin'),
		);

		// build up the dump
		$this->composer->method('getConfig')->willReturn($config);
		$this->composer->method('getInstallationManager')->willReturn($installManager);
		$this->composer->method('getPackage')->willReturn($rootPackage);
		$this->composer->method('getRepositoryManager')->willReturn($repo);
		$repo->method('getLocalRepository')->willReturn($repository);
		$repository->method('getPackages')->willReturn($packages);

		$rootPath = $this->vfs->url();
		$installManager->method('getInstallPath')->willReturnCallback(
			function (\Composer\Package\Package $pkg) use ($rootPath) {
				return "$rootPath/vendor/" . $pkg->getName();
			}
		);

		$config->method('get')->with('vendor-dir')->willReturn($this->vfs->url());

		Plugin::dumpPackages(
			new Event(
				'post-install-cmd',
				$this->composer,
				$this->io
			)
		);

		$rootPath = $this->vfs->url();
		$file     = "{$rootPath}/vendor/fastframe/composer-packages/src/Packages.php";
		self::assertFileExists($file);

		$data = array();
		foreach (explode("\n", file_get_contents($file)) as $line) {
			if (strpos($line, ' * @date') === 0) {
				continue;
			}
			$data[] = $line;
		}

		self::assertEquals(
			file_get_contents(__DIR__ . '/_files/plugin-test-dump.txt'),
			join("\n", $data)
		);
	}
}