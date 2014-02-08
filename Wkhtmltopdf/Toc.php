<?php

namespace Wkhtmltopdf;

use Nette\Object;


/**
 * @author Ladislav Marek <ladislav@marek.su>
 */
class Toc extends Object implements IDocumentPart
{

	/** @var string */
	public $header = 'Table of contents';

	/** @var float */
	public $headersSizeShrink = 0.9;

	/** @var string */
	public $indentationLevel = '1em';


	/**
	 * @param  Document
	 * @return string
	 */
	public function buildShellArgs(Document $document)
	{
		return ' toc --toc-header-text ' . escapeshellarg($this->header)
			. ' --toc-level-indentation ' . escapeshellarg($this->indentationLevel)
			. ' --toc-text-size-shrink ' . number_format($this->headersSizeShrink, 4, '.', '');
	}

}
