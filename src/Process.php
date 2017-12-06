<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Wkhtmltopdf;

use Kdyby;
use Nette;



/**
 * @author Ladislav Marek <ladislav@marek.su>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Process extends Nette\Object
{

	/**
	 * @var string    NULL means autodetect
	 */
	private $executable;

	/**
	 * @var resource
	 */
	private $p;

	/**
	 * @var array
	 */
	private $pipes;

	/**
	 * @var string
	 */
	private $executedCommand;



	public function __construct($executable = NULL)
	{
		$this->executable = $executable;
	}



	public function open(array $args)
	{
		if ($this->executable === NULL) {
			$this->executable = (string) new Utils\ExecutableFinder();
		}

		if (!$this->executable) {
			throw new \RuntimeException("Please specify path to the wkhtmltopdf binary, it couldn't be autodetected");
		}

		$cmd = $this->executable;

		array_walk_recursive($args, function ($value, $arg) use (&$cmd) {
			if ($value === NULL) { // option like -q
				$cmd .= sprintf(' %s', $arg);

			} elseif (is_numeric($arg)) { // argument
				$cmd .= sprintf(' %s', escapeshellarg($value));

			} else { // option with value
				$cmd .= sprintf(' %s %s', $arg, is_numeric($value) ? $value : escapeshellarg($value));
			}
		});

		static $spec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		$this->p = proc_open($this->executedCommand = $cmd . ' -', $spec, $this->pipes);
	}



	public function printOutput()
	{
		fpassthru($this->pipes[1]);
	}



	/**
	 * @param int $length
	 * @return string
	 */
	public function getOutput($length = NULL)
	{
		if ($length !== NULL) {
			return fgets($this->pipes[1], $length);
		}

		return stream_get_contents($this->pipes[1]);
	}



	/**
	 * @param resource $stream
	 */
	public function copyOutputTo($stream)
	{
		stream_copy_to_stream($this->pipes[1], $stream);
	}



	/**
	 * @return string
	 */
	public function getErrorOutput()
	{
		return stream_get_contents($this->pipes[2]);
	}



	/**
	 * @throws \RuntimeException
	 */
	public function close()
	{
		$this->getOutput(); // wait for process
		$error = $this->getErrorOutput();
		if (proc_close($this->p) > 0) {
			$msg = $this->executedCommand . "\n\n" . $error;
			throw new \RuntimeException($msg);
		}
	}

}
