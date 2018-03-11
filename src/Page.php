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
class Page implements Kdyby\Wkhtmltopdf\IDocumentPart
{
	use Kdyby\StrictObjects\Scream;

	/** @var string */
	public $file;

	/** @var string */
	public $html;

	/** @var bool */
	public $isCover = false;

	/** @var string */
	public $encoding;

	/** @var bool */
	public $usePrintMediaType = true;

	/** @var string */
	public $styleSheet;

	/** @var string */
	public $javascript;

	/** @var int */
	public $zoom = 1;


	public function buildShellArgs(Document $document): array
	{
		$args = [];
		$file = $this->file;

		if ($file === null) {
			$file = $document->saveTempFile((string) $this->html);
		}

		if ($this->isCover) {
			$args['cover'] = null;
		}

		$args[] = $file;

		if ($this->encoding) {
			$args['--encoding'] = $this->encoding;
		}

		if ($this->usePrintMediaType) {
			$args['--print-media-type'] = null;
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
