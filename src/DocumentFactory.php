<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Kdyby\Wkhtmltopdf;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DocumentFactory
{
	use Kdyby\StrictObjects\Scream;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $executable;


	public function __construct(string $tempDir, string $executable)
	{
		$this->tempDir = $tempDir;
		$this->executable = $executable;
	}


	public function create(): Kdyby\Wkhtmltopdf\Document
	{
		return new Document($this->tempDir, $this->executable);
	}
}
