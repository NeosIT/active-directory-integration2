<?php

namespace Dreitier\Nadi\User;

use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Attributes;
use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\Nadi\Authentication\PrincipalResolver;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\WordPressErrorException;
use Hoa\Protocol\Bin\Resolve;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class ResolveLocalUserTest extends BasicTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @issue #188
	 * @test
	 */
	public function GH_188_aNonWpUser_isTransformedToNull()
	{
		$sut = new ResolveLocalUser('principal', fn($principal) => false);

		$this->assertEquals(null, $sut->resolve());
	}
}