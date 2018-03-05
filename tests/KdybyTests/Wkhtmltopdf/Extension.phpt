<?php

/**
 * Test: Kdyby\Translation\Extension.
 *
 * @testCase KdybyTests\Translation\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	public function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		Kdyby\Wkhtmltopdf\DI\WkhtmltopdfExtension::register($config);

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$sl = $this->createContainer();

		Assert::type('Kdyby\Wkhtmltopdf\DocumentFactory', $sl->getService('wkhtmltopdf.documentFactory'));
	}

}

$test = new ExtensionTest;
$test->run();
