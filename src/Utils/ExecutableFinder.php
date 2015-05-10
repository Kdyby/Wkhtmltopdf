<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf\Utils;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExecutableFinder extends Nette\Object
{

	/**
	 * @var array
	 */
	public static $executables = array(
		'wkhtmltopdf',
		'wkhtmltopdf-amd64',
		'wkhtmltopdf-i386'
	);

	/**
	 * @var array
	 */
	public static $lookupPaths = array(
		'/usr/local/sbin',
		'/usr/local/bin',
		'/usr/sbin',
		'/usr/bin',
		'/sbin',
		'/bin',
	);



	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function __toString()
	{
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

		trigger_error("Please specify path to the wkhtmltopdf binary, it couldn't be autodetected", E_USER_WARNING);
		return '';
	}



	private static function openProcess($cmd, & $pipes)
	{
		static $spec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		return proc_open($cmd, $spec, $pipes);
	}

}
