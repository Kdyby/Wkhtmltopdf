<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2011 Ladislav Marek <ladislav@marek.su>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;

use Kdyby;
use Nette;


/**
 * @author Ladislav Marek <ladislav@marek.su>
 */
class PageMeta implements Kdyby\Wkhtmltopdf\IDocumentPart
{
	use Nette\SmartObject;

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
	public $line = false;

	/** @var string */
	public $fontName;

	/** @var string */
	public $fontSize;

	/** @var int */
	public $spacing = 0;


	/**
	 * @param string
	 */
	public function __construct(string $type)
	{
		$this->type = $type;
	}


	/**
	 * @param Document
	 * @return string
	 */
	public function buildShellArgs(Kdyby\Wkhtmltopdf\Document $document): string
	{
		$file = $this->file;

		if ($file === null && $this->html !== null) {
			$file = $document->saveTempFile((string) $this->html);
		}

		if ($file !== null) {
			$cmd = "--$this->type-html " . escapeshellarg($file);

		} else {
			$cmd = "--$this->type-left " . escapeshellarg($this->left)
				. " --$this->type-center " . escapeshellarg($this->center)
				. " --$this->type-right " . escapeshellarg($this->right);
		}

		$cmd .= ' --' . ($this->line ? '' : 'no-') . "$this->type-line";
		if ($this->fontName !== null) {
			$cmd .= " --$this->type-font-name " . escapeshellarg($this->fontName);
		}

		if ($this->fontSize !== null) {
			$cmd .= " --$this->type-font-size " . escapeshellarg($this->fontSize);
		}

		return $cmd;
	}
}
