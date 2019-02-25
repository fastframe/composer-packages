<?php

/**
 * @file
 * Contains \FastFrame\Composer\Packages\Package
 */

namespace FastFrame\Composer\Packages;

use Psr\Container\ContainerInterface;

/**
 * Allows to interact with the packages as if they were part of a container, and in some cases a repository
 *
 * @package FastFrame\Composer\Packages
 */
class Container
	implements ContainerInterface
{
	/**
	 * @var Package[] Map of packages
	 */
	protected $packages;

	/**
	 * @var string[] Map of types to associated packages
	 */
	protected $types;

	public function __construct(array $packages = array(), array $types = array())
	{
		$this->packages = empty($packages) ? Packages::PACKAGES : $packages;
		$this->types    = empty($types) ? Packages::TYPES : $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($id)
	{
		$id = strtolower($id);

		if (array_key_exists($id, $this->packages)) {
			if (!is_object($this->packages[$id])) {
				$this->packages[$id]['extra'] = $this->convertToObject($this->packages[$id]['extra']);
				$this->packages[$id]          = Package::fromDefinition($this->packages[$id]);
			}

			return $this->packages[$id];
		}

		throw new NotFoundException("Package not found: $id");
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($id)
	{
		return array_key_exists(strtolower($id), $this->packages);
	}

	/**
	 * @param $type
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
	 * @param $ary
	 * @return \stdClass
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