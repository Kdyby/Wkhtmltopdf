<?php

namespace Wkhtmltopdf;



interface IDocumentPart
{
	/**
	 * @param  Document
	 * @return string
	 */
	function buildShellArgs(Document $document);
}
