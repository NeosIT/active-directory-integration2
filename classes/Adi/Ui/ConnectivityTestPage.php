<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Ui_ConnectivityTestPage')) {
	return;
}

/**
 * Controller for "Test Connection" plug-in view.
 *
 * It collects different system information and connects to the configured Active Directory based upon the provided credentials.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Ui_ConnectivityTestPage extends NextADInt_Multisite_View_Page_Abstract
{
	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'test-connection.twig';
	const NONCE = 'Active Directory Integration Test Authentication Nonce';

	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var NextADInt_Ldap_Attribute_Service $attributeService */
	private $attributeService;

	/** @var NextADInt_Adi_User_Manager $userManager */
	private $userManager;

	/* @var Logger $logger */
	private $logger;

	/** @var string $result */
	private $result;

	/** @var array $output */
	private $log;

	/** @var NextADInt_Adi_Role_Manager */
	private $roleManager;

	/** @var  NextADInt_Ldap_Connection */
	private $ldapConnection;

	/**
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Ldap_Connection $ldapConnection
	 * @param NextADInt_Ldap_Attribute_Service $attributeService
	 * @param NextADInt_Adi_User_Manager $userManager
	 * @param NextADInt_Adi_Role_Manager $roleManager
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Ldap_Connection $ldapConnection,
								NextADInt_Ldap_Attribute_Service $attributeService,
								NextADInt_Adi_User_Manager $userManager,
								NextADInt_Adi_Role_Manager $roleManager)
	{
		parent::__construct($twigContainer);

		$this->configuration = $configuration;
		$this->attributeService = $attributeService;
		$this->ldapConnection = $ldapConnection;
		$this->userManager = $userManager;
		$this->roleManager = $roleManager;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Test authentication', 'next-active-directory-integration');
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		$this->checkCapability();

		// get data from $_POST
		$params = $this->processData();
		$params['nonce'] = wp_create_nonce(self::NONCE); // add nonce for security
		$params['message'] = $this->result;
		$params['log'] = $this->log;

		// TODO @dme duplicated code?
        $params['i18n'] = array(
            'title' => __('Test Active Directory authentication', 'next-active-directory-integration'),
            'descriptionLine1' => __('Please enter the username and password for the account you want to authenticate with. After submitting the request you will get the debug output.', 'next-active-directory-integration'),
            'descriptionLine2' => __('For this page feature of blocking user accounts with failed login attempts is disabled. You do not have to worry about locking an account.', 'next-active-directory-integration'),
            'descriptionLine3' => __('Please note that the entered password is not masked.', 'next-active-directory-integration'),
            'username' => __('Username:', 'next-active-directory-integration'),
            'password' => __('Password (will be shown):', 'next-active-directory-integration'),
            'tryAgain' => __('Try to authenticate again', 'next-active-directory-integration'),
            'tryAuthenticate' => __('Try to authenticate', 'next-active-directory-integration'),
            'showLogOutput' => __('Show log output', 'next-active-directory-integration')
        );

		$i18n = array(
            'title' => __('Test Active Directory authentication', 'next-active-directory-integration'),
            'descriptionLine1' => __('Please enter the username and password for the account you want to authenticate with. After submitting the request you will get the debug output.', 'next-active-directory-integration'),
            'descriptionLine2' => __('For this page feature of blocking user accounts with failed login attempts is disabled. You do not have to worry about locking an account.', 'next-active-directory-integration'),
            'descriptionLine3' => __('Please note that the entered password <strong>is not masked</strong>.', 'next-active-directory-integration'),
            'username' => __('Username:', 'next-active-directory-integration'),
            'password' => __('Password (will be shown):', 'next-active-directory-integration'),
            'tryAgain' => __('Try to authenticate again', 'next-active-directory-integration'),
            'tryAuthenticate' => __('Try to authenticate', 'next-active-directory-integration'),
            'showLogOutput' => __('Show log output', 'next-active-directory-integration')
        );
		$params['i18n'] = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

		// render
		$this->display(self::TEMPLATE, $params);
	}

	/**
	 * This method reads the $_POST array and triggers the connection test (if the admin has pressed the button)
	 *
	 * @return array
	 */
	public function processData()
	{
        // ADI-421 do not revert the character escaping from WordPress because $username and $password must be escaped
        $post = $_POST;

		if (!isset($post['username']) || !isset($post['password'])) {
			return array();
		}

		// before test connection check nonce
		if (!wp_verify_nonce($post['security'], self::NONCE)) {
			$message = __('You do not have sufficient permissions.', 'next-active-directory-integration');
			wp_die($message);
		}

		$username = $post['username'];
		$password = $post['password'];

		NextADInt_Core_Logger::enableFrontendHandler();
		$information = $this->collectInformation($username, $password);
		$this->log = NextADInt_Core_Logger::getBufferedLog();
		NextADInt_Core_Logger::disableFrontendHandler();

		if ($information['authentication_result']) {
			$this->result = esc_html__('User logged on.', 'next-active-directory-integration');
		} else {
			$this->result = esc_html__('Logon failed.', 'next-active-directory-integration');
		}

		return array(
			'status' => $information['authentication_result'],
		);
	}

	/**
	 * Collect the information for the login process
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return array with key 'output' and 'authentication_result'
	 */
	function collectInformation($username, $password)
	{

		// detect support-id
		$supportData = $this->detectSupportData();
		
		foreach ($supportData as $line) {
			$this->logger->info($line);
		}
		
		// detect system environment
		$env = $this->detectSystemEnvironment();
		$this->logger->info('System Information: ');

		foreach ($env as $info) {
			$this->logger->info(' - ' . $info[0] . ": " . $info[1]);
		}

		$this->logger->info('*** Establishing Active Directory connection ***');
		$authenticationResult = $this->connectToActiveDirectory($username, $password);

		return array(
			'authentication_result' => $authenticationResult
		);
	}

	/**
	 * Detect relevant system environment information for debug purposes
	 * @return array of array [['PHP', '5.6'], ['WordPress', '3.5']]
	 */
	function detectSystemEnvironment()
	{
		global $wp_version;

		if (!class_exists('adLDAP')) {
			require_once(NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php');
		}

		return array(
			array('PHP', json_encode(phpversion())),
			array('WordPress', json_encode($wp_version)),
			array('Active Directory Integration', json_encode(NEXT_AD_INT_PLUGIN_VERSION)),
			array('Operating System', json_encode(php_uname())),
			array('Web Server', json_encode(php_sapi_name())),
			array('adLDAP', json_encode(adLDAP::VERSION)),
		);
	}

	/**
	 * Detects the support data
	 * 
	 * @return array
	 */
	function detectSupportData()
	{
		$supportId = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY, get_current_blog_id());
		$siteUrl = get_site_url();
		$siteName = get_bloginfo('name');
		
		if ($supportId == '') {
			$supportId = 'unlicensed';
		}
		
		$supportString = 'Support for: ###' . $supportId . '###' . $siteUrl . '###' . $siteName . '###';
		$supportStringHashed = 'Support Hash: ' . hash('sha256', $supportString);		
		
		return array($supportString, $supportStringHashed);
	}

	/**
	 * Connect to the Active Directory with given username and password
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool if authentication was successful
	 */
	function connectToActiveDirectory($username, $password)
	{
		// create login authenticator with custom logger
		$loginAuthenticator = new NextADInt_Adi_Authentication_LoginService(
			null,
			$this->configuration,
			$this->ldapConnection,
			$this->userManager,
			null,
			null,
			$this->attributeService,
			$this->roleManager
		);

		return $loginAuthenticator->authenticate(null, $username, $password);
	}

	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param string $hook
	 */
	public function loadAdminScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		wp_enqueue_style('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);

		wp_enqueue_style('next_ad_int_bootstrap_min_css', NEXT_AD_INT_URL . '/css/bootstrap.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
        wp_enqueue_script('next_ad_int_bootstrap_min_js', NEXT_AD_INT_URL . '/js/libraries/bootstrap.min.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS);

    }

	/**
	 * Get the menu slug for the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return NEXT_AD_INT_PREFIX . 'test_connection';
	}

	/**
	 * Get the slug for post requests.
	 *
	 * @return mixed
	 */
	public function wpAjaxSlug()
	{
		return null;
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
