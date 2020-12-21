<?php

/**
 * @file
 * Contains \FastFrame\Composer\Packages\Package
 */

namespace FastFrame\Composer\Packages;

/**
 * Composer Package
 *
 * @package FastFrame\Composer\Packages
 */
class Package
{
	/**
	 * @var array The Package definition data
	 */
	protected $data;

	/**
	 * @var object The decoded composer.json file
	 */
	protected $composer_json;

	/**
	 * @param array $def
	 * @return Package  The Package object
	 */
	public static function fromDefinition(array $def)
	{
		$pkg       = new self;
		$pkg->data = $def;

		return $pkg;
	}

	/**
	 * @return string The name of the package
	 */
	public function name(): string
	{
		return $this->data[0];
	}

	/**
	 * @return string The type of composer package
	 */
	public function type(): string
	{
		return $this->data[1];
	}

	/**
	 * @return string The package version
	 */
	public function version(): string
	{
		return $this->data[2];
	}

	/**
	 * @return string The absolute path to the package
	 */
	public function path(): string
	{
		return $this->data[3];
	}

	/**
	 * @return object The JSON of the "extra" argument if it exists
	 */
	public function extra()
	{
		return $this->data[4];
	}

	/**
	 * @return object The composer.json
	 */
	public function composerJson()
	{
		return $this->composer_json ?? ($this->composer_json = $this->decodeComposerJson());
	}

	protected function decodeComposerJson(): object
	{
		$data = file_get_contents($file = "{$this->data[3]}/composer.json");
		if ($data === false) {
			throw new \RuntimeException("Unable to read composer.json for {$this->data[0]}");
		}

		return json_decode($data);
	}
}