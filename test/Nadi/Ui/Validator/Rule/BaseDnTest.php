<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class BaseDnTest extends BasicTestCase
{
	/**
	 * @issue #216
	 */
	#[Test]
	public function validate_worksWithDefaultBaseDn()
	{
		$errMsg = "ERRROR";
		$sut = new BaseDn($errMsg);
		$r = $sut->validate("DC=xyz,DC=org", []);

		$this->assertTrue($r);
	}

	/**
	 * @issue #216
	 */
	#[Test]
	public function validate_failsIfWhitespaceIsPresentAtTheBeginning()
	{
		$errMsg = "ERRROR";
		$sut = new BaseDn($errMsg);
		$r = $sut->validate(" DC=xyz,DC=org", []);

		$this->assertEquals(['error' => $errMsg], $r);
	}
}