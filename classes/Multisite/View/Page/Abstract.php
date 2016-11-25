<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_View_Page_Abstract')) {
	return;
}

/**
 * NextADInt_Multisite_View_Page_Abstract provides the basic functionality for working with pages.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
abstract class NextADInt_Multisite_View_Page_Abstract implements NextADInt_Multisite_View_Page
{
	/** @var NextADInt_Multisite_View_TwigContainer */
	protected $twigContainer;

	/**
	 * Adi_Page_PageAbstract constructor.
	 *
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 */
	protected function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer)
	{
		$this->twigContainer = $twigContainer;
	}

	/**
	 * Render the provided data as JSON and exit
	 *
	 * @param mixed $data
	 */
	protected function renderJson($data)
	{
		$this->display('ajax-json.twig', array('data' => $data));
		exit;
	}

	/**
	 * Check if the user has the permission to view this page and either send a message or render the page.
	 *
	 * @param string $template
	 * @param array  $params
	 *
	 * @return string
	 */
	protected function render($template, $params = array())
	{
		// check if the user is able to view this site
		$this->checkCapability();

		// get twig and render the display
		$twig = $this->twigContainer->getTwig();

		return $twig->render($template, $params);
	}

	/**
	 * Get the rendered template and display it.
	 *
	 * @param       $template
	 * @param array $params
	 */
	protected function display($template, $params = array())
	{
		echo $this->render($template, $params);
	}

	/**
	 * Check if the current user can see this page.
	 * If not an error will be shown.
	 */
	protected function checkCapability()
	{
		if (!$this->currentUserHasCapability()) {
			$message = esc_html__('You do not have sufficient permissions to access this page.', 'next-active-directory-integration');
			wp_die($message);
		}
	}

	/**
	 * @see check_ajax_referer()
	 */
	protected function checkNonce() {
		check_ajax_referer($this->getNonce(), 'security', true);
	}

	/**
	 * Check if the current user has the given capability.
	 *
	 * @return bool
	 */
	protected function currentUserHasCapability()
	{
		return current_user_can($this->getCapability());
	}

	/**
	 * Get the current capability to check if the user has permission to view this page.
	 *
	 * @return string
	 */
	protected abstract function getCapability();

	/**
	 * Get the current nonce value.
	 *
	 * @return mixed
	 */
	protected abstract function getNonce();
}