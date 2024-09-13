<?php

namespace Dreitier\WordPress;

use Dreitier\Test\BasicTest;

/**
 * @author Christopher Klein <ckl@dreitier.com>
 * @access private
 */
class WordPressErrorExceptionTest extends BasicTest
{
	/**
	 * This test has to be run in a separate process:
	 * WordPressErrorException is already mocked through other tests when running the whole testsuite.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @issue #178
	 */
	public function GH_178_processWordPressError_doesNotResultInClassNotFoundException()
	{
		$error = new \WP_Error("fail", "fail");
		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($error),
			'times' => 1,
			'return' => true,
		));

		try {
			WordPressErrorException::processWordPressError($error);
		} catch (\Exception $e) {
			$this->assertTrue($e instanceof WordPressErrorException);
		}
	}
}