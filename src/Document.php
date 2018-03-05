<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2011 Ladislav Marek <ladislav@marek.su>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Kdyby\Wkhtmltopdf;

use Kdyby;
use Nette;


/**
 * @author Ladislav Marek <ladislav@marek.su>
 */
class Document implements Nette\Application\IResponse
{
	use Kdyby\StrictObjects\Scream;

	/** @var int */
	public $dpi = 200;

	/** @var array */
	public $margin = [10, 10, 10, 10];

	/** @var string */
	public $orientation = 'portrait';

	/** @var string */
	public $size = 'A4';

	/** @var string */
	public $title = '';

	/** @var string */
	public $encoding;

	/** @var bool */
	public $usePrintMediaType = true;

	/** @var string */
	public $styleSheet;

	/** @var string */
	public $tmpDir;

	/** @var Kdyby\Wkhtmltopdf\PageMeta */
	private $header;

	/** @var Kdyby\Wkhtmltopdf\PageMeta */
	private $footer;

	/** @var array */
	private $pages = [];

	/** @var array */
	private $tmpFiles = [];

	/** @var Kdyby\Wkhtmltopdf\Process */
	private $process;


	/**
	 * @param string
	 * @param string
	 */
	public function __construct(string $tmpDir, string $executable = null)
	{
		$this->tmpDir = $tmpDir;
		$this->process = new Process($executable);
	}


	/**
	 * @return Kdyby\Wkhtmltopdf\PageMeta
	 */
	public function getHeader(): Kdyby\Wkhtmltopdf\PageMeta
	{
		if ($this->header === null) {
			$this->header = new PageMeta('header');
		}

		return $this->header;
	}


	/**
	 * @return Kdyby\Wkhtmltopdf\PageMeta
	 */
	public function getFooter(): Kdyby\Wkhtmltopdf\PageMeta
	{
		if ($this->footer === null) {
			$this->footer = new PageMeta('footer');
		}

		return $this->footer;
	}


	/**
	 * @param string|Nette\Bridges\ApplicationLatte\Template
	 * @param bool
	 * @return Kdyby\Wkhtmltopdf\Page
	 */
	public function addHtml($html, bool $isCover = false): Kdyby\Wkhtmltopdf\Page
	{
		if ($html instanceof Nette\Bridges\ApplicationLatte\Template) {// $html property without type hint => prevent BC break
			$html = $html->__toString();
		}

		$this->pages[] = $page = $this->createPage();
		$page->html = $html;
		$page->isCover = $isCover;

		return $page;
	}


	/**
	 * @param string
	 * @param bool
	 * @return Kdyby\Wkhtmltopdf\Page
	 */
	public function addFile(string $file, bool $isCover = false): Kdyby\Wkhtmltopdf\Page
	{
		$this->pages[] = $page = $this->createPage();
		$page->file = $file;
		$page->isCover = $isCover;

		return $page;
	}


	/**
	 * @param string
	 * @return Kdyby\Wkhtmltopdf\Toc
	 */
	public function addToc(string $header = null): Kdyby\Wkhtmltopdf\Toc
	{
		$this->pages[] = $toc = new Toc;

		if ($header !== null) {
			$toc->header = $header;
		}

		return $toc;
	}


	/**
	 * @param Kdyby\Wkhtmltopdf\IDocumentPart
	 * @return Kdyby\Wkhtmltopdf\Document
	 */
	public function addPart(IDocumentPart $part): Kdyby\Wkhtmltopdf\Document
	{
		$this->pages[] = $part;
		return $this;
	}


	/**
	 * @return Kdyby\Wkhtmltopdf\Page
	 */
	private function createPage(): Kdyby\Wkhtmltopdf\Page
	{
		$page = new Page;
		$page->encoding = $this->encoding;
		$page->usePrintMediaType = $this->usePrintMediaType;
		$page->styleSheet = $this->styleSheet;

		return $page;
	}


	/**
	 * @internal
	 * @param string
	 * @return string
	 */
	public function saveTempFile(string $content): string
	{
		Nette\Utils\FileSystem::createDir($this->tmpDir);

		do {
			$file = $this->tmpDir . '/' . md5($content . '.' . lcg_value()) . '.html';
		} while (file_exists($file));

		file_put_contents($file, $content);
		return $this->tmpFiles[] = $file;
	}


	/**
	 * Send headers and outputs PDF document to browser.
	 *
	 * @return void
	 * @throws Nette\InvalidStateException
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
	{
		$this->convert();

		$output = $this->process->getOutput(5);
		if ($output === '%PDF') {
			$httpResponse->setContentType('application/pdf');

			if (strpos($httpRequest->getHeader('User-Agent'), 'MSIE') != false) {
				$httpResponse->setHeader('Pragma', 'private');
				$httpResponse->setHeader('Cache-control', 'private');
				$httpResponse->setHeader('Accept-Ranges', 'bytes');
				$httpResponse->setExpiration('- 5 years');
			}

			echo $output;
			$this->process->printOutput();
		}

		$this->close();
	}


	/**
	 * Save PDF document to file.
	 *
	 * @param string
	 * @return void
	 * @throws Nette\InvalidStateException
	 */
	public function save(string $file): void
	{
		$f = fopen($file, 'w');
		$this->convert();
		$this->process->copyOutputTo($f);
		fclose($f);
		$this->close();
	}


	/**
	 * Returns PDF document as string.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		try {
			$this->convert();
			$s = $this->process->getOutput();
			$this->close();
			return $s;

		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}


	/**
	 * @return void
	 */
	private function convert(): void
	{
		$args = [
			'-q' => null,
			'--disable-smart-shrinking' => null,
			'--disable-internal-links' => null,
			'-T' => $this->margin[0],
			'-R' => $this->margin[1],
			'-B' => $this->margin[2],
			'-L' => $this->margin[3],
			'--dpi' => $this->dpi,
			'--orientation' => (string) $this->orientation,
			'--title' => (string) $this->title,
		];

		if (is_array($this->size)) {
			$args['--page-width'] = $this->size[0];
			$args['--page-height'] = $this->size[1];

		} else {
			$args['--page-size'] = $this->size;
		}

		if ($this->header !== null) {
			$args[] = $this->header->buildShellArgs($this);
		}

		if ($this->footer !== null) {
			$args[] = $this->footer->buildShellArgs($this);
		}

		foreach ($this->pages as $page) {
			$args[] = $page->buildShellArgs($this);
		}

		$this->process->open($args);
	}


	/**
	 * @return void
	 */
	private function close(): void
	{
		$this->process->close();

		foreach ($this->tmpFiles as $file) {
			try {
				Nette\Utils\FileSystem::delete($file);

			} catch (Nette\IOException $e) {
			}
		}

		$this->tmpFiles = [];
	}
}
