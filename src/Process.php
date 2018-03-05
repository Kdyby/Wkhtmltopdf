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
class Process
{
	use Kdyby\StrictObjects\Scream;

	/** @var string null means autodetect */
	private $executable;

	/** @var resource */
	private $p;

	/** @var array */
	private $pipes;

	/** @var string */
	private $executedCommand;


	/**
	 * @param string
	 */
	public function __construct(string $executable = null)
	{
		$this->executable = $executable;
	}


	/**
	 * @param array
	 * @return void
	 * @throws RuntimeException
	 */
	public function open(array $args): void
	{
		if ($this->executable === null) {
			$this->executable = (string) new Utils\ExecutableFinder();
		}

		if (!$this->executable) {
			throw new \RuntimeException("Please specify path to the wkhtmltopdf binary, it couldn't be autodetected");
		}

		$cmd = $this->executable;

		array_walk_recursive($args, function ($value, $arg) use (&$cmd) {
			if ($value === null) { // option like -q
				$cmd .= sprintf(' %s', $arg);

			} elseif (is_numeric($arg)) { // argument
				$cmd .= sprintf(' %s', escapeshellarg($value));

			} else { // option with value
				$cmd .= sprintf(' %s %s', $arg, is_numeric($value) ? $value : escapeshellarg($value));
			}
		});

		static $spec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$this->p = proc_open($this->executedCommand = $cmd . ' -', $spec, $this->pipes);
	}


	/**
	 * @return void
	 */
	public function printOutput(): void
	{
		fpassthru($this->pipes[1]);
	}


	/**
	 * @param int
	 * @return string
	 */
	public function getOutput(int $length = null): string
	{
		if ($length !== null) {
			return fgets($this->pipes[1], $length);
		}

		return stream_get_contents($this->pipes[1]);
	}


	/**
	 * @param resource
	 * @return void
	 */
	public function copyOutputTo($stream): void
	{
		stream_copy_to_stream($this->pipes[1], $stream);
	}


	/**
	 * @return string
	 */
	public function getErrorOutput(): string
	{
		return stream_get_contents($this->pipes[2]);
	}


	/**
	 * @return void
	 * @throws \RuntimeException
	 */
	public function close(): void
	{
		$this->getOutput();// wait for process
		$error = $this->getErrorOutput();
		if (proc_close($this->p) > 0) {
			$msg = $this->executedCommand . "\n\n" . $error;
			throw new \RuntimeException($msg);
		}
	}
}
