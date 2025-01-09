<?php
namespace Dreitier\Test;

/**
 * @see https://github.com/sebastianbergmann/phpunit/issues/5320
 *
 * @template T
 */
interface CallableMock
{
	/**
	 * @return T
	 */
	public function __invoke();

	/**
	 * @return T
	 */
	public function __call(string $name, array $arguments);
}