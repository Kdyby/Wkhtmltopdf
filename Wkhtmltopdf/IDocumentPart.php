<?php

namespace Wkhtmltopdf;


/**
 * @author Ladislav Marek <ladislav@marek.su>
 */
interface IDocumentPart
{

	/**
	 * @param  Document
	 * @return string
	 */
	function buildShellArgs(Document $document);

}
