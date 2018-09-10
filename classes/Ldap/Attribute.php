<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attribute')) {
	return;
}

/**
 * Value object for a mapped Active Directory to WordPress attribute
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Ldap_Attribute
{
	private $type;

	private $metakey;

	private $description;

	/**
	 * @var bool
	 */
	private $syncable;
	
	/**
	 * @var bool
	 */
	private $viewable;

	/**
	 * @var bool
	 */
	private $overwriteWithEmpty;

	/**
	 * Get the type of the attribute like string, bool, list etc.
	 *
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set the type of the attribute like string, bool, list etc.
	 *
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Get the meta key (see database table wp_usermeta) for the attribute.
	 *
	 * @return mixed
	 */
	public function getMetakey()
	{
		return $this->metakey;
	}

	/**
	 * Set the meta key (see database table wp_usermeta) for the attribute.
	 *
	 * @param mixed $metakey
	 */
	public function setMetakey($metakey)
	{
		$this->metakey = $metakey;
	}

	/**
	 * Get the custom description for this attribute.
	 *
	 * @return mixed
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set the custom description for this attribute.
	 *
	 * @param mixed $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Should this attribute be synchronized with the active directory.
	 *
	 * @return bool
	 */
	public function isSyncable()
	{
		return $this->syncable;
	}

	/**
	 * Allow (true) or disallow (false) the synchronization of the attribute with the active directory.
	 *
	 * @param bool $syncable
	 */
	public function setSyncable($syncable)
	{
		$this->syncable = $syncable;
	}

	/**
	 * Should this attribute be visible in the user profile page? (see wordpress/wp-admin/profile.php)
	 *
	 * @return bool
	 */
	public function isViewable()
	{
		return $this->viewable;
	}

	/**
	 * Allow (true) or disallow (false) the visibility of the attribute in the user profile page? (see wordpress/wp-admin/profile.php)
	 *
	 * @param bool $viewable
	 */
	public function setViewable($viewable)
	{
		$this->viewable = $viewable;
	}

	/**
	 * @return bool
	 */
	public function isOverwriteWithEmpty()
	{
		return $this->overwriteWithEmpty;
	}

	/**
	 * @param bool $overwriteWithEmpty
	 */
	public function setOverwriteWithEmpty($overwriteWithEmpty)
	{
		$this->overwriteWithEmpty = $overwriteWithEmpty === 'true'? true: false;
	}

	public function __toString() {
		return "Attribute " . $this->metakey . "={type='" . $this->type . "', syncable='" . $this->syncable . "', viewable='" .  $this->viewable . "', overwriteWithEmpty='" . $this->overwriteWithEmpty ."}";
	}
}