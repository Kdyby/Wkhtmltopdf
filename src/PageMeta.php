<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2011 Ladislav Marek <ladislav@marek.su>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;

use Nette\Object;


/**
 * @author Ladislav Marek <ladislav@marek.su>
 */
class PageMeta extends Object implements IDocumentPart
{

	/** @var string */
	private $type;

	/** @var string */
	public $left;

	/** @var string */
	public $center;

	/** @var string */
	public $right;

	/** @var string */
	public $file;

	/** @var string */
	public $html;

	/** @var bool */
	public $line = FALSE;

	/** @var string */
	public $fontName;

	/** @var string */
	public $fontSize;

	/** @var int */
	public $spacing = 0;


	/**
	 * @param string
	 */
	public function __construct($type)
	{
		$this->type = $type;
	}


	/**
	 * @param  Document
	 * @return array
	 */
	public function buildShellArgs(Document $document)
	{
		$args = [];

		$file = $this->file;
		if ($file === NULL && $this->html !== NULL) {
			$file = $document->saveTempFile((string) $this->html);
		}

		if ($file !== NULL) {
			$args["--$this->type-html"] = $file;

		} else {
			$args["--$this->type-left"] = (string) $this->left;
			$args["--$this->type-center"] = (string) $this->center;
			$args["--$this->type-right"] = (string) $this->right;
		}

		$args['--' . ($this->line ? '' : 'no-') . "$this->type-line"] = NULL;
		if ($this->fontName !== NULL) {
			$args["--$this->type-font-name"] = $this->fontName;
		}
		if ($this->fontSize !== NULL) {
			$args["--$this->type-font-size"] = $this->fontSize;
		}

		return $args;
	}

}
