<?php

require_once __DIR__ . '/../vendor/autoload.php';

$tempDir = __DIR__;
$document = new Wkhtmltopdf\Document($tempDir);

$html = <<<HTML
<html>
<head>
	<title>Lorem ipsum</title>
</head>
<body>
	<h1>Lorem ipsum</h1>

	<p>dolor sit amet, consectetur adipiscing elit.</p>
</body>
HTML;
$document->addHtml($html);

$document->save(__FILE__ . '.pdf');
