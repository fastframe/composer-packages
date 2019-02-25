<?php

/**
 * @file
 * Contains \FastFrame\Composer\Packages\NotFoundException
 */

namespace FastFrame\Composer\Packages;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException
	extends \Exception
	implements NotFoundExceptionInterface
{
	// defaults
}