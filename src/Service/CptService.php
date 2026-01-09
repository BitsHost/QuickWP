<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for creating and managing WordPress Custom Post Type items via REST API.
 */
class CptService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Create a new CPT item.
     *
     * @param string $slug CPT slug (e.g., 'product', 'portfolio', 'event')
     * @param array $data CPT data (title, content, status, etc.)
     * @param string|null $customEndpoint Override endpoint URL (optional)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response with 'ok', 'http_code', 'json', etc.
     */
    public function create(string $slug, array $data, ?string $customEndpoint = null, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->buildCptEndpoint($slug, $customEndpoint);
        
        if ($endpoint === '') {
            return $this->errorResponse('CPT endpoint could not be determined. Configure posts_endpoint or provide a custom endpoint.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        return $this->client->postJson(
            $endpoint,
            $this->buildPayload($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Update an existing CPT item.
     *
     * @param string $slug CPT slug
     * @param int $itemId Item ID
     * @param array $data Fields to update
     * @param string|null $customEndpoint Override endpoint URL (optional)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function update(string $slug, int $itemId, array $data, ?string $customEndpoint = null, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->buildCptEndpoint($slug, $customEndpoint);
        
        if ($endpoint === '') {
            return $this->errorResponse('CPT endpoint could not be determined.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $url = rtrim($endpoint, '/') . '/' . $itemId;

        return $this->client->postJson(
            $url,
            $this->buildPayload($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Get a single CPT item by ID.
     */
    public function get(string $slug, int $itemId, ?string $customEndpoint = null, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->buildCptEndpoint($slug, $customEndpoint);
        
        if ($endpoint === '') {
            return $this->errorResponse('CPT endpoint could not be determined.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $itemId;

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * List CPT items with optional filters.
     */
    public function list(string $slug, array $params = [], ?string $customEndpoint = null, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->buildCptEndpoint($slug, $customEndpoint);
        
        if ($endpoint === '') {
            return $this->errorResponse('CPT endpoint could not be determined.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = $endpoint;
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Delete a CPT item.
     */
    public function delete(string $slug, int $itemId, bool $force = false, ?string $customEndpoint = null, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->buildCptEndpoint($slug, $customEndpoint);
        
        if ($endpoint === '') {
            return $this->errorResponse('CPT endpoint could not be determined.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $itemId;

        return $this->client->delete($url, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Build CPT payload from input data.
     */
    private function buildPayload(array $data): array
    {
        $payload = [];

        // Standard fields (same as posts)
        $fields = ['title', 'content', 'excerpt', 'status', 'slug', 'date', 'author', 'featured_media', 'comment_status', 'ping_status'];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $payload[$field] = $data[$field];
            }
        }

        // Taxonomy fields (arrays of IDs)
        if (!empty($data['categories'])) {
            $payload['categories'] = $this->parseIdList($data['categories']);
        }
        if (!empty($data['tags'])) {
            $payload['tags'] = $this->parseIdList($data['tags']);
        }

        // Custom taxonomy support (pass through any other array of IDs)
        foreach ($data as $key => $value) {
            if (!isset($payload[$key]) && is_array($value) && !empty($value)) {
                // Assume it's a custom taxonomy or meta
                $payload[$key] = $value;
            }
        }

        // Meta fields
        if (!empty($data['meta']) && is_array($data['meta'])) {
            $payload['meta'] = $data['meta'];
        }

        return $payload;
    }

    /**
     * Parse comma-separated ID string or array into array of integers.
     */
    private function parseIdList($input): array
    {
        if (is_array($input)) {
            return array_map('intval', $input);
        }

        if (is_string($input)) {
            $ids = [];
            foreach (explode(',', $input) as $id) {
                $id = (int) trim($id);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            return $ids;
        }

        return [];
    }

    /**
     * Build an error response.
     */
    private function errorResponse(string $message): array
    {
        return [
            'ok' => false,
            'http_code' => 0,
            'curl_error' => $message,
            'raw_body' => null,
            'info' => null,
            'json' => null,
        ];
    }
}
