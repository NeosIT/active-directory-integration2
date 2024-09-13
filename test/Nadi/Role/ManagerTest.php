<?php

namespace Dreitier\Nadi\Role;

use Dreitier\ActiveDirectory\Context;
use Dreitier\AdLdap\AdLdap;
use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTest;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class ManagerTest extends BasicTest
{
	/* @var Service|MockObject $configuration */
	private $configuration;

	/* @var AdLdap|MockObject $adLdap */
	private $adLdap;

	/* @var Connection|MockObject $ldapConnection */
	private $ldapConnection;

	/* @var Native|MockObject $sessionHandler */
	private $native;

	/** @var Context|MockObject */
	private $activeDirectoryContext;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->activeDirectoryContext = $this->createMock(Context::class);
		$this->adLdap = $this->createMock(AdLdap::class);

		$this->ldapConnection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs(array($this->configuration, $this->activeDirectoryContext))
			->getMock();

		$this->ldapConnection->method('getAdLdap')->willReturn($this->adLdap);

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		Util::native($this->native);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		Util::native(null);
	}

	/**
	 * @param $methods
	 *
	 * @return Manager|MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder(Manager::class)
			->setConstructorArgs(array($this->configuration, $this->ldapConnection))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function createRoleMapping_looksupSecurityGroups()
	{
		$sut = $this->sut(null);

		$this->adLdap->expects($this->once())
			->method("user_groups")
			->with('username')
			->willReturn(array('A', 'B'));

		$actual = $sut->createRoleMapping("username");
		$this->assertEquals(array('A', 'B'), $actual->getSecurityGroups());
	}

	/**
	 * @test
	 */
	public function isInAuthorizationGroup_itReturnsFalse_ifHeIsNotMemberOfAuthorizationGroup()
	{
		$sut = $this->sut(array('getAdLdap', 'isUserInGroup'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('authorization_group')
			->willReturn('Test Group;Test Group 2;Test Group   3;');

		$mapping = new Mapping("username");
		$mapping->setSecurityGroups(array('Unassigned group'));

		$value = $sut->isInAuthorizationGroup($mapping);

		$this->assertEquals(false, $value);
	}

	/**
	 * @test
	 */
	public function isInAuthorizationGroup_itReturnsTrue_ifHeIsMemberOfOneAuthorizationGroup()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('authorization_group')
			->willReturn('Test Group;Test Group 2;assigned group;Test Group   3;');

		$mapping = new Mapping("username");
		$mapping->setSecurityGroups(array('assigned group'));

		$value = $sut->isInAuthorizationGroup($mapping);

		$this->assertEquals(true, $value);
	}

	/**
	 * @issue ADI-248
	 * @test
	 */
	public function ADI248_whenAuthorizationGroupIsEmpty_itIsNotPossibleToAuthorize()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('authorization_group')
			// empty authorization groups
			->willReturn('  ; ;;');

		$mapping = new Mapping("username");

		$value = $sut->isInAuthorizationGroup($mapping);

		$this->assertEquals(true, $value);
	}

	/**
	 * @test
	 */
	public function synchronizeRoles_loadsWordPressRoles()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles'));

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array());

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;

		$sut->expects($this->once())
			->method('loadWordPressRoles');

		$sut->synchronizeRoles($wpUser, $roleMapping, true);
	}


	/**
	 * @test
	 */
	public function synchronizeRoles_onUserCreation_withoutREGsTheDefaultRoleSubscriberIsUsed()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles', 'isMemberOfRoleEquivalentGroups'));

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array('security-group' => 'wordpress-role'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::CLEAN_EXISTING_ROLES)
			->willReturn(true);

		$sut->expects($this->once())
			->method('isMemberOfRoleEquivalentGroups')
			->willReturn(false);

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array());

		$sut->expects($this->once())
			->method('updateRoles')
			->with($wpUser, array('subscriber'), false);

		$sut->synchronizeRoles($wpUser, $roleMapping, true);
	}


	/**
	 * @test
	 */
	public function synchronizeRoles_onUserCreation_withREGs_assignRoles()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles'));

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;
		$wpUser->roles = array('subscriber');
		$wpUser->user_login = 'username';

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array('administrator'));
		$roleMapping->setSecurityGroups(array('securityGroup'));

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array('securityGroup' => 'administrator'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::CLEAN_EXISTING_ROLES)
			->willReturn(true);


		$sut->expects($this->once())
			->method('updateRoles')
			->with($wpUser, array('administrator'), true);

		$sut->synchronizeRoles($wpUser, $roleMapping, true);
	}


	/**
	 * @test
	 */
	public function synchronizeRoles_onUserUpdate_withREGsAndUserHasNoRole_noRolesAreSet()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles'));

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array('security-group' => 'wordpress-role'));

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array());

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::CLEAN_EXISTING_ROLES)
			->willReturn(true);

		$sut->expects($this->once())
			->method('updateRoles')
			->with($wpUser, array(), true);

		$sut->synchronizeRoles($wpUser, $roleMapping, false);
	}

	/**
	 * @test
	 */
	public function synchronizeRoles_onUserUpdate_withoutREGs_allOldRolesArePreserved()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles'));

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array());

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array());

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;

		$sut->expects($this->once())
			->method('updateRoles')
			->with($wpUser, array(), false);

		$sut->synchronizeRoles($wpUser, $roleMapping, false);
	}


	/**
	 * @test
	 */
	public function synchronizeRoles_setRolesIfPresent()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups', 'updateRoles', 'loadWordPressRoles'));

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array('security-group' => 'wordpress-role', 'security-group2' => 'wordpress-role2',
				'security-group3' => 'wordpress-role3'));

		$roleMapping = new Mapping("username");
		$roleMapping->setWordPressRoles(array('wordpress-role', 'wordpress-role2'));

		$wpUser = $this->createAnonymousMock(array());
		$wpUser->ID = 1;

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::CLEAN_EXISTING_ROLES)
			->willReturn(true);

		$sut->expects($this->once())
			->method('updateRoles')
			->with($wpUser, array('wordpress-role', 'wordpress-role2'), true);

		$sut->synchronizeRoles($wpUser, $roleMapping, false);
	}

	/**
	 * @test
	 */
	public function updateRoles_itDoesNotSetRole_ifCleanExistingRolesIsDisabled()
	{
		$sut = $this->sut(null);
		$wpUser = $this->createAnonymousMock(array('set_role', 'add_role'));
		$wpUser->user_login = 'username';

		$wpUser->expects($this->never())
			->method('set_role');

		$wpUser->expects($this->once())
			->method('add_role')
			->with('subscriber');

		$actual = $sut->updateRoles($wpUser, array('subscriber'), false);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function updateRoles_itReleasesExistingRoles_ifCleanExistingRolesIsEnabled()
	{
		$sut = $this->sut(null);
		$wpUser = $this->createAnonymousMock(array('set_role', 'add_role'));
		$wpUser->user_login = 'username';

		$wpUser->expects($this->once())
			->method('set_role');

		$wpUser->expects($this->once())
			->method('add_role')
			->with('subscriber');

		$actual = $sut->updateRoles($wpUser, array('subscriber'), true);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getRoleEquivalentGrous_returnsTheMapping()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::ROLE_EQUIVALENT_GROUPS)
			->willReturn(';ad-group=wp-role;c=d');

		$actual = $sut->getRoleEquivalentGroups();
		$this->assertEquals(2, sizeof($actual));
		$this->assertEquals('d', $actual['c']);
		$this->assertEquals('wp-role', $actual['ad-group']);
	}

	/**
	 * @test
	 */
	public function getMappedWordPressRoles_returnsTheMapping()
	{
		$sut = $this->sut(array('getRoleEquivalentGroups'));

		$sut->expects($this->once())
			->method('getRoleEquivalentGroups')
			->willReturn(array('belonging-security-group' => 'A', 'not-belonging-security-group' => 'B',
				'belonging-security-group-2' => 'C'));

		$roleMapping = new Mapping("username");
		$roleMapping->setSecurityGroups(array('belonging-security-group', 'belonging-security-group-2'));

		$actual = $sut->getMappedWordPressRoles($roleMapping);
		$this->assertEquals(2, sizeof($actual));
		$this->assertEquals('A', $actual[0]);
		$this->assertEquals('C', $actual[1]);
	}

	/**
	 * @test
	 */
	public function roleConstants_haveCorrectValues()
	{
		$this->assertEquals('super admin', Manager::ROLE_SUPER_ADMIN);
	}

	/**
	 * @test
	 */
	public function updateRoles_handlesSuperAdminRoleDifferent()
	{
		$wpUser = $this->createMockWithMethods(\WP_User::class, array('add_role'));
		$wpUser->user_login = 'username';
		$roles = array(Manager::ROLE_SUPER_ADMIN);

		$sut = $this->sut(array('grantSuperAdminRole'));

		$this->expects($sut, $this->once(), 'grantSuperAdminRole', $wpUser, null);
		$this->expects($wpUser, $this->never(), 'add_role', null, null);

		$sut->updateRoles($wpUser, $roles, false);
	}

	/**
	 * @test
	 */
	public function grantSuperAdminRole_loadsMultisiteFunctions()
	{
		$wpUser = $this->createMock(\WP_User::class);
		$wpUser->ID = 1;

		$sut = $this->sut(array('loadMultisiteFunctions'));

		$sut->expects($this->once())
			->method('loadMultisiteFunctions');

		\WP_Mock::userFunction('grant_super_admin', array(
			'times' => 1,
			'with' => $wpUser->ID,
		));

		$this->invokeMethod($sut, 'grantSuperAdminRole', array($wpUser));
	}

	/**
	 * @test
	 */
	public function loadMultisiteFunctions_withFunctionAvailable_returns()
	{
		$this->native->expects($this->once())
			->method('isFunctionAvailable')
			->with('grant_super_admin')
			->willReturn(true);

		$this->native->expects($this->never())
			->method('isFileAvailable');

		$sut = $this->sut(null);

		$this->invokeMethod($sut, 'loadMultisiteFunctions');
	}

	/**
	 * @test
	 */
	public function loadMultisiteFunctions_withoutFunctionAvailable_checksForFileAndImportsIt()
	{
		$this->native->expects($this->once())
			->method('isFunctionAvailable')
			->with('grant_super_admin')
			->willReturn(false);

		$this->native->expects($this->once())
			->method('isFileAvailable')
			->willReturn(ABSPATH . 'wp-admin/includes/ms.php')
			->willReturn(true);

		$this->native->expects($this->once())
			->method('includeOnce')
			->with(ABSPATH . 'wp-admin/includes/ms.php');

		$sut = $this->sut(null);

		$this->invokeMethod($sut, 'loadMultisiteFunctions');
	}

	/**
	 * @test
	 */
	public function getRoles_inSingleSite_removesSuperAdminFromRoles()
	{
		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => false,
		));

		$expected = array(
			'administrator' => 'administrator',
			'editor' => 'editor',
			'contributor' => 'contributor',
			'subscriber' => 'subscriber',
			'author' => 'author',
		);
		$actual = Manager::getRoles();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getRoles_inMultiSite_containsAllRoles()
	{
		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		$expected = array(
			'super admin' => 'super admin',
			'administrator' => 'administrator',
			'editor' => 'editor',
			'contributor' => 'contributor',
			'subscriber' => 'subscriber',
			'author' => 'author',
		);
		$actual = Manager::getRoles();

		$this->assertEquals($expected, $actual);
	}
}