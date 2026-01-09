<?php

namespace QuickWP\Config;

/**
 * Immutable configuration wrapper for QuickWP.
 * Encapsulates endpoints, auth, access mode, and other flags.
 */
class Config
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * Create a new Config with merged overrides.
     * @return static
     */
    public function merge(array $overrides): self
    {
        $class = static::class;
        return new $class(array_merge($this->data, $overrides));
    }

    /**
     * Create a new Config with a single value changed.
     * @return static
     */
    public function with(string $key, $value): self
    {
        $data = $this->data;
        $data[$key] = $value;
        $class = static::class;
        return new $class($data);
    }
}
