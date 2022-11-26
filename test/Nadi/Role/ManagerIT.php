<?php

namespace Dreitier\Nadi\Role;

use Dreitier\ActiveDirectory\Context;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Test\BasicIntegrationTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class ManagerIT extends BasicIntegrationTest
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var Connection */
	private $connection;

	/* @var ConnectionDetails */
	protected $connectionDetails;

	/* @var Manager */
	private $sut;

	public function setUp(): void
	{
		$this->configuration = $this->createMock(Service::class);

		$this->connection = new Connection($this->configuration, new Context(['000']));
		$this->connectionDetails = $this->createAdConnectionDetails();
		$this->sut = new Manager($this->configuration, $this->connection);
		$this->connection->connect($this->connectionDetails);
		$this->prepareActiveDirectory($this->connection->getAdLdap());
	}

	public function tearDown(): void
	{
		$this->rollbackAdAfterConnectionIt($this->connection->getAdLdap());
		Mockery::close();
	}

	/**
	 * @test
	 */
	public function createRoleMapping_findsTheUsersSecurityGroups()
	{
		$givenGroups = array("Domänen-Admins", "Users", "Systemadmins", "YetAnotherUserGroup", $this->groupName1);

		$roleMapping = $this->sut->createRoleMapping($this->username1);
		$this->assertTrue(sizeof($roleMapping->getSecurityGroups()) > 0);
		$this->assertTrue(in_array($this->groupName1, $roleMapping->getSecurityGroups()));
	}
}
