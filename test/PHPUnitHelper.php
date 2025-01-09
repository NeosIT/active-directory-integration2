<?php
namespace Dreitier\Test;


use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * @see https://github.com/sebastianbergmann/phpunit/issues/4026#issuecomment-1418205424
 */
trait PHPUnitHelper
{
	/**
	 * @param array<mixed> $firstCallArguments
	 * @param array<mixed> ...$consecutiveCallsArguments
	 *
	 * @return iterable<Callback<mixed>>
	 */
	public static function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): iterable
	{
		foreach ($consecutiveCallsArguments as $consecutiveCallArguments) {
			self::assertSameSize($firstCallArguments, $consecutiveCallArguments, 'Each expected arguments list need to have the same size.');
		}

		$allConsecutiveCallsArguments = [$firstCallArguments, ...$consecutiveCallsArguments];

		$numberOfArguments = count($firstCallArguments);
		$argumentList      = [];
		for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; $argumentPosition++) {
			$argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
		}

		$mockedMethodCall = 0;
		$callbackCall     = 0;
		foreach ($argumentList as $index => $argument) {
			yield new Callback(
				static function (mixed $actualArgument) use ($argumentList, &$mockedMethodCall, &$callbackCall, $index, $numberOfArguments): bool {
					$expected = $argumentList[$index][$mockedMethodCall] ?? null;

					$callbackCall++;
					$mockedMethodCall = (int) ($callbackCall / $numberOfArguments);

					if ($expected instanceof Constraint) {
						self::assertThat($actualArgument, $expected);
					} else {
						self::assertEquals($expected, $actualArgument);
					}

					return true;
				},
			);
		}
	}
}
