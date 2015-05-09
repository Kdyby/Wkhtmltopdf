<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2011 Ladislav Marek <ladislav@marek.su>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;

use Nette\Object,
	Nette\Application\IResponse,
	Nette\Http,
	Nette\InvalidStateException;


/**
 * @property-read PageMeta $header
 * @property-read PageMeta $footer
 *
 * @author Ladislav Marek <ladislav@marek.su>
 */
class Document extends Object implements IResponse
{

	/** @var int */
	public $dpi = 200;

	/** @var array */
	public $margin = array(10, 10, 10, 10);

	/** @var string */
	public $orientation = 'portrait';

	/** @var string */
	public $size = 'A4';

	/** @var string */
	public $title;

	/** @var string */
	public $encoding;

	/** @var bool */
	public $usePrintMediaType = TRUE;

	/** @var string */
	public $styleSheet;

	/** @var PageMeta */
	private $header;

	/** @var PageMeta */
	private $footer;

	/** @var array|Page[] */
	private $pages = array();

	/** @var string */
	public $tmpDir;

	/** @var array */
	private $tmpFiles = array();

	/** @var Process */
	private $process;



	/**
	 * @param string $tmpDir
	 * @param string $executable
	 */
	public function __construct($tmpDir, $executable = NULL)
	{
		$this->tmpDir = $tmpDir;
		$this->process = new Process($executable);
	}


	/**
	 * @return PageMeta
	 */
	public function getHeader()
	{
		if ($this->header === NULL) {
			$this->header = new PageMeta('header');
		}
		return $this->header;
	}


	/**
	 * @return PageMeta
	 */
	public function getFooter()
	{
		if ($this->footer === NULL) {
			$this->footer = new PageMeta('footer');
		}
		return $this->footer;
	}


	/**
	 * @param  string
	 * @param  bool
	 * @return Page
	 */
	public function addHtml($html, $isCover = FALSE)
	{
		$this->pages[] = $page = $this->createPage();
		$page->html = $html;
		$page->isCover = $isCover;
		return $page;
	}


	/**
	 * @param string
	 * @param bool
	 * @return Page
	 */
	public function addFile($file, $isCover = FALSE)
	{
		$this->pages[] = $page = $this->createPage();
		$page->file = $file;
		$page->isCover = $isCover;
		return $page;
	}


	/**
	 * @param  string
	 * @return Toc
	 */
	public function addToc($header = NULL)
	{
		$this->pages[] = $toc = new Toc;
		if ($header !== NULL) {
			$toc->header = $header;
		}
		return $toc;
	}


	/**
	 * @param  IDocumentPart
	 * @return Document
	 */
	public function addPart(IDocumentPart $part)
	{
		$this->pages[] = $part;
		return $this;
	}


	/**
	 * @return Page
	 */
	private function createPage()
	{
		$page = new Page;
		$page->encoding = $this->encoding;
		$page->usePrintMediaType = $this->usePrintMediaType;
		$page->styleSheet = $this->styleSheet;
		return $page;
	}


	/**
	 * @internal
	 * @param  string
	 * @return string
	 */
	public function saveTempFile($content)
	{
		do {
			$file = $this->tmpDir . '/' . md5($content . '.' . lcg_value()) . '.html';
		} while (file_exists($file));
		file_put_contents($file, $content);
		return $this->tmpFiles[] = $file;
	}


	/**
	 * Send headers and outputs PDF document to browser.
	 * @throws InvalidStateException
	 */
	public function send(Http\IRequest $httpRequest, Http\IResponse $httpResponse)
	{
		$this->convert();

		$output = $this->process->getOutput(5);
		if ($output === '%PDF') {
			$httpResponse->setContentType('application/pdf');
			if (strpos($httpRequest->getHeader('User-Agent'), 'MSIE') != FALSE) {
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
	 * @param  string
	 * @throws InvalidStateException
	 */
	public function save($file)
	{
		$f = fopen($file, 'w');
		$this->convert();
		$this->process->copyOutputTo($f);
		fclose($f);
		$this->close();
	}


	/**
	 * Returns PDF document as string.
	 * @return string
	 */
	public function __toString()
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


	private function convert()
	{
		$args = [
			'-q' => NULL,
			'--disable-smart-shrinking' => NULL,
			'--disable-internal-links' => NULL,
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

		if ($this->header !== NULL) {
			$args[] = $this->header->buildShellArgs($this);
		}
		if ($this->footer !== NULL) {
			$args[] = $this->footer->buildShellArgs($this);
		}
		foreach ($this->pages as $page) {
			$args[] = $page->buildShellArgs($this);
		}

		$this->process->open($args);
	}


	private function close()
	{
		$this->process->close();
		foreach ($this->tmpFiles as $file) {
			@unlink($file);
		}
		$this->tmpFiles = array();
	}

}
