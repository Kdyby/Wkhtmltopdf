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
class Page extends Object implements IDocumentPart
{
	/** @var string */
	public $file;

	/** @var string */
	public $html;

	/** @var bool */
	public $isCover = FALSE;

	/** @var string */
	public $encoding;

	/** @var bool */
	public $usePrintMediaType = TRUE;

	/** @var string */
	public $styleSheet;

	/** @var string */
	public $javascript;

	/** @var int */
	public $zoom = 1;


	/**
	 * @param  Document
	 * @return string
	 */
	public function buildShellArgs(Document $document)
	{
		$file = $this->file;
		if ($file === NULL) {
			$file = $document->saveTempFile((string) $this->html);
		}

		return ($this->isCover ? ' cover ' : ' ')
			. escapeshellarg($file)
			. ($this->encoding ? ' --encoding ' . escapeshellarg($this->encoding) : '')
			. ($this->usePrintMediaType ? ' --print-media-type' : '')
			. ($this->styleSheet ? ' --user-style-sheet ' . escapeshellarg($this->styleSheet) : '')
			. ($this->javascript ? ' --run-script ' . escapeshellarg($this->javascript) : '')
			. ' --zoom ' . ($this->zoom * 1);
	}

}
