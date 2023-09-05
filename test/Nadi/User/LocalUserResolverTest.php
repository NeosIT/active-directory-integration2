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
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\WordPressErrorException;
use Hoa\Protocol\Bin\Resolve;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class LocalUserResolverTest extends BasicTest
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function sut(): LocalUserResolver
	{
		return new LocalUserResolver(NadiLog::getInstance());
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function add_addsResolver()
	{
		$sut = $this->sut();
		$this->assertEquals(0, sizeof($sut->getResolvers()));

		$sut->add(ResolveLocalUser::by('', fn() => null, ''));
		$this->assertEquals(1, sizeof($sut->getResolvers()));
	}

	/**
	 * @test
	 */
	public function resolve_returnsFirstResolverMatch()
	{
		$expect = new \WP_User();
		$expect->ID = 555;
		$ignore = new \WP_User();
		$ignore->ID = 666;

		$sut = $this->sut();
		$this->assertEquals(0, sizeof($sut->getResolvers()));

		$sut->add(ResolveLocalUser::by('', fn($p) => null, 'failed'));
		$sut->add(ResolveLocalUser::by('', fn($p) => $expect, 'used'));
		$sut->add(ResolveLocalUser::by('', fn($p) => $ignore, 'ignored'));

		$this->assertEquals($expect, $sut->resolve());
	}
}