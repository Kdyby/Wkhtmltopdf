<?php

namespace Wkhtmltopdf;

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

	/** @var string	NULL means autodetect */
	public static $executable;

	/** @var array	possible executables */
	public static $executables = array('wkhtmltopdf', 'wkhtmltopdf-amd64', 'wkhtmltopdf-i386');

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

	/** @var resource */
	private $p;

	/** @var array */
	private $pipes;


	/**
	 * @param string
	 */
	public function __construct($tmpDir)
	{
		$this->tmpDir = $tmpDir;
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

		$output = fgets($this->pipes[1], 5);
		if ($output === '%PDF') {
			$httpResponse->setContentType('application/pdf');
			if (strpos($httpRequest->getHeader('User-Agent'), 'MSIE') != FALSE) {
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
	 * @param  string
	 * @throws InvalidStateException
	 */
	public function save($file)
	{
		$f = fopen($file, 'w');
		$this->convert();
		stream_copy_to_stream($this->pipes[1], $f);
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
			$s = stream_get_contents($this->pipes[1]);
			$this->close();
			return $s;
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}


	private function convert()
	{
		if (self::$executable === NULL) {
			self::$executable = $this->detectExecutable() ?: FALSE;
		}

		if (self::$executable === FALSE) {
			throw new InvalidStateException('Cannot found Wkhtmltopdf executable');
		}

		$m = $this->margin;
		$cmd = self::$executable . ' -q --disable-smart-shrinking --disable-internal-links'
			. ' -T ' . escapeshellarg($m[0])
			. ' -R ' . escapeshellarg($m[1])
			. ' -B ' . escapeshellarg($m[2])
			. ' -L ' . escapeshellarg($m[3])
			. ' --dpi ' . escapeshellarg($this->dpi)
			. ' --orientation ' . escapeshellarg($this->orientation)
			. ' --title ' . escapeshellarg($this->title);

		if (is_array($this->size)) {
			$cmd .= ' --page-width ' . escapeshellarg($this->size[0]);
			$cmd .= ' --page-height ' . escapeshellarg($this->size[1]);

		} else {
			$cmd .= ' --page-size ' . escapeshellarg($this->size);
		}

		if ($this->header !== NULL) {
			$cmd .= ' ' . $this->header->buildShellArgs($this);
		}
		if ($this->footer !== NULL) {
			$cmd .= ' ' . $this->footer->buildShellArgs($this);
		}
		foreach ($this->pages as $page) {
			$cmd .= ' ' . $page->buildShellArgs($this);
		}
		$this->p = $this->openProcess($cmd . ' -', $this->pipes);
	}


	/**
	 * Returns path to executable.
	 * @return string
	 */
	protected function detectExecutable()
	{
		foreach (self::$executables as $exec) {
			if (proc_close($this->openProcess("$exec -v", $tmp)) === 1) {
				return $exec;
			}
		}
	}


	private function openProcess($cmd, & $pipes)
	{
		static $spec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		return proc_open($cmd, $spec, $pipes);
	}


	private function close()
	{
		stream_get_contents($this->pipes[1]); // wait for process
		$error = stream_get_contents($this->pipes[2]);
		if (proc_close($this->p) > 0) {
			throw new InvalidStateException($error);
		}
		foreach ($this->tmpFiles as $file) {
			@unlink($file);
		}
		$this->tmpFiles = array();
	}

}
