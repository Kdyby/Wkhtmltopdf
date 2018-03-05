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
use Kdyby\Wkhtmltopdf\Document;
use Kdyby\Wkhtmltopdf\Utils\ExecutableFinder;
use Kdyby\Wkhtmltopdf\Utils\XvfbWrapper;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FunctionalTest extends Tester\TestCase
{

	/**
	 * @var string
	 */
	private $binary;



	protected function setup()
	{
		$this->binary = new XvfbWrapper((string) new ExecutableFinder());
	}



	public function testRender_addFile()
	{
		$document = new Document(TEMP_DIR, $this->binary);
		$document->addFile(__DIR__ . '/files/sample-1.html');
		$document->save($pdfFile = TEMP_DIR . '/sample-1.pdf');

		Assert::true(filesize($pdfFile) > 0);
	}



	public function testRender_addHtml()
	{
		$document = new Document(TEMP_DIR, $this->binary);
		$document->addHtml(file_get_contents(__DIR__ . '/files/sample-1.html'));
		$document->save($pdfFile = TEMP_DIR . '/sample-2.pdf');

		Assert::true(filesize($pdfFile) > 0);
	}

}

$test = new FunctionalTest;
$test->run();
