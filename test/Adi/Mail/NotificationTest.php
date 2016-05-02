<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Mail_NotificationTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Ldap_Connection| PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('Ldap_Connection');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param methods
	 *
	 * @return Adi_Mail_Notification| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Mail_Notification')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->ldapConnection
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function sendNotifications_notifyUser_delegateToMethod()
	{
		$sut = $this->sut(array('sendNotification'));

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(Adi_Configuration_Options::USER_NOTIFICATION),
				array(Adi_Configuration_Options::ADMIN_NOTIFICATION)
			)
			->will(
				$this->onConsecutiveCalls(
					true,
					false
				)
			);

		$sut->expects($this->once())
			->method('sendNotification')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hugo', $mail->getUsername());
				PHPUnit_Framework_Assert::assertEquals(true, $mail->getTargetUser());
			}));

		$sut->sendNotifications('hugo');
	}

	/**
	 * @test
	 */
	public function sendNotifications_notifyAdmin_delegateToMethod()
	{
		$sut = $this->sut(array('sendNotification'));

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(Adi_Configuration_Options::USER_NOTIFICATION),
				array(Adi_Configuration_Options::ADMIN_NOTIFICATION)
			)
			->will(
				$this->onConsecutiveCalls(
					false,
					true
				)
			);

		$sut->expects($this->once())
			->method('sendNotification')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hugo2', $mail->getUsername());
				PHPUnit_Framework_Assert::assertEquals(false, $mail->getTargetUser());
			}));

		$sut->sendNotifications('hugo2');
	}

	/**
	 * @test
	 */
	public function sendNotification_withoutUserMeta_returnFalse()
	{
		$sut = $this->sut(array('getUserMeta'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->with('hugo')
			->willReturn(false);

		$mail = new Adi_Mail_Message();
		$mail->setUsername('hugo');

		$actual = $sut->sendNotification($mail);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addUsername()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->with('hugo')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hugo', $mail->getUsername());
				return true;
			}));

		$mail = new Adi_Mail_Message();
		$mail->setUsername('hugo');

		$actual = $sut->sendNotification($mail);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addFirstName()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(
				'firstName' => 'hubert'
			));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hubert', $mail->getFirstName());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addSecondName()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(
				'secondName' => 'habicht'
			));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('habicht', $mail->getSecondName());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addEmail()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(
				'email' => 'hugo.habicht@stuff.de'
			));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hugo.habicht@stuff.de', $mail->getEmail());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addBlogUrl()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost/wordpress1/wordpress'
		));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'name',
			'return' => ''
		));

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('http://localhost/wordpress1/wordpress', $mail->getBlogUrl());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addBlogDomain()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost/wordpress1/wordpress'
		));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'name',
			'return' => ''
		));

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('localhost/wordpress1', $mail->getBlogDomain());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addBlogName()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'url',
			'return' => ''
		));

		\WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'name',
			'return' => 'My Own Blog'
		));

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('My Own Blog', $mail->getBlogName());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addBlogTime()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$this->configuration->expects($this->once())
			->method('getOption')
			->with(Adi_Configuration_Options::BLOCK_TIME)
			->willReturn('30');

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('30', $mail->getBlockTime());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendNotification_withUserMeta_addRemoteAddress()
	{
		$sut = $this->sut(array('getUserMeta', 'sendMails'));

		$sut->expects($this->once())
			->method('getUserMeta')
			->willReturn(array(''));

		\WP_Mock::wpFunction('get_bloginfo', array());

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$sut->expects($this->once())
			->method('sendMails')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('127.0.0.1', $mail->getRemoteAddress());
				return true;
			}));

		$actual = $sut->sendNotification(new Adi_Mail_Message());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getUserMeta_getValuesFromAd_returnAdResponse()
	{
		$sut = $this->sut(array('findADUserAttributeValues',));

		$userMeta = array(
			'firstName' => 'Heinz'
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::AUTO_UPDATE_USER)
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('isConnected')
			->willReturn(true);

		$sut->expects($this->once())
			->method('findADUserAttributeValues')
			->with('hugo')
			->willReturn($userMeta);

		$actual = $sut->getUserMeta('hugo');
		$this->assertEquals($userMeta, $actual);
	}

	/**
	 * @test
	 */
	public function getUserMeta_getValuesFromWordPress_returnWpResponse()
	{
		$userMeta = array(
			'email' => 'test@company.it',
		);

		$sut = $this->sut(array('findWPUserAttributeValues'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::AUTO_UPDATE_USER)
			->willReturn(false);

		$this->ldapConnection->expects($this->never())
			->method('isConnected')
			->willReturn(true);

		$sut->expects($this->once())
			->method('findWPUserAttributeValues')
			->with('herbert')
			->willReturn($userMeta);

		$actual = $sut->getUserMeta('herbert');
		$this->assertEquals($userMeta, $actual);
	}

	/**
	 * @test
	 */
	public function getUserMeta_invalidResponse_returnFalse()
	{
		$sut = $this->sut(array('findWPUserAttributeValues'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::AUTO_UPDATE_USER)
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('isConnected')
			->willReturn(false);

		$sut->expects($this->once())
			->method('findWPUserAttributeValues')
			->with('herbert')
			->willReturn(array());

		$actual = $sut->getUserMeta('herbert');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function findADUserAttributeValues_getValuesForUser_returnValues()
	{
		$sut = $this->sut(null);

		$adResponse = array(
			'mail' => 'test@company.it',
			'givenname' => 'testGivenName',
			'sn' => 'testSnName',
		);

		$this->ldapConnection->expects($this->once())
			->method('findSanitizedAttributesOfUser')
			->with('hubertus', array("sn", "givenname", "mail"))
			->willReturn($adResponse);

		$expected = array(
			'email' => 'test@company.it',
			'firstName' => 'testGivenName',
			'lastName' => 'testSnName',
		);

		$actual = $sut->findADUserAttributeValues('hubertus');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findWPUserAttributeValues_getValues_returnValues()
	{
		$sut = $this->sut(null);

		$userInfoObject = (object)array(
			'user_email' => 'test@company.it',
			'user_firstname' => 'testFirstname',
			'user_lastname' => 'testLastname',
		);

		$expected = array(
			'email' => 'test@company.it',
			'firstName' => 'testFirstname',
			'lastName' => 'testLastname',
		);

		WP_Mock::wpFunction('username_exists', array(
				'args' => 'herbert',
				'times' => 1,
				'return' => 99)
		);

		WP_Mock::wpFunction('get_userdata', array(
				'args' => 99,
				'times' => 1,
				'return' => $userInfoObject)
		);

		$returnedUserAttributeValue = $sut->findWPUserAttributeValues('herbert');
		$this->assertEquals($expected, $returnedUserAttributeValue);
	}

	/**
	 * @test
	 */
	public function findWPUserAttributeValues_withoutValues_returnFalse()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('username_exists', array(
				'args' => 'hugo',
				'times' => 1,
				'return' => 5)
		);

		WP_Mock::wpFunction('get_userdata', array(
				'args' => 5,
				'times' => 1,
				'return' => array())
		);

		$actual = $sut->findWPUserAttributeValues('hugo');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function sendMails_invalidEmailAddresses_returnFalse()
	{
		$sut = $this->sut(array('getRecipients'));

		$sut->expects($this->once())
			->method('getRecipients')
			->will($this->returnCallback(function ($mail) {
				/* @var Adi_Mail_Message $mail */
				// validate Adi_Mail_Message object
				PHPUnit_Framework_Assert::assertEquals('hugo', $mail->getUsername());
				return array('invalid1', 'invalid2');
			}));

		WP_Mock::wpFunction('is_email', array(
				'args' => 'invalid1',
				'times' => 1,
				'return' => false)
		);

		WP_Mock::wpFunction('is_email', array(
				'args' => 'invalid2',
				'times' => 1,
				'return' => false)
		);

		$mail = new Adi_Mail_Message();
		$mail->setUsername('hugo');
		$actual = $sut->sendMails($mail);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function sendMails_noEmailCanBeSent_returnFalse()
	{
		$sut = $this->sut(array('getRecipients', 'sendMail'));

		$mail = new Adi_Mail_Message();

		$sut->expects($this->once())
			->method('getRecipients')
			->with($mail)
			->willReturn(array('valid@v.de', 'valid@2.de'));

		WP_Mock::wpFunction('is_email', array(
				'return' => true)
		);

		$sut->expects($this->exactly(2))
			->method('sendMail')
			->withConsecutive(array('valid@v.de', $mail), array('valid@2.de', $mail))
			->will($this->onConsecutiveCalls(false, false));

		$actual = $sut->sendMails($mail);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function sendMails_someEmailCanBeSent_returnTrue()
	{
		$sut = $this->sut(array('getRecipients', 'sendMail'));

		$mail = new Adi_Mail_Message();

		$sut->expects($this->once())
			->method('getRecipients')
			->with($mail)
			->willReturn(array('valid@1.de', 'valid@2.de', 'valid@3.de', 'valid@4.de'));

		WP_Mock::wpFunction('is_email', array(
				'return' => true)
		);

		$sut->expects($this->exactly(4))
			->method('sendMail')
			->withConsecutive(
				array('valid@1.de', $mail),
				array('valid@2.de', $mail),
				array('valid@3.de', $mail),
				array('valid@4.de', $mail))
			->will($this->onConsecutiveCalls(false, true, true, false));

		$actual = $sut->sendMails($mail);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function sendMail_validateParameter_delegateToMethod()
	{
		$sut = $this->sut(null);

		$mail = $this->createMock('Adi_Mail_Message');

		$mail->expects($this->once())
			->method('getSubject')
			->willReturn('subject');

		$mail->expects($this->once())
			->method('getBody')
			->willReturn('body');

		$mail->expects($this->once())
			->method('getHeader')
			->willReturn('header');

		WP_Mock::wpFunction('wp_mail', array(
				'args' => array('a@b.de', 'subject', 'body', 'header'),
				'times' => 1,
				'return' => true)
		);

		$actual = $sut->sendMail('a@b.de', $mail);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getRecipients_emailOnlyForTheUser_returnUserEmail()
	{
		$sut = $this->sut(null);

		$mail = new Adi_Mail_Message();
		$mail->setTargetUser(true);
		$mail->setEmail('user@email.de');

		$actual = $sut->getRecipients($mail);
		$this->assertEquals(array('user@email.de'), $actual);
	}

	/**
	 * @test
	 */
	public function getRecipients_adminEmailAddressesAreGiven_returnGivenAddresses()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::ADMIN_EMAIL)
			->willReturn('newAdmin@email.de');

		$mail = new Adi_Mail_Message();
		$mail->setTargetUser(false);

		$actual = $sut->getRecipients($mail);
		$this->assertEquals(array('newAdmin@email.de'), $actual);
	}

	/**
	 * @test
	 */
	public function getRecipients_noGivenAdminEmailAddresses_returnDefaultAddress()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::ADMIN_EMAIL)
			->willReturn(false);

		$mail = new Adi_Mail_Message();
		$mail->setTargetUser(false);

		WP_Mock::wpFunction('get_bloginfo', array(
				'args' => 'admin_email',
				'times' => 1,
				'return' => 'defaultAdmin@email.de')
		);

		$actual = $sut->getRecipients($mail);
		$this->assertEquals(array('defaultAdmin@email.de'), $actual);
	}
}

