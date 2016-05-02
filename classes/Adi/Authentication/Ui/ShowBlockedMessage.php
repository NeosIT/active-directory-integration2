<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_Ui_ShowBlockedMessage')) {
	return;
}

/**
 * Adi_Authentication_Ui_ShowBlockedMessage blocks user and shows block message.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Adi_Authentication_Ui_ShowBlockedMessage
{
	const TEMPLATE_NAME = 'block-user.twig';

	/* @var Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var Multisite_View_TwigContainer $twigContainer */
	private $twigContainer;

	/* @var Logger $logger */
	private $logger;

	/**
	 * @param Multisite_Configuration_Service $configuration
	 * @param Multisite_View_TwigContainer            $twigContainer
	 */
	public function __construct(Multisite_Configuration_Service $configuration,
								Multisite_View_TwigContainer $twigContainer)
	{
		$this->configuration = $configuration;
		$this->twigContainer = $twigContainer;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Block WordPress for the current user if log level is none.
	 */
	public function blockCurrentUser()
	{
		//TODO warum sollte die Sperranzeige denn nicht gezeigt werden, wenn WordPress eh beendet wird
		if (LoggerLevel::OFF !== $this->logger->getLevel()) {
			wp_die();
		}

		//show block message via wp_die
		$blockTime = $this->configuration->getOptionValue(Adi_Configuration_Options::BLOCK_TIME);
		
		$this->showBlockMessage($blockTime);
	}

	/**
	 * Exit PHP Code because the user profile is still blocked.
	 *
	 * @param int $timeLeft
	 */
	public function showBlockMessage($timeLeft)
	{
		// animate the counter (add javaScript to file)
		echo $this->twigContainer->getTwig()->render(
			self::TEMPLATE_NAME, array(
				'timeLeft' => $timeLeft + 5, //prevent user profile is still blocked for example 10 ms
			)
		);

		$display = __("Your account is blocked for %s seconds.", ADI_I18N);
		$display = sprintf($display, "<span id='secondsLeft'>$timeLeft</span>");

		wp_die($display);
	}
}