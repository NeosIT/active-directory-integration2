<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn;


use Dreitier\Test\BasicTestCase;

/**
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 * @author  Stefan Fiedler <sfi@neos.it.de>
 *
 * @access
 */
class VariableTest extends BasicTestCase
{
	/**
	 * @test
	 */
	public function getValues_returnsExpectedResult()
	{
		$expected = array(
			Variable::REMOTE_USER,
			Variable::X_REMOTE_USER,
			Variable::HTTP_X_REMOTE_USER,
			Variable::PHP_AUTH_USER
		);

		$actual = Variable::getValues();

		$this->assertEquals($expected, $actual);
	}
}