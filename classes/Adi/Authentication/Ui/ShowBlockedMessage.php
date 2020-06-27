<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Ui_ShowBlockedMessage')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Ui_ShowBlockedMessage blocks user and shows block message.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Authentication_Ui_ShowBlockedMessage
{
	const TEMPLATE_NAME = 'block-user.twig';

	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var NextADInt_Multisite_View_TwigContainer $twigContainer */
	private $twigContainer;

	/* @var Logger $logger */
	private $logger;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Multisite_View_TwigContainer            $twigContainer
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Multisite_View_TwigContainer $twigContainer)
	{
		$this->configuration = $configuration;
		$this->twigContainer = $twigContainer;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Block WordPress for the current user if log level is none.
	 */
	public function blockCurrentUser()
	{
		//show block message via wp_die
		$blockTime = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::BLOCK_TIME);
		
		$this->showBlockMessage($blockTime);
	}

	/**
	 * Exit PHP Code because the user profile is still blocked.
	 *
	 * @param int $timeLeft
	 */
	public function showBlockMessage($timeLeft)
	{
        // show special brute force message for xmlrpc user
		if (strpos($_SERVER['PHP_SELF'], 'xmlrpc.php') !== false) {
            $xmlRpcDisplay = __('Authentication denied by Next Active Directory Integration Brute Force Protection. Your account is blocked for %s seconds.', 'next-active-directory-integration');
			$xmlRpcDisplay = sprintf($xmlRpcDisplay, $timeLeft);
			wp_die($xmlRpcDisplay);
			return;
		}

		// animate the counter (add javaScript to file)
		echo $this->twigContainer->getTwig()->render(
			self::TEMPLATE_NAME, array(
				'timeLeft' => $timeLeft, //prevent user profile is still blocked for example 10 ms
			)
		);

		$display = __('Authentication denied by Next Active Directory Integration Brute Force Protection. <br> Your account is blocked for %s seconds.', 'next-active-directory-integration');
		$display = sprintf($display, "<span id='secondsLeft'>$timeLeft</span>");

		wp_die($display);
	}
}