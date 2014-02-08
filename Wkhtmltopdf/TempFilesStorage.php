<?php

namespace Wkhtmltopdf;

use Nette\Object;


class TempFilesStorage extends Object
{

	/** @var string */
	public $directory;

	/** @var array */
	private $files = array();



	/**
	 * @param string
	 */
	public function __construct($directory)
	{
		$this->directory = $directory;
	}


	/**
	 * @param string
	 * @return string newly created file name
	 */
	public function save($content)
	{
		do {
			$file = $this->directory . '/' . md5($content . '.' . lcg_value()) . '.html';
			$handle = fopen($file, 'x');
		} while ($handle === FALSE);

		fwrite($handle, $content);
		fclose($handle);
		return $this->files[] = $file;
	}


	public function clear()
	{
		foreach ($this->files as $file) {
			@unlink($file);
		}
		$this->files = array();
	}

}
