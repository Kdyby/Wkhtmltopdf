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
	 * @return string
	 */
	public function buildShellArgs(Document $document): string
	{
		if ($this->file === null && $this->html !== null) {
			$this->file = $document->saveTempFile((string) $this->html);
		}

		if ($this->file !== null) {
			$cmd = "--$this->type-html " . escapeshellarg($this->file);

		} else {
			$cmd = "--$this->type-left " . escapeshellarg((string) $this->left)
				. " --$this->type-center " . escapeshellarg((string) $this->center)
				. " --$this->type-right " . escapeshellarg((string) $this->right);
		}

		$cmd .= ' --' . ($this->line ? '' : 'no-') . "$this->type-line";
		if ($this->fontName !== null) {
			$cmd .= " --$this->type-font-name " . escapeshellarg((string) $this->fontName);
		}

		if ($this->fontSize !== null) {
			$cmd .= " --$this->type-font-size " . escapeshellarg((string) $this->fontSize);
		}

		return $cmd;
	}
}
