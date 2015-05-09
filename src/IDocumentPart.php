<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2011 Ladislav Marek <ladislav@marek.su>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;


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
