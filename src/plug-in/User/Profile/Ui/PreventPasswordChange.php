<?php

namespace Dreitier\Nadi\User\Profile\Ui;


use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\User\Manager;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * Depending upon the blog configuration this class disables the password field to prevent users from changing there password.
 * This hook is only executed for Active Directory members.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class PreventPasswordChange
{
	/** @var $multisiteConfigurationService Service */
	private $multisiteConfigurationService;

	/** @var $userManager Manager */
	private $userManager;

	/**
	 * @param Service $multisiteConfigurationService
	 * @param Manager $userManager
	 */
	public function __construct(Service $multisiteConfigurationService,
								Manager $userManager)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->userManager = $userManager;
	}

	/**
	 * Disable password fields for a user.
	 */
	public function register()
	{
		// the callback functions have to decide whether to execute thm or not b/c they require the current user context

		// Is local password change disallowed?
		// disable password fields
		add_filter('show_password_fields', array($this, 'showPasswordFields'), 10, 2);
	}

	/**
	 * Return the value of th setting "Allow local password changes"
	 * @return bool
	 */
	public function isPasswordChangeEnabled()
	{
		return $this->multisiteConfigurationService->getOptionValue(Options::ENABLE_PASSWORD_CHANGE);
	}

	/**
	 * WordPress callback for showing/hiding the password fields
	 *
	 * @param bool $show setting from prior called plug-ins
	 * @param WP_User $wpUser
	 * @return mixed bool
	 */
	public function showPasswordFields($show, $wpUser)
	{
		// if user is no AD member or a local user (admin etc.), the parent value has priority
		if (!$this->userManager->hasActiveDirectoryAccount($wpUser)) {
			return $show;
		}

		// user is AD member so decide by ADI setting
		return $this->isPasswordChangeEnabled();
	}
}