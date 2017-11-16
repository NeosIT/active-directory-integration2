<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_adLdap')) {
	return;
}

if (!class_exists('adLDAP')) {
	// get adLdap
	require_once NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php';
}

/**
 * This class extends the functionality of the adLDAP library. 
 *
 * NextADInt_adLdap allows additional functions needed by NADI that are not covered by adLDAP without needing to manage the LDAP configuration.
 *
 * @author Erik Nedwidek
 * @access public
 */
class NextADInt_adLDAP extends adLDAP {
	
	/**
	 *  Finds the sAMAccountName for the LDAP record that has the given email address in one of its proxyAddresses attributes.
	 *  
	 *  EJN - 2017/11/16 - Allow users to log in with one of their email addresses specified in proxyAddresses
	 *  
	 * @param String $proxyAddress The proxy address to use in the look up.
	 *
	 * @return The associated sAMAccountName or false if not found or uniquely found.
	 */
	public function findByProxyAddress($proxyAddress) 
	{
		$filter="(&(objectCategory=user)(proxyAddresses~=smtp:" . $proxyAddress . "))";
        $fields = array("samaccountname");
        $sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);
		
		// Return false if we didn't find exactly one entry.
		if($entries['count'] == 0 || $entries['count'] > 1) {
			$logger->debug("Number of entries: " . $entries['count']);
			return false;
		}
		
		return $entries[0]['samaccountname'][0];
	}
}