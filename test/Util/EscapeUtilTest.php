<?php

namespace Dreitier\Util;

use Dreitier\Test\BasicTestCase;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class EscapeUtilTest extends BasicTestCase
{
	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withHarmfulTag_removeHarmfulTag()
	{
		$raw = 'hi<iframe src="http://www.w3schools.com"></iframe>';

		$expected = 'hi';
		$actual = EscapeUtil::escapeHarmfulHtml($raw);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withMultipleHarmfulTags_removeAll()
	{
		$raw = 'hi<iframe></iframe><applet></applet><script></script><style></style><link><a></a><form></form>' .
			'<input><video></video><form></form><math></math><picture></picture><img><map></map><svg></svg>' .
			'<details></details><frameset></frameset><embed></embed><object></object><javascript></javascript>';

		$expected = 'hi';
		$actual = EscapeUtil::escapeHarmfulHtml($raw);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withHarmlessTag_keepHarmlessTag()
	{
		$raw = '<strong>hi</strong>';

		$expected = $raw;
		$actual = EscapeUtil::escapeHarmfulHtml($raw);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withArray_escapeStringsInArrray()
	{
		$raw = array(
			'<strong>hi</strong>',
			'<em>what?</em><iframe>',
			'key' => '<applet>',
			'evil' => '<javascript><code>hack</code></javascript>'
		);

		$expected = array(
			'<strong>hi</strong>',
			'<em>what?</em>',
			'key' => '',
			'evil' => '<code>hack</code>'
		);

		$actual = EscapeUtil::escapeHarmfulHtml($raw);
		$this->assertEquals($expected, $actual);
	}
}