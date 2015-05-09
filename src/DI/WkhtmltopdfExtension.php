<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf\DI;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class WkhtmltopdfExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = array(
		'executable' => NULL, // autodetect
		'tempDir' => '%tempDir%/wkhtmltopdf',
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('documentFactory'))
			->setClass('Kdyby\Wkhtmltopdf\DocumentFactory', [
				$config['tempDir'],
				$config['executable'],
			]);
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('wkhtmltopdf', new WkhtmltopdfExtension());
		};
	}

}
