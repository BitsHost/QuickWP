<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for managing WordPress taxonomies (categories, tags, custom) via REST API.
 */
class TaxonomyService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Create a new category.
     *
     * @param array $data Category data (name, slug, description, parent)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function createCategory(array $data, ?string $username = null, ?string $appPassword = null): array
    {
        return $this->create('categories', $data, $username, $appPassword);
    }

    /**
     * Create a new tag.
     *
     * @param array $data Tag data (name, slug, description)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function createTag(array $data, ?string $username = null, ?string $appPassword = null): array
    {
        return $this->create('tags', $data, $username, $appPassword);
    }

    /**
     * Create a term in any taxonomy.
     *
     * @param string $taxonomy Taxonomy slug (categories, tags, or custom)
     * @param array $data Term data
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function create(string $taxonomy, array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->getEndpoint($taxonomy);
        
        if ($endpoint === '') {
            return $this->errorResponse("Endpoint for taxonomy '{$taxonomy}' could not be determined.");
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
     * Update a term in any taxonomy.
     */
    public function update(string $taxonomy, int $termId, array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->getEndpoint($taxonomy);
        
        if ($endpoint === '') {
            return $this->errorResponse("Endpoint for taxonomy '{$taxonomy}' could not be determined.");
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $url = rtrim($endpoint, '/') . '/' . $termId;

        return $this->client->postJson(
            $url,
            $this->buildPayload($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Get a single term by ID.
     */
    public function get(string $taxonomy, int $termId, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->getEndpoint($taxonomy);
        
        if ($endpoint === '') {
            return $this->errorResponse("Endpoint for taxonomy '{$taxonomy}' could not be determined.");
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $termId;

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * List terms in a taxonomy.
     *
     * @param string $taxonomy Taxonomy slug
     * @param array $params Query parameters (per_page, page, search, orderby, order, hide_empty, etc.)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function list(string $taxonomy, array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->getEndpoint($taxonomy);
        
        if ($endpoint === '') {
            return $this->errorResponse("Endpoint for taxonomy '{$taxonomy}' could not be determined.");
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
     * List categories.
     */
    public function listCategories(array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        return $this->list('categories', $params, $username, $appPassword);
    }

    /**
     * List tags.
     */
    public function listTags(array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        return $this->list('tags', $params, $username, $appPassword);
    }

    /**
     * Delete a term.
     */
    public function delete(string $taxonomy, int $termId, bool $force = false, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->getEndpoint($taxonomy);
        
        if ($endpoint === '') {
            return $this->errorResponse("Endpoint for taxonomy '{$taxonomy}' could not be determined.");
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $termId;

        return $this->client->delete($url, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Get endpoint for a taxonomy.
     */
    private function getEndpoint(string $taxonomy): string
    {
        // Check for explicit endpoint in config
        if ($taxonomy === 'categories') {
            $endpoint = $this->config->getCategoriesEndpoint();
            if ($endpoint !== '') {
                return $endpoint;
            }
        }

        if ($taxonomy === 'tags') {
            $endpoint = $this->config->getTagsEndpoint();
            if ($endpoint !== '') {
                return $endpoint;
            }
        }

        // Derive from posts endpoint
        $postsEndpoint = $this->config->getPostsEndpoint();
        if ($postsEndpoint !== '') {
            return $this->config->deriveEndpoint($postsEndpoint, $taxonomy);
        }

        return '';
    }

    /**
     * Build term payload from input data.
     */
    private function buildPayload(array $data): array
    {
        $payload = [];

        // Standard taxonomy term fields
        $fields = ['name', 'slug', 'description', 'parent', 'meta'];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'parent') {
                    $payload[$field] = (int) $data[$field];
                } else {
                    $payload[$field] = $data[$field];
                }
            }
        }

        return $payload;
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
