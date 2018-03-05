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
class Toc implements Kdyby\Wkhtmltopdf\IDocumentPart
{
	use Kdyby\StrictObjects\Scream;

	/** @var string */
	public $header = 'Table of contents';

	/** @var string */
	public $indentationLevel = '1em';

	/** @var float */
	public $headersSizeShrink = 0.9;


	/**
	 * @param Kdyby\Wkhtmltopdf\Document
	 * @return array
	 */
	public function buildShellArgs(Document $document): array
	{
		return [
			'--toc-header-text' => $this->header,
			'--toc-level-indentation' => $this->indentationLevel,
			'--toc-text-size-shrink' => number_format($this->headersSizeShrink, 4, '.', ''),
		];
	}
}
