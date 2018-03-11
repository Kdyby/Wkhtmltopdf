<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

declare(strict_types=1);

namespace Kdyby\Wkhtmltopdf\DI;

use Kdyby;
use Nette;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class WkhtmltopdfExtension extends Nette\DI\CompilerExtension
{
	/** @var array */
	public $defaults = [
		'executable' => null,// autodetect
		'tempDir' => '%tempDir%/wkhtmltopdf',
	];


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($config['executable'] === null) {
			$config['executable'] = (string) new Kdyby\Wkhtmltopdf\Utils\ExecutableFinder();
		}

		$builder->addDefinition($this->prefix('documentFactory'))
			->setClass('Kdyby\Wkhtmltopdf\DocumentFactory', [
				$config['tempDir'],
				$config['executable'],
			]);
	}


	public static function register(Nette\Configurator $configurator): void
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('wkhtmltopdf', new WkhtmltopdfExtension());
		};
	}
}
