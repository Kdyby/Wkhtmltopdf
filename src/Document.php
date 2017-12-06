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
 * @property-read Kdyby\Wkhtmltopdf\PageMeta $header
 * @property-read Kdyby\Wkhtmltopdf\PageMeta $footer
 *
 * @author Ladislav Marek <ladislav@marek.su>
 */
class Document implements Nette\Application\IResponse
{
	use Kdyby\StrictObjects\Scream;

	/** @var string	null means autodetect */
	public static $executable;

	/** @var array possible executables */
	public static $executables = ['wkhtmltopdf', 'wkhtmltopdf-amd64', 'wkhtmltopdf-i386'];

	/** @var string */
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

	/** @var array| */
	private $pages = [];

	/** @var array */
	private $tmpFiles = [];

	/** @var resource */
	private $p;

	/** @var array */
	private $pipes;


	/**
	 * @param string
	 */
	public function __construct(string $tmpDir)
	{
		$this->tmpDir = $tmpDir;
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

		$output = fgets($this->pipes[1], 5);
		if ($output === '%PDF') {
			$httpResponse->setContentType('application/pdf');

			if (strpos($httpRequest->getHeader('User-Agent'), 'MSIE') != false) {
				$httpResponse->setHeader('Pragma', 'private');
				$httpResponse->setHeader('Cache-control', 'private');
				$httpResponse->setHeader('Accept-Ranges', 'bytes');
				$httpResponse->setExpiration('- 5 years');
			}

			echo $output;
			fpassthru($this->pipes[1]);
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
		stream_copy_to_stream($this->pipes[1], $f);
		fclose($f);
		$this->close();
	}


	/**
	 * Returns PDF document as string.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		try {
			$this->convert();
			$s = stream_get_contents($this->pipes[1]);
			$this->close();
			return $s;

		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}


	/**
	 * @return void
	 * @throw Nette\InvalidStateException
	 */
	private function convert()
	{
		if (self::$executable === null) {
			self::$executable = $this->detectExecutable() ?: false;
		}

		if (self::$executable === false) {
			throw new Nette\InvalidStateException('Cannot found Wkhtmltopdf executable');
		}

		$m = $this->margin;
		$cmd = self::$executable . ' -q --disable-smart-shrinking --disable-internal-links'
			. ' -T ' . escapeshellarg((string) $m[0])
			. ' -R ' . escapeshellarg((string) $m[1])
			. ' -B ' . escapeshellarg((string) $m[2])
			. ' -L ' . escapeshellarg((string) $m[3])
			. ' --dpi ' . escapeshellarg((string) $this->dpi)
			. ' --orientation ' . escapeshellarg((string) $this->orientation)
			. ' --title ' . escapeshellarg($this->title);

		if (is_array($this->size)) {
			$cmd .= ' --page-width ' . escapeshellarg($this->size[0]);
			$cmd .= ' --page-height ' . escapeshellarg($this->size[1]);

		} else {
			$cmd .= ' --page-size ' . escapeshellarg($this->size);
		}

		if ($this->header !== null) {
			$cmd .= ' ' . $this->header->buildShellArgs($this);
		}

		if ($this->footer !== null) {
			$cmd .= ' ' . $this->footer->buildShellArgs($this);
		}

		foreach ($this->pages as $page) {
			$cmd .= ' ' . $page->buildShellArgs($this);
		}

		$this->p = $this->openProcess($cmd . ' -', $this->pipes);
	}


	/**
	 * Returns path to executable.
	 * 
	 * @return void
	 */
	protected function detectExecutable()
	{
		foreach (self::$executables as $exec) {
			if (proc_close($this->openProcess("$exec -v", $tmp)) === 1) {
				return $exec;
			}
		}
	}


	/**
	 * @param string
	 * @param array
	 * @return resource
	 */
	private function openProcess($cmd, &$pipes)
	{
		static $spec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		return proc_open($cmd, $spec, $pipes);
	}


	/**
	 * @return void
	 */
	private function close(): void
	{
		stream_get_contents($this->pipes[1]); // wait for process
		$error = stream_get_contents($this->pipes[2]);

		if (proc_close($this->p) > 0) {
			throw new Nette\InvalidStateException($error);
		}

		foreach ($this->tmpFiles as $file) {
			try {
				Nette\Utils\FileSystem::delete($file);

			} catch (Nette\IOException $e) {
			}
		}

		$this->tmpFiles = [];
	}
}
