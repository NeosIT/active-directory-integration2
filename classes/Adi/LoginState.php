<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (class_exists('NextADInt_Adi_LoginState')) {
    return;
}

/**
 * Contain the current state of authentication and authorization of a user login workflow.
 * This is used to decouple some classes and make testing easier.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_LoginState
{
    /**
     * Indicate that the user has been logged in by NADI
     * @var bool
     */
    private $authenticated = false;

    /**
     * Not yet used
     * @var bool
     */
    private $authorized = false;

    /**
     * Return if user has been authenticated by NADI
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * Return true if user has been authorized by NADI
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * Set the NADI authentication has succeeded
     */
    public function setAuthenticationSucceeded()
    {
        $this->authenticated = true;
    }

    /**
     * Set the NADI authorization has succeeded
     */
    public function setAuthorizationSucceeded()
    {
        $this->authorized = true;
    }
}
