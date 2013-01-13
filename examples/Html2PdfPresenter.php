<?php


class Html2PdfPresenter extends Nette\Application\UI\Presenter
{
	public function renderDefault($html)
	{
		$document = new Wkhtmltopdf\Document($this->context->parameters['tempDir']);
		$document->addHtml($html);
		$this->sendResponse($document);
	}
}
