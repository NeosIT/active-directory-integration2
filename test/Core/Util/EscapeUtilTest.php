<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Core_Util_EscapeUtilTest extends Ut_BasicTest
{
    /**
     * @test
     */
    public function escapeHarmfulHtml_withHarmfulTag_removeHarmfulTag()
    {
		$raw = 'hi<iframe src="http://www.w3schools.com"></iframe>';

	    $expected = 'hi';
	    $actual = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($raw);

	    $this->assertEquals($expected, $actual);
    }

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withMultipleHarmfulTags_removeAll()
	{
		$raw = 'hi<iframe></iframe><applet></applet><script></script><style></style><link><a></a><form></form>'.
		'<input><video></video><form></form><math></math><picture></picture><img><map></map><svg></svg>' .
		'<details></details><frameset></frameset><embed></embed><object></object><javascript></javascript>';

		$expected = 'hi';
		$actual = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($raw);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withHarmlessTag_keepHarmlessTag()
	{
		$raw = '<strong>hi</strong>';

		$expected = $raw;
		$actual = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($raw);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function escapeHarmfulHtml_withArray_escapeStringsInArray() {
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

		$actual = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($raw);
		$this->assertEquals($expected, $actual);
	}
}