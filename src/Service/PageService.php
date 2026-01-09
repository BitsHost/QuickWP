<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for creating and managing WordPress pages via REST API.
 */
class PageService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Create a new page.
     *
     * @param array $data Page data (title, content, status, excerpt, slug, parent, menu_order, template, etc.)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response with 'ok', 'http_code', 'json', etc.
     */
    public function create(array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPagesEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Pages endpoint not configured.');
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
     * Update an existing page.
     *
     * @param int $pageId Page ID
     * @param array $data Fields to update
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function update(int $pageId, array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPagesEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Pages endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $url = rtrim($endpoint, '/') . '/' . $pageId;

        return $this->client->postJson(
            $url,
            $this->buildPayload($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Get a single page by ID.
     */
    public function get(int $pageId, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPagesEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Pages endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $pageId;

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * List pages with optional filters.
     */
    public function list(array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPagesEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Pages endpoint not configured.');
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
     * Delete a page.
     */
    public function delete(int $pageId, bool $force = false, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getPagesEndpoint();
        
        if ($endpoint === '') {
            return $this->errorResponse('Pages endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $pageId;

        return $this->client->delete($url, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Build page payload from input data.
     */
    private function buildPayload(array $data): array
    {
        $payload = [];

        // Standard fields
        $fields = ['title', 'content', 'excerpt', 'status', 'slug', 'date', 'author', 'featured_media', 'comment_status', 'ping_status'];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $payload[$field] = $data[$field];
            }
        }

        // Page-specific fields
        if (isset($data['parent'])) {
            $payload['parent'] = (int) $data['parent'];
        }
        if (isset($data['parent_id'])) {
            $payload['parent'] = (int) $data['parent_id'];
        }
        if (isset($data['menu_order'])) {
            $payload['menu_order'] = (int) $data['menu_order'];
        }
        if (!empty($data['template'])) {
            $payload['template'] = $data['template'];
        }

        // Meta fields
        if (!empty($data['meta']) && is_array($data['meta'])) {
            $payload['meta'] = $data['meta'];
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
