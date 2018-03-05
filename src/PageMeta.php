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
class PageMeta implements Kdyby\Wkhtmltopdf\IDocumentPart
{
	use Kdyby\StrictObjects\Scream;

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
	public $line = false;

	/** @var string */
	public $fontName;

	/** @var string */
	public $fontSize;

	/** @var int */
	public $spacing = 0;

	/** @var string */
	private $type;


	/**
	 * @param string
	 */
	public function __construct(string $type)
	{
		$this->type = $type;
	}


	/**
	 * @param Kdyby\Wkhtmltopdf\Document
	 * @return array
	 */
	public function buildShellArgs(Document $document): array
	{
		$args = [];
		$file = $this->file;

		if ($file === null && $this->html !== null) {
			$file = $document->saveTempFile((string) $this->html);
		}

		if ($file !== null) {
			$args["--$this->type-html"] = $file;

		} else {
			$args["--$this->type-left"] = (string) $this->left;
			$args["--$this->type-center"] = (string) $this->center;
			$args["--$this->type-right"] = (string) $this->right;
		}

		$args['--' . ($this->line ? '' : 'no-') . "$this->type-line"] = null;

		if ($this->fontName !== null) {
			$args["--$this->type-font-name"] = $this->fontName;
		}

		if ($this->fontSize !== null) {
			$args["--$this->type-font-size"] = $this->fontSize;
		}

		return $args;
	}
}
