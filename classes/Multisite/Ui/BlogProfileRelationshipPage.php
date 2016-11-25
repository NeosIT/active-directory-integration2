<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_BlogProfileRelationshipPage')) {
	return;
}

/**
 * Controller for assigning an ADI profile to a specific blog
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Multisite_Ui_BlogProfileRelationshipPage extends NextADInt_Multisite_View_Page_Abstract
{
	const VERSION_BLOG_PROFILE_RELATIONSHIP_JS = '1.0';

	const CAPABILITY = 'manage_network';
	const TEMPLATE = 'blog-profile-relationship.twig';
	const NONCE = 'Active Directory Integration Profile Assignment Nonce';

	/** @var NextADInt_Multisite_Ui_BlogProfileRelationshipController */
	private $blogProfileRelationshipController;

	/**
	 * @param NextADInt_Multisite_View_TwigContainer                   $twigContainer
	 * @param NextADInt_Multisite_Ui_BlogProfileRelationshipController $profileConfigurationRelationshipController
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
		NextADInt_Multisite_Ui_BlogProfileRelationshipController $profileConfigurationRelationshipController
	) {
		parent::__construct($twigContainer);

		$this->blogProfileRelationshipController = $profileConfigurationRelationshipController;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Profile assignment', 'next-active-directory-integration');
	}

	/**
	 * Get the slug for post requests.
	 *
	 * @return string
	 */
	public function wpAjaxSlug()
	{
		return $this->getSlug();
	}

	/**
	 * Get the menu slug of the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return self::buildSlug();
	}

	/**
	 * Build the menu slug of the page.
	 *
	 * @return string
	 */
	public static function buildSlug()
	{
		return NEXT_AD_INT_PREFIX . 'blog_profile_relationship';
	}

	/**
	 * Render the page for an network admin.
	 */
	public function renderNetwork()
	{
        // translate twig text
        $i18n = array(
            'search' => __('Search', 'next-active-directory-integration'),
            'title' => __('Profile assignment', 'next-active-directory-integration'),
            'defaultProfile' => __('Default profile', 'next-active-directory-integration'),
            'noneAssigned' => __('--- None assigned', 'next-active-directory-integration'),
            'apply' => __('Apply', 'next-active-directory-integration'),
            'changeBlogs' => __('Change selected blogs to profile', 'next-active-directory-integration'),
            'useDefaultProfile' => __('--- Use default profile', 'next-active-directory-integration')
        );
		$i18n = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

		$this->display(self::TEMPLATE, array(
			'nonce' => wp_create_nonce(self::NONCE), //create nonce for security
			'table' => $this->blogProfileRelationshipController->buildSiteTable(),
            'i18n' => $i18n
		));
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param $hook
	 */
	public function loadNetworkScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		wp_enqueue_script(
			'next_ad_int_blog_profile_association', NEXT_AD_INT_URL . '/js/blog-profile-relationship.js', array('jquery'),
			self::VERSION_BLOG_PROFILE_RELATIONSHIP_JS
		);

		wp_enqueue_style('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
	}

	/**
	 * This method listens to post request via wp_ajax_xxx hook.
	 */
	public function wpAjaxListener()
	{
		// die if nonce is not valid
		$this->checkNonce();

        // ADI-357 unescape already escaped $_POST
        $post = stripslashes_deep($_POST);

		// is $post does not contain data, then return
		if (empty($post['data'])) {
			return;
		}

		// if user has got insufficient permission, then leave
		if (!$this->currentUserHasCapability()) {
			return;
		}

		$data = $post['data'];

		$this->saveBlogProfileAssociations($data);
		$this->saveDefaultProfile($data);
	}

	/**
	 * Check if the data for {@see NextADInt_Multisite_Ui_BlogProfileRelationshipController::saveBlogProfileAssociations(
	 * ($profileId, $blogIds) is given. If not, the action will <strong>not</strong> continue.
	 *
	 * @param array $data
	 */
	protected function saveBlogProfileAssociations(array $data)
	{
		if (empty($data['profile']) || empty($data['allblogs'])) {
			return;
		}

		$this->blogProfileRelationshipController->saveBlogProfileAssociations($data['profile'], $data['allblogs']);
	}

	/**
	 * Check if the data for {@see NextADInt_Multisite_Ui_BlogProfileRelationshipController::saveDefaultProfile($profileId) is
	 * given. If not, the action will <strong>not</strong> continue.
	 *
	 * @param array $data
	 */
	protected function saveDefaultProfile(array $data)
	{
		if (empty($data['default-profile'])) {
			return;
		}

		$this->blogProfileRelationshipController->saveDefaultProfile($data['default-profile']);
	}

	/**
	 * Get the current capability to check if the user has permission to view this page.
	 *
	 * @return string
	 */
	protected function getCapability()
	{
		return self::CAPABILITY;
	}

	/**
	 * Get the current nonce value.
	 *
	 * @return mixed
	 */
	protected function getNonce()
	{
		return self::NONCE;
	}
}