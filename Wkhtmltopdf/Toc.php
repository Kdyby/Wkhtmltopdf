<?php

namespace Wkhtmltopdf;

use Nette\Object;



class Toc extends Object implements IDocumentPart
{
	public $header = 'Table of contents';

	public $headersSizeShring = 0.9;

	public $indentationLevel = '1em';



	public function buildShellArgs(Document $document)
	{
		return ' toc --toc-header-text ' . escapeshellarg($this->header)
			. ' --toc-level-indentation ' . escapeshellarg($this->indentationLevel)
			. ' --toc-text-size-shrink ' . number_format($this->headersSizeShring, 4, '.', '');
	}
}
