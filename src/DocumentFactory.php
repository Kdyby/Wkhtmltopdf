<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DocumentFactory extends Nette\Object
{

	/**
	 * @var string
	 */
	private $tempDir;

	/**
	 * @var string
	 */
	private $executable;



	/**
	 * @param string $tempDir
	 * @param string $executable
	 */
	public function __construct($tempDir, $executable)
	{
		Nette\Utils\FileSystem::createDir($tempDir);

		$this->tempDir = $tempDir;
		$this->executable = $executable;
	}



	/**
	 * @return Document
	 */
	public function create()
	{
		return new Document($this->tempDir, $this->executable);
	}

}
