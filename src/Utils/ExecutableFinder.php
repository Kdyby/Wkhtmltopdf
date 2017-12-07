<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Kdyby\Wkhtmltopdf\Utils;

use Kdyby;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExecutableFinder
{
	use Kdyby\StrictObjects\Scream;

	/** @var array */
	public static $executables = [
		'wkhtmltopdf',
		'wkhtmltopdf-amd64',
		'wkhtmltopdf-i386'
	];

	/** @var array */
	public static $lookupPaths = [
		'/usr/local/sbin',
		'/usr/local/bin',
		'/usr/sbin',
		'/usr/bin',
		'/sbin',
		'/bin',
	];


	/**
	 * @return string
	 * @throws \RuntimeException
	 */
	public function __toString(): string
	{
		$tmp = [];

		// check if binary is accessible using $PATH
		foreach (self::$executables as $name) {
			if (proc_close(self::openProcess("$name -v", $tmp)) === 1) {
				return $name;
			}
		}

		// try to find it
		foreach (self::$executables as $name) {
			foreach (self::$lookupPaths as $path) {
				$binary = $path . '/' . $name;
				if (is_executable($binary)) {
					return $binary;
				}
			}
		}

		trigger_error('Please specify path to the wkhtmltopdf binary, it couldn\'t be autodetected', E_USER_WARNING);
		return '';
	}


	/**
	 * @param string
	 * @param array
	 * @return resource
	 */
	private static function openProcess(string $cmd, array &$pipes): resource
	{
		static $spec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		return proc_open($cmd, $spec, $pipes);
	}
}
