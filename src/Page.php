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
	 * @return array
	 */
	public function buildShellArgs(Document $document)
	{
		$file = $this->file;
		if ($file === NULL) {
			$file = $document->saveTempFile((string) $this->html);
		}

		$args = [];
		if ($this->isCover) {
			$args['cover'] = NULL;
		}

		$args[] = $file;

		if ($this->encoding) {
			$args['--encoding'] = $this->encoding;
		}
		if ($this->usePrintMediaType) {
			$args['--print-media-type'] = NULL;
		}
		if ($this->styleSheet) {
			$args['--user-style-sheet'] = $this->styleSheet;
		}
		if ($this->javascript) {
			$args['--run-script'] = $this->javascript;
		}
		$args['--zoom'] = $this->zoom * 1;

		return $args;
	}

}
