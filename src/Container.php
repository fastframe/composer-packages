<?php

/**
 * @file
 * Contains \FastFrame\Composer\Packages\Container
 */

namespace FastFrame\Composer\Packages;

use function getcwd;
use Psr\Container\ContainerInterface;

/**
 * Allows to interact with the packages as if they were part of a container, and in some cases a repository
 *
 * @package FastFrame\Composer\Packages
 */
class Container
	implements ContainerInterface
{
	const PATH_KEY  = 3;
	const EXTRA_KEY = 4;

	/**
	 * @var array Map of packages
	 */
	protected $packages;

	/**
	 * @var string[][] Map of types to associated packages
	 */
	protected $types;

	/**
	 * @var string The path to the root of the project
	 */
	protected $path;

	public function __construct(string $path = null, array $packages = array(), array $types = array())
	{
		$this->path     = static::resolvePath($path);
		$this->packages = empty($packages) ? Packages::PACKAGES : $packages;
		$this->types    = empty($types) ? Packages::TYPES : $types;
	}

	/**
	 * Resolves the path by using the path or getcwd
	 *
	 * @param ?string $path The path if set
	 *
	 * @return string The path or current working directory
	 */
	protected static function resolvePath(?string $path): string
	{
		if (empty($path) && empty($path = getcwd())) {
			throw new \RuntimeException("No path specified and getcwd() failed");
		}

		return str_replace('\\', '/', (string)$path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $id)
	{
		$id = strtolower($id);

		if (array_key_exists($id, $this->packages)) {
			if (!is_object($this->packages[$id])) {
				$this->packages[$id][self::EXTRA_KEY] = $this->convertToObject($this->packages[$id][self::EXTRA_KEY]);
				$this->packages[$id][self::PATH_KEY]  = $this->path . $this->packages[$id][self::PATH_KEY];
				$this->packages[$id]                  = Package::fromDefinition($this->packages[$id]);
			}

			return $this->packages[$id];
		}

		throw new NotFoundException("Package not found: $id");
	}

	/**
	 * {@inheritdoc}
	 */
	public function has(string $id): bool
	{
		return array_key_exists(strtolower($id), $this->packages);
	}

	/**
	 * @param mixed $type
	 *
	 * @return string[] List of packages for the given type
	 */
	public function getByType($type): array
	{
		return isset($this->types[$type])
			? $this->types[$type]
			: array();
	}

	/**
	 * Converts the passed in ary in to an object
	 *
	 * NOTES:
	 *  - this converts keys with - in them to in to _ : branch-alias > branch_alias to make it easier to use
	 *
	 * @param mixed $ary
	 *
	 * @return mixed
	 */
	protected function convertToObject($ary)
	{
		if (!is_array($ary)) {
			return $ary;
		}

		$ret = new \stdClass();
		foreach ($ary as $key => $value) {
			$key       = str_replace('-', '_', $key);
			$ret->$key = is_array($value) ? $this->convertToObject($value) : $value;
		}

		return $ret;
	}
}