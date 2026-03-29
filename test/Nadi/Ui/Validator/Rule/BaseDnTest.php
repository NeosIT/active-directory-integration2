<?php
namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class BaseDnTest extends BasicTestCase
{
	/**
	 * @test
	 * @issue #216
	 */
	public function validate_worksWithDefaultBaseDn()
	{
		$errMsg = "ERRROR";
		$sut = new BaseDn($errMsg);
		$r = $sut->validate("DC=xyz,DC=org", []);

		$this->assertTrue($r);
	}

	/**
	 * @test
	 * @issue #216
	 */
	public function validate_failsIfWhitespaceIsPresentAtTheBeginning()
	{
		$errMsg = "ERRROR";
		$sut = new BaseDn($errMsg);
		$r = $sut->validate(" DC=xyz,DC=org", []);

		$this->assertEquals(['error' => $errMsg], $r);
	}
}