<?php

/**
 * Basic class for Integration tests
 *
 * @author Danny Meißner <dme@neos-it.de>
 * @access
 */
abstract class It_BasicTest extends Ut_BasicTest
{
	// AD Connection Details
	/* @var Ldap_ConnectionDetails $connectionDetails */
	protected $connectionDetails;

	// User Group and OU names used to build and delete the AD objects to work with
	protected $username1 = "Jurg";
	protected $username2 = "Thorsten";
	protected $groupName1 = "AdiItGroup";
	protected $groupName2 = "AdiItGroup2";
	protected $ouName = "AdiItOu";

	// Number of Users to be created for SyncToWordpressIt
	protected $numberOfUsers = 2;

	// adLdap Schema
	private $attributes = array(
		"username" => 'TestUser',
		"logon_name" => 'TestUser',
		"firstname" => 'Test',
		"surname" => 'User',
		"company" => 'Test Company GmbH',
		"department" => 'Test Department',
		"email" => 'test@mylocal.de',
		"container" => '',
		'address_city' => 'Test City',
		'address_code' => '00000',
		'address_country' => '248',
		'address_pobox' => 'PO Box 123456',
		'address_state' => 'Test State',
		'address_street' => 'Test Street',
		'change_password' => '',
		'description' => 'Description for Adi integration test user',
		'expires' => '',
		'home_directory' => '',
		'home_drive' => '',
		'initials' => 'TU',
		'manager' => '',
		'office' => 'Test Office',
		//'password' => 'Pa$$w0rd',
		'profile_path' => 'test/path',
		'script_path' => 'TestScript/path',
		'title' => 'Test Title',
		'telephone' => '0123456789',
		'mobile' => '9876543210',
		'pager' => '00112233445566778899',
		'ipphone' => '127.0.0.1',
		'web_page' => 'http://testpage.com',
		'fax' => '01123698754',
		//'group_sendpermission' => 'FALSE', Existiert nicht als AD Attribute
		//'group_rejectpermission' => 'FALSE', Existiert nicht als AD Attribute
		'exchange_homemdb' => '',
		//'exchange_mailnickname' => 'Test User', Existiert nicht als AD Attribute
		'exchange_proxyaddress' => '127.0.0.1',
		//'exchange_usedefaults' => 'TRUE', Existiert nicht als AD Attribute
		//'exchange_policyexclude' => 'FALSE', Existiert nicht als AD Attribute
		//'exchange_policyinclude' => 'TRUE', Existiert nicht als AD Attribute
		'exchange_addressbook' => '',
		//'exchange_hidefromlists' => 'TRUE', Existiert nicht als AD Attribute
		//'contact_email' => 'testuser@mylocal.de', Existiert nicht als AD Attribute
		"enabled" => '',
		);

	/**
	 * Create Ldap_ConnectionDetails to establish a connection to a Active Directory server
	 *
	 * @return Ldap_ConnectionDetails
	 */
	protected function createAdConnectionDetails()
	{
		$this->connectionDetails = new Ldap_ConnectionDetails();

		$this->connectionDetails->setCustomBaseDn(get_cfg_var('AD_BASE_DN'));
		$this->connectionDetails->setCustomDomainControllers(get_cfg_var('AD_ENDPOINT'));

		$port = get_cfg_var('AD_PORT') ? get_cfg_var('AD_PORT') : 389;
		$this->connectionDetails->setCustomPort($port);

		$useStartTls = get_cfg_var('AD_USE_TLS') ? filter_var(get_cfg_var('AD_USE_TLS'), FILTER_VALIDATE_BOOLEAN) : false;
		$this->connectionDetails->setCustomUseStartTls($useStartTls);
		
		$this->connectionDetails->setCustomNetworkTimeout('5');
		$this->connectionDetails->setUsername(get_cfg_var('AD_USERNAME') . get_cfg_var('AD_SUFFIX'));
		$this->connectionDetails->setPassword(get_cfg_var('AD_PASSWORD'));

		return $this->connectionDetails;
	}

	/**
	 * Create 2 unique userAttributes arrays used to create AD users
	 *
	 * @return array
	 */
	protected function createUserSchemaForTwoUsers()
	{
		$userAttributes1 = $this->attributes;
		$userAttributes1["username"] = $this->username1;
		$userAttributes1["logon_name"] = $this->username1;
		$userAttributes1["firstname"] = $this->username1;
		$userAttributes1["surname"] = "Smith";
		$userAttributes1["email"] = $this->username1 . "@mydomain.local";
		$userAttributes1["container"] = array($this->ouName);

		$userAttributes2 = $this->attributes;
		$userAttributes2["username"] = $this->username2;
		$userAttributes2["logon_name"] = $this->username2;
		$userAttributes2["firstname"] = $this->username2;
		$userAttributes2["surname"] = "Baecker";
		$userAttributes2["email"] = $this->username2 . "@mydomain.local";
		$userAttributes2["container"] = array($this->ouName);

		$attributeCollector = array($userAttributes1,$userAttributes2);

		return $attributeCollector;
	}

	/**
	 * Create an OuAttributes array used to create an OU
	 *
	 * @return array
	 */
	protected function createOuSchema()
	{
		$attributes = array(
			"ou_name" => $this->ouName,
			"description" => "Integration Test Ou Description",
			"container" => array($this->ouName),
		);

		return $attributes;
	}

	/**
	 * Create 2 unique groupAttributes arrays used to create AD groups
	 *
	 * @return array
	 */
	protected function createGroupSchema()
	{
		$attributes = array(
			"group_name" => $this->groupName1,
			"description" => "Integration Test Group Description",
			"container" =>array ($this->ouName),
		);

		$attributes2 = array(
			"group_name" => $this->groupName2,
			"description" => "Integration Test Group2 Description",
			"container" => array($this->ouName),
		);

		$attributeCollector = array($attributes,$attributes2);

		return $attributeCollector;
	}

	/**
	 * Add a user to a group
	 *
	 * @param string $groupname
	 * @param string $username
	 * @param adLDAP $adLDAP
	 *
	 * @return boolean
	 */
	protected function addUserToGroup($groupname, $username, $adLDAP)
	{
		$status = $adLDAP->group_add_user($groupname, $username);
		return $status;
	}


	/**
	 * Delete a group by its name
	 *
	 * @param string $groupname, adLDAP $adLDAP
	 * @param adLDAP $adLDAP
	 *
	 * @return boolean
	 */
	protected function deleteGroupWithGroupName($groupname, $adLDAP)
	{
		$status = $adLDAP->group_delete($groupname);
		return $status;
	}

	/**
	 * Delete a user by given username
	 *
	 * @param string $username
	 * @param adLDAP $adLDAP
	 *
	 * @return boolean
	 */
	protected function deleteUserWithUsername($username, $adLDAP)
	{
		$status = $adLDAP->user_delete($username);
		return $status;
	}

	/**
	 * Prepares the Active Directory
	 *
	 * Creates an OU, two groups, two users, adds user1 to group1 und user2 to group2
	 * @param adLDAP $adLDAP
	 *
	 */
	protected function prepareActiveDirectory($adLDAP)
	{
		// create OU
		$adLDAP->ou_create($this->createOuSchema());

		// create Groups
		foreach ($this->createGroupSchema() as $attributes) {
			$adLDAP->group_create($attributes);
		}


		// create 2 Users
		foreach ($this->createUserSchemaForTwoUsers() as $attributes) {
			$adLDAP->user_create($attributes);
		}

		// add Users to Group
		$this->addUserToGroup($this->groupName1, $this->username1, $adLDAP);
		$this->addUserToGroup($this->groupName2, $this->username2, $adLDAP);
	}

	/**
	 * Delete all the Objects created by prepareActiveDirectory
	 * @param adLDAP $adLDAP
	 */
	protected function rollbackAdAfterConnectionIt($adLDAP)
	{
		$this->deleteUserWithUsername($this->username1, $adLDAP);
		$this->deleteUserWithUsername($this->username2, $adLDAP);
		$this->deleteGroupWithGroupName($this->groupName1, $adLDAP);
		$this->deleteGroupWithGroupName($this->groupName2, $adLDAP);
		$adLDAP->ou_delete("OU=". $this->ouName. "," . $this->connectionDetails->getCustomBaseDn());
	}

	/**
	 * Create the amount of users ($numberOfUsers)
	 *
	 * This method is not used yet. It can be used to create the Users to be imported by SyncToWordpressIt
	 * @param int $numberOfUsers
	 * @return array
	 */
	protected function createSyncToWordpressItUsersAttributes($numberOfUsers)
	{
		$attributeCollector = array();

		for ($i = 0; $i < $numberOfUsers; $i++)
		{
			$attributes	 = $this->attributes;
			$attributes["username"] = $attributes["username"] . $i;
			$attributes["logon_name"] = $attributes["username"] . $i;
			$attributes["surname"] = $attributes["surname"] . $i;
			$attributes["email"] = $attributes["username"] . $i . "@mydomain.local";
			$attributes["container"] = array($this->ouName);

			array_push($attributeCollector, $attributes);
		}

		return $attributeCollector;
	}

	/**
	 * This methode creates the test users for SyncToWordpressIt using the userAttribute Arrays provided by createSyncToWordpressItUsers
	 *
	 * Not used yet.
	 * @param adLDAP $adLDAP
	 */
	protected function createSyncToWordpressItUsers($adLDAP)
	{
		foreach ($this->createSyncToWordpressItUsersAttributes($this->numberOfUsers) as $attributes) {
			$adLDAP->user_create($attributes);
		}
	}

	/**
	 * This methode deletes all the test users created by createSyncToWordpressItUsers
	 * Not used yet.
	 * @param adLDAP $adLDAP
	 */
	protected function deleteSyncToWordpressItUsers($adLDAP)
	{
		for ($i = 0; $i < $this->numberOfUsers; $i++)
		{
			$this->deleteUserWithUsername('TestUser' . $i, $adLDAP);
		}
	}
}
