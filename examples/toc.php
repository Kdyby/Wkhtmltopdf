<?php

require_once __DIR__ . '/../vendor/autoload.php';

$tempDir = __DIR__;
$document = new Wkhtmltopdf\Document($tempDir);

$document->addToc('My TOC');

$html = <<<HTML
<h1>1. header</h1>

<h2>A header</h1>

<h3>B header</h1>

<h1>2. header</h1>
HTML;
$document->addHtml($html);

$document->save(__FILE__ . '.pdf');
