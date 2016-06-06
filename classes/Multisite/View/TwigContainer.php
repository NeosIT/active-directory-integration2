<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_View_TwigContainer')) {
	return;
}

/**
 * Multisite_View_TwigContainer provides the basic configuration for twig and registers all necessary function and filter.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Multisite_View_TwigContainer
{
	/** @var \Twig_Environment $twig */
	private $twig;

	/** @var Multisite_Configuration_Service $configuration */
	private $configuration;

	/** @var Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var  Multisite_Option_Provider $optionProvider */
	private $optionProvider;

	/** @var Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository */
	private $defaultProfileRepository;

	/**
	 * @param Multisite_Configuration_Persistence_BlogConfigurationRepository    $blogConfigurationRepository
	 * @param Multisite_Configuration_Service                                    $configuration
	 * @param Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository
	 * @param Multisite_Configuration_Persistence_ProfileRepository              $profileRepository
	 * @param Multisite_Configuration_Persistence_DefaultProfileRepository       $defaultProfileRepository
	 * @param Multisite_Option_Provider                                          $optionProvider
	 */
	public function __construct(Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
		Multisite_Configuration_Service $configuration,
		Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository,
		Multisite_Configuration_Persistence_ProfileRepository $profileRepository,
		Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository,
		Multisite_Option_Provider $optionProvider
	) {
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->configuration = $configuration;
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->profileRepository = $profileRepository;
		$this->defaultProfileRepository = $defaultProfileRepository;
		$this->optionProvider = $optionProvider;
	}

	/**
	 * Get the name of the current blog.
	 *
	 * @param $blogId
	 *
	 * @return mixed
	 */
	public function getBlogName($blogId = null)
	{
		if (null === $blogId || !is_multisite()) {
			return get_option('blogname');
		}

		return get_blog_option($blogId, 'blogname');
	}

	/**
	 * Get the id of the profile which is related with the current blog.
	 *
	 * @param $blogId
	 *
	 * @return null|string
	 */
	public function getProfileIdOfBlog($blogId)
	{
		return $this->blogConfigurationRepository->findProfileId($blogId);
	}

	/**
	 * Return the twig renderer.
	 *
	 * @return Twig_Environment
	 */
	public function getTwig()
	{
		if (!$this->twig) {
			$this->register();
		}

		return $this->twig;
	}

	/**
	 * Create the twig template engine with custom functions.
	 */
	public function register()
	{
		//register twig
		if (!class_exists('Twig_Autoloader')) {
			require_once(ADI_PATH . '/vendor/twig/twig/lib/Twig/Autoloader.php');
		}

		Twig_Autoloader::register();

		$loader = new Twig_Loader_Filesystem(ADI_PATH . '/views');

		$twigOptions = $this->getTwigOptions(Core_Util_Internal_Environment::isProductive());

		$this->twig = new Twig_Environment($loader, $twigOptions);

		$this->addSimpleTwigFilter('__', '__');
		$this->addSimpleTwigFilter('var_dump', 'var_dump');

		$this->addSimpleTwigFunction('isOptionGroupVisible', array($this, 'isOptionGroupVisible'));

		// meta data for options like metadata or grouping
		$this->addSimpleTwigFunction('isPermissionEnabled', array($this, 'isPermissionEnabled'));
		$this->addSimpleTwigFunction('getMetadata', array($this, 'getMetadata'));
		$this->addSimpleTwigFunction('getOptionsGrouping', 'Adi_Configuration_Ui_Layout::get');

		// option meta data like value, permission etc.
		$this->addSimpleTwigFunction('getOptionPermission', array($this, 'getOptionPermission'));
		$this->addSimpleTwigFunction('isOptionDisabled', array($this, 'isOptionDisabled'));
		$this->addSimpleTwigFunction('getOptionValue', array($this, 'getOptionValue'));
		$this->addSimpleTwigFunction('getPermissionForOptionAndBlog', array($this, 'getPermissionForOptionAndBlog'));

		// meta data for profiles and sites
		$this->addSimpleTwigFunction('getBlogName', array($this, 'getBlogName'));
		$this->addSimpleTwigFunction('getProfileIdOfBlog', array($this, 'getProfileIdOfBlog'));
		$this->addSimpleTwigFunction('getSites', array($this, 'getSites'));
		$this->addSimpleTwigFunction('findAllProfileIds', array($this->profileRepository, 'findAllIds'));
		$this->addSimpleTwigFunction('findDefaultProfileId', array($this->defaultProfileRepository, 'findProfileId'));
		$this->addSimpleTwigFunction('findProfileName', array($this->profileRepository, 'findName'));
		$this->addSimpleTwigFunction('findProfileDescription', array($this->profileRepository, 'findDescription'));
		$this->addSimpleTwigFunction('isOnNetworkDashboard', array($this, 'isOnNetworkDashboard'));
	}

	/**
	 * Generate the configuration array for twig.
	 *
	 * @param $isProductive
	 *
	 * @return array
	 */
	private function getTwigOptions($isProductive)
	{
		$result = array(
			'cache'            => ADI_PATH . '/twig/cache',
			'strict_variables' => true,
		);

		if (!$isProductive || 'false' == $isProductive) {
			$result['debug'] = true;
		}

		return $result;
	}

	/**
	 * Create a new Twig_SimpleFilter using the given data and add it to twig.
	 *
	 * @param $name
	 * @param $callback
	 */
	private function addSimpleTwigFilter($name, $callback)
	{
		$this->twig->addFilter(new Twig_SimpleFilter($name, $callback));
	}

	/**
	 * Create a new Twig_SimpleFunction using the given data and add it to twig.
	 *
	 * @param $name
	 * @param $callback
	 */
	private function addSimpleTwigFunction($name, $callback)
	{
		$this->twig->addFunction(new Twig_SimpleFunction($name, $callback));
	}

	/**
	 * Return the meta information of only 9,999 sites. 10,000 or more sites are not supported.
	 * https://codex.wordpress.org/Function_Reference/wp_get_sites
	 *
	 * @return array
	 */
	public function getSites()
	{
		// get all profiles and all blogs
		if (is_multisite()) {
			return wp_get_sites(
				array(
					'limit' => 9999,
				)
			);
		}

		$sites = array();
		$sites[0] = array(
			'network_id' => 0,
			'public'     => null,
			'archived'   => null,
			'mature'     => null,
			'spam'       => null,
			'deleted'    => null,
			'limit'      => 100,
			'offset'     => 0,
		);

		return $sites;
	}

	/**
	 * Check if the given $optionGroup should be visible in the frontend or not.
	 *
	 * @param $optionGroup
	 *
	 * @return bool
	 */
	public function isOptionGroupVisible($optionGroup)
	{
		$isNetworkDashboard = $this->isOnNetworkDashboard();

		if ($optionGroup[Adi_Configuration_Ui_Layout::MULTISITE_ONLY] && $isNetworkDashboard) {
			return true;
		}

		if (!$optionGroup[Adi_Configuration_Ui_Layout::MULTISITE_ONLY]) {
			return true;
		}

		return false;
	}

	/**
	 * Simple delegate to {@see Multisite_Util::isOnNetworkDashboard()}.
	 *
	 * @return bool
	 */
	public function isOnNetworkDashboard()
	{
		return Multisite_Util::isOnNetworkDashboard();
	}

	/**
	 * @param      $optionName
	 * @param null $profileId
	 *
	 * @return bool|null|object|string
	 */
	public function getOptionValue($optionName, $profileId = null)
	{
		if (null !== $profileId) {
			return $this->profileConfigurationRepository->findSanitizedValue($profileId, $optionName);
		}

		$permission = $this->getPermissionForOptionAndBlog($optionName);

		// if permission is 0 or 1
		if (2 > $permission) {
			return false;
		}

		return $this->blogConfigurationRepository->findSanitizedValue(get_current_blog_id(), $optionName);
	}

	/**
	 * Returns the values of all options
	 *
	 * @return array
	 */
	public function getAllOptionsValues()
	{
		return $this->configuration->getAllOptions();
	}

	/**
	 * Returns the values of all options for a specific profile
	 *
	 * @param $profileId
	 *
	 * @return array
	 */
	public function getAllProfileOptionsValues($profileId)
	{
		return $this->configuration->getAllProfileOptionsValues($profileId);
	}

	/**
	 * Returns ID NAME and DESCRIPTION for all profiles
	 *
	 * @return array
	 */
	public function getAllProfilesData()
	{
		return $this->profileRepository->findAll();
	}

	/**
	 * @param      $optionName
	 * @param null $profileId
	 *
	 * @return bool
	 */
	public function isOptionDisabled($optionName, $profileId = null)
	{
		if (null !== $profileId) {
			return false;
		}

		$permission = $this->getPermissionForOptionAndBlog($optionName);

		if ('3' == $permission) {
			return false;
		}

		return true;
	}

	/**
	 * @param $optionName
	 *
	 * @return array|bool|null|object|void
	 */
	public function getPermissionForOptionAndBlog($optionName)
	{
		$blogId = get_current_blog_id();

		$profileId = $this->blogConfigurationRepository->findProfileId($blogId);
		$permission = $this->profileConfigurationRepository->findPermissionSanitized($profileId, $optionName);

		return $permission;
	}

	/**
	 * @param $profileId
	 * @param $optionName
	 *
	 * @return array|bool|null|object|void
	 */
	public function getOptionPermission($profileId, $optionName)
	{
		if (null === $profileId) {
			$profileId = $this->blogConfigurationRepository->findProfileId(get_current_blog_id());
		}

		return $this->profileConfigurationRepository->findPermissionSanitized($profileId, $optionName);
	}

	/**
	 * @param $optionName
	 *
	 * @return bool|mixed
	 */
	public function isPermissionEnabled($optionName)
	{
		$metaData = $this->getMetadata($optionName, 'SHOW_PERMISSION');

		return ('' === $metaData || true == $metaData) ? true : false;
	}

	/**
	 * @param $optionName
	 * @param $attribute
	 *
	 * @return mixed
	 */
	public function getMetadata($optionName, $attribute)
	{
		$constantName = "Multisite_Option_Attribute::$attribute";
		$constant = constant($constantName);
		$metadata = $this->optionProvider->get($optionName);

		$value = Core_Util_ArrayUtil::get($constant, $metadata, '');

		return $value;
	}
}