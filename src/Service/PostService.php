<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for creating and managing WordPress posts via REST API.
 */
class PostService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Create a new post.
     *
     * @param array $data Post data (title, content, status, excerpt, slug, categories, tags, etc.)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response with 'ok', 'http_code', 'json', etc.
     */
    public function create(array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPostsEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Posts endpoint not configured.');
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
     * Update an existing post.
     *
     * @param int $postId Post ID
     * @param array $data Fields to update
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function update(int $postId, array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPostsEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Posts endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $url = rtrim($endpoint, '/') . '/' . $postId;

        return $this->client->postJson(
            $url,
            $this->buildPayload($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Get a single post by ID.
     */
    public function get(int $postId, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPostsEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Posts endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $postId;

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * List posts with optional filters.
     */
    public function list(array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPostsEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Posts endpoint not configured.');
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
     * Delete a post.
     */
    public function delete(int $postId, bool $force = false, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPostsEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Posts endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $postId;

        return $this->client->delete($url, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Build post payload from input data.
     */
    private function buildPayload(array $data): array
    {
        $payload = [];

        // Standard fields
        $fields = ['title', 'content', 'excerpt', 'status', 'slug', 'date', 'author', 'featured_media', 'comment_status', 'ping_status', 'format', 'sticky'];
        
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
