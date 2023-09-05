<?php

namespace Dreitier\Nadi\User;

/**
 * Resolve a user by a single search method
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 */
class ResolveLocalUser
{
	private ?string $principal;
	private mixed $searchMethod;
	private ?string $description;

	/**
	 * @param string|null $principal Principal to search for
	 * @param callable $searchMethod How to search for the given principal
	 * @param string|null $description Optional description for debug messages
	 */
	public function __construct(?string $principal, callable $searchMethod, ?string $description = null)
	{
		$this->principal = $principal;
		$this->searchMethod = $searchMethod;
		$this->description = $description;
	}

	public function getPrincipal(): ?string
	{
		return $this->principal;
	}

	public function getSearchMethod(): callable
	{
		return $this->searchMethod;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * Passes the getPrincipal to getSearchMethod and executes the search
	 * @return \WP_User|null
	 */
	public function resolve(): ?\WP_User
	{
		$method = $this->getSearchMethod();

		$r = $method($this->getPrincipal());

		if ($r instanceof \WP_User) {
			return $r;
		}

		return null;
	}

	/**
	 * Create a new ResolveLocalUser instance
	 *
	 * @param string|null $principal
	 * @param callable $searchMethod
	 * @param string|null $description
	 * @return ResolveLocalUser
	 */
	public static function by(?string $principal, callable $searchMethod, ?string $description = null): ResolveLocalUser
	{
		return new static($principal, $searchMethod, $description);
	}
}