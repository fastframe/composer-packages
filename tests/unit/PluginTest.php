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
	 * @var IOInterface
	 */
	protected $io;

	public function setUp(): void
	{
		$this->vfs = vfsStream::setup(
			'root',
			null,
			array(
				'.env'   => 'config=1',
				'vendor' => array(
					'fastframe' => array(
						'composer-packages' => array(
							'src' => array(
								'Packages.php' => ''
							)
						)
					)
				)
			)
		);

		$this->io       = $this->createMock(IOInterface::class);
	}

	protected function generatePackage($name, $type = 'library')
	{
		$pkg = new \Composer\Package\Package($name, '1.0.0', 'v1.0');
		$pkg->setType($type);

		return $pkg;
	}

	public function testSkipsSelfAsRoot()
	{
		$composer = $this->buildComposer($this->generatePackage(Plugin::COMPOSER_NAME), []);
		$composer->expects($this->never())->method('getRepositoryManager');
		Plugin::dumpPackages(new Event('post-install-cmd', $composer, $this->io));
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

	protected function buildComposer($package, $packages)
	{
		$config         = $this->createMock(Config::class);
		$repo           = $this->createMock(RepositoryManager::class);
		$installManager = $this->createMock(Installer\InstallationManager::class);
		$repository     = $this->createMock(InstalledRepositoryInterface::class);
		$composer       = $this->createMock(Composer::class);
		$events         = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();

		// build up the dump
		$composer->method('getEventDispatcher')->willReturn($events);
		$composer->method('getConfig')->willReturn($config);
		$composer->method('getInstallationManager')->willReturn($installManager);
		$composer->method('getPackage')->willReturn($package);
		$composer->method('getRepositoryManager')->willReturn($repo);
		$repo->method('getLocalRepository')->willReturn($repository);
		$repository->method('getPackages')->willReturn($packages);

		$rootPath = $this->vfs->url();
		$installManager->method('getInstallPath')->willReturnCallback(
			function (\Composer\Package\Package $pkg) use ($rootPath) {
				return "$rootPath/vendor/" . $pkg->getName();
			}
		);

		$config->method('get')->with('vendor-dir')->willReturn($this->vfs->url());

		return $composer;
	}

	public function testDumpPackages()
	{
		$t2 = $this->generatePackage("test/test-two");
		$t2->setExtra(array('branch-alias' => array('test' => 'key')));

		$composer = $this->buildComposer(
			$this->generatePackage('test/test'),
			array(
				$this->generatePackage("test/test-one"),
				$t2,
				$this->generatePackage("fastframe/composer-packages", 'composer-plugin'),
			)
		);

		$rootPath = $this->vfs->url();
		putenv("COMPOSER={$rootPath}");
		Plugin::dumpPackages(new Event('post-install-cmd', $composer, $this->io));
		putenv("COMPOSER=");

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