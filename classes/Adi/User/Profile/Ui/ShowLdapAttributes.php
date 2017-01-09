<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes')) {
	return;
}

/**
 * NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes adds adAttributes to the userProfile Template
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes
{
	const TEMPLATE_NAME = 'user-profile-ad-attributes.twig';

	/* @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/* @var NextADInt_Multisite_View_TwigContainer */
	private $twigContainer;

	/* @var NextADInt_Ldap_Attribute_Repository */
	private $attributeRepository;

	/** @var  NextADInt_Adi_Synchronization_ActiveDirectory */
	private $syncToActiveDirectory;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 * @param NextADInt_Ldap_Attribute_Repository  $attributeRepository
	 * @param NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Ldap_Attribute_Repository $attributeRepository,
								NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory)
	{
		$this->configuration = $configuration;
		$this->twigContainer = $twigContainer;
		$this->attributeRepository = $attributeRepository;
		$this->syncToActiveDirectory = $syncToActiveDirectory;
	}

	/**
	 * Register custom listener which extend the user profile.
	 */
	public function register()
	{
		// Adding AD user attributes to profile page
		add_action('show_user_profile', array($this, 'extendOwnProfile'));
		add_action('edit_user_profile', array($this, 'extendForeignProfile'));
	}

	/**
	 * Add user attributes to the own user profile
	 *
	 * @param WP_User $user
	 */
	public function extendOwnProfile($user)
	{
		$this->extendProfile($user, true);
	}

	/**
	 * Add user attributes to a foreign user profile
	 *
	 * @param WP_User $user
	 */
	public function extendForeignProfile($user)
	{
		$this->extendProfile($user, false);
	}

	/**
	 * Is attribute view enabled
	 * @return mixed
	 */
	public function isShowAttributesEnabled()
	{
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SHOW_ATTRIBUTES);
	}

	/**
	 * Extend the profile of user $user with Active Directory user attribute values.
	 * Old name: showAttributes
	 *
	 * @param WP_User $wpUser
	 * @param boolean $isOwnProfile is the current profile the profile of the current user?
	 */
	function extendProfile($wpUser, $isOwnProfile)
	{
		// create view model with Active Directory attributes for the visible user profile
		$data = $this->createViewModel($wpUser, $isOwnProfile);

        // translate twig text
        $i18n = array(
            'additionalInformation' => __('Additional Information provided by Next Active Directory Integration', 'next-active-directory-integration'),
            'reenterPassword' => __('Reenter password', 'next-active-directory-integration'),
            'youMustEnterPassword' => __('If you want to save the changes in "Additional Information" back to the Active Directory you must enter your password.', 'next-active-directory-integration'),
            'canNotBeEdited' => __('Profile can not be edited or synchronized back to Active Directory:', 'next-active-directory-integration')
        );

		// render it
		echo $this->twigContainer->getTwig()->render(
			self::TEMPLATE_NAME, array(
				'renderData' => $data,
                'i18n' => $i18n
			)
		);
	}

	/**
	 * Create the model for the WordPress user's profile view
	 * @param WP_User $wpUser
	 * @param int $isOwnProfile
	 * @return array
	 */
	function createViewModel($wpUser, $isOwnProfile)
	{
		// get all user attributes for user profile
		$attributes = $this->attributeRepository->filterWhitelistedAttributes(true);

		// is the service account available for this blog?
		$isServiceAccountEnabled = $this->syncToActiveDirectory->isServiceAccountEnabled();

		// return this array
		$r = array(
			'require_password' => false,
			'adi_is_editable' => $this->syncToActiveDirectory->isEditable($wpUser->ID, $isOwnProfile),
			'adi_synchronization_available' => false,
			'adi_synchronization_error_message' => null
		);


		if (!$r['adi_is_editable']) {
			// if the attributes are not editable we have to load the error message
			try {
				$this->syncToActiveDirectory->assertSynchronizationAvailable($wpUser->ID, $isOwnProfile);
			} catch (Exception $e) {
				$r['adi_synchronization_error_message'] = $e->getMessage();
			}
		}

		// if edit of attributes is enabled and no service account has been enabled, the password of the current user has to be entered
		if ($r['adi_is_editable'] && !$isServiceAccountEnabled) {
			$r['require_password'] = true;
		}

		// create view model for Active Directory attributes
		$r['attributes'] = $this->createAttributesViewModel($attributes, $wpUser, $r['adi_is_editable']);

		return $r;
	}

	/**
	 * Create the view model for a list of Ldap_Attribute
	 *
	 * @param array of Ldap_Attribute $attributes
	 * @param WP_User $wpUser
	 * @param bool $isEditable
	 * @return array
	 */
	function createAttributesViewModel($attributes, $wpUser, $isEditable)
	{
		$r = array();

		// iterate over attributes
		foreach ($attributes as $attributeName => $attribute) {
			// get render data for the current $attribute
			$viewModel = $this->createAttributeViewModel(
				$attribute, $wpUser, $isEditable
			);

			$r[$attributeName] = $viewModel;
		}

		return $r;
	}

	/**
	 * Create the view model for a single Ldap_Attribute
	 *
	 * @param NextADInt_Ldap_Attribute $attribute
	 * @param WP_User $user
	 * @param bool $isEditable
	 *
	 * @return array
	 */
	function createAttributeViewModel($attribute, $user, $isEditable)
	{
		// default values
		$noAttribute = true;
		$attributeName = '';
		$description = '';
		$value = '';
		$outputType = 'plain';

		// get the metakey and the value for this $userAttributeArray
		if ($attribute->getMetakey()) {
			$attributeName = $attribute->getMetakey();
			$value = get_user_meta($user->ID, $attributeName, true);
			$noAttribute = false;
		}

		// get the description for this $userAttributeArray
		if ($attribute->getDescription()) {
			$description = trim($attribute->getDescription());
		} else {
			// if value is empty and we've found no description then this is no attribute
			if (!$value) {
				$noAttribute = true;
			}
		}

		// the fields are editable if:
		// - sync to ad is activated
		// - the value for the key 'sync' of the current user attribute array is true
		// - the user edits his own profile or a global sync to ad user is set
		$isElementEditable = $isEditable && $attribute->isSyncable();

		if ($isElementEditable) {
			// use textarea if we have a list
			$outputType = ($attribute->getType() === 'list') ? 'textarea' : 'text';
		}

		//
		return array(
			'noAttribute' => $noAttribute,
			'description' => $description,
			'metaKey' => $attributeName,
			'value' => $value,
			'outputType' => $outputType,
		);
	}

}