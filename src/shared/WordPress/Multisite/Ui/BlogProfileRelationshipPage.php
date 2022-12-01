<?php

namespace Dreitier\WordPress\Multisite\Ui;


use Dreitier\Util\EscapeUtil;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\View\Page\PageAdapter;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use Twig\Profiler\Profile;

/**
 * Controller for assigning an ADI profile to a specific blog
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class BlogProfileRelationshipPage extends PageAdapter
{
	const VERSION_BLOG_PROFILE_RELATIONSHIP_JS = '1.0';

	const CAPABILITY = 'manage_network';
	const TEMPLATE = 'blog-profile-relationship.twig';
	const NONCE = 'Active Directory Integration Profile Assignment Nonce';

	/** @var BlogProfileRelationshipController */
	private $blogProfileRelationshipController;

	/**
	 * @param TwigContainer $twigContainer
	 * @param BlogProfileRelationshipController $blogProfileRelationshipController
	 */
	public function __construct(TwigContainer                     $twigContainer,
								BlogProfileRelationshipController $blogProfileRelationshipController
	)
	{
		parent::__construct($twigContainer);

		$this->blogProfileRelationshipController = $blogProfileRelationshipController;
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
		return NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_profile_relationship';
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
		$i18n = EscapeUtil::escapeHarmfulHtml($i18n);

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
			'next_ad_int_blog_profile_association',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/blog-profile-relationship.js', array('jquery'),
			self::VERSION_BLOG_PROFILE_RELATIONSHIP_JS
		);

		wp_enqueue_style('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', array(), Ui::VERSION_CSS);
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
	 * Check if the data for {@see BlogProfileRelationshipController::saveBlogProfileAssociations(
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
	 * Check if the data for {@see BlogProfileRelationshipController::saveDefaultProfile($profileId) is
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