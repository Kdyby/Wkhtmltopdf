<?php

require_once __DIR__ . '/../vendor/autoload.php';

$tempDir = __DIR__;
$document = new Wkhtmltopdf\Document($tempDir);

$document->header->center = '[date]';
$document->footer->right = 'Page: [page]';

$html = <<<HTML
<h1>Lorem ipsum</h1>

<p>dolor sit amet, consectetur adipiscing elit.</p>
HTML;
$document->addHtml($html);

$document->save(__FILE__ . '.pdf');
