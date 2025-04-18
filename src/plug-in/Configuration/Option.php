<?php

namespace Dreitier\Nadi\Configuration;

use ArrayAccess;

/**
 * Container for NADI options.
 * This container allows lazy loading of option attributes, specifically for 'title' and 'description' attributes.
 */
class Option implements ArrayAccess
{
    private array $initializedAttributes = [];
    private array $uninitializedAttributes = [];

    /**
     * TODO: Move to `private readonly` with a newer release in 2026
     * @param array $uninitializedAttributes
     */
    public function __construct(array $uninitializedAttributes = [])
    {
        $this->uninitializedAttributes = $uninitializedAttributes;
    }

	/**
	 * Create a new Option
	 * @param array $uninitializedAttributes
	 * @return Option
	 */
    public static function make(array $uninitializedAttributes = []): Option
    {
        return new static($uninitializedAttributes);
    }

    public function offsetSet($offset, $value): void
    {
        throw new \Exception("Option container is immutable");
    }

    public function offsetExists($offset): bool
    {
        return isset($this->uninitializedAttributes[$offset]);
    }

    public function offsetUnset($offset): void
    {
        throw new \Exception("Option container is immutable");
    }

    public function offsetGet($offset): mixed
    {
        if (isset($this->initializedAttributes[$offset])) {
            return $this->initializedAttributes[$offset];
        }

        if (!isset($this->uninitializedAttributes[$offset])) {
            return null;
        }

		// If attribute is a function, use it to initialize its value.
		// This allows accessing options before the 'init' hook (e.g. in `set_current_user`)
		// but prevents textdomain loading issues.
        $uninitialized = $this->uninitializedAttributes[$offset];
        $initialized = is_callable($uninitialized) ? $uninitialized() : $uninitialized;

        $this->initializedAttributes[$offset] = $initialized;

        return $initialized;
    }
}