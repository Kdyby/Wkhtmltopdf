<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Kdyby\Wkhtmltopdf\Utils;

use Kdyby;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class XvfbWrapper
{
	use Kdyby\StrictObjects\Scream;

	/** @var string */
	private $executable;


	public function __construct(string $executable)
	{
		$this->executable = $executable;
	}


	public function __toString(): string
	{
		return sprintf('xvfb-run -a --server-args="-screen 0, 1024x768x24" %s', $this->executable);
	}
}
