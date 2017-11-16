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
	
	public function findByProxyAddress($proxyAddress) 
	{
		$filter="(&(objectCategory=user)(proxyAddresses~=smtp:" . $proxyAddress . "))";
        $fields = array("samaccountname");
        $sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);
		
		if($entries['count'] == 0 || $entries['count'] > 1) {
			$logger->debug("Number of entries: " . $entries['count']);
			return false;
		}
		
		return $entries[0]['samaccountname'][0];
	}
}