<?php

namespace Dreitier\Nadi\User;

use Dreitier\Nadi\Vendor\Monolog\Logger;

/**
 * Accepts multiple ResolveLocalUser instances and tries each of those to find a local user based upon a given principal.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 */
class LocalUserResolver
{
	/** @var ResolveLocalUser[] */
	private array $resolvers = [];
	private Logger $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @return ResolveLocalUser[]
	 */
	public function getResolvers(): array
	{
		return $this->resolvers;
	}

	/**
	 * Add a new resolver to the list of resolvers
	 * @param ResolveLocalUser $resolveLocalUserBy
	 * @return $this
	 */
	public function add(ResolveLocalUser $resolveLocalUserBy): LocalUserResolver
	{
		$this->resolvers[] = $resolveLocalUserBy;
		return $this;
	}

	/**
	 * Try each of the resolvers of this class and return the first match found.
	 * @return \WP_User|null
	 */
	public function resolve(): ?\WP_User
	{
		$usedResolvers = [];

		foreach ($this->resolvers as $resolver) {
			$description = $resolver->getDescription() ?? 'unknown';
			$r = $resolver->resolve();

			// a local WordPress user had been resolved by a given principal
			if ($r) {
				$this->logger->debug("User resolver '$description' returned a local WordPress user");
				return $r;
			}

			$usedResolvers[] = $description;
		}

		$this->logger->debug("A local WordPress user could not be found by any of the following resolvers: '" . implode(",", $usedResolvers) . "'");

		return null;
	}
}