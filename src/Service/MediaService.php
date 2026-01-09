<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for uploading media and managing featured images via WordPress REST API.
 */
class MediaService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Upload a media file.
     *
     * @param array $fileInfo PHP $_FILES array item (name, tmp_name, type, etc.)
     * @param array $data Additional media data (title, alt_text, caption, description, post)
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response with 'ok', 'http_code', 'json', etc.
     */
    public function upload(array $fileInfo, array $data = [], ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getMediaEndpoint();
        
        if ($endpoint === '') {
            // Try to derive from posts endpoint
            $endpoint = $this->config->deriveEndpoint($this->config->getPostsEndpoint(), 'media');
        }

        if ($endpoint === '') {
            return $this->errorResponse('Media endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $fields = $this->buildMediaFields($data);

        return $this->client->uploadFile(
            $endpoint,
            $fileInfo,
            $fields,
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Set featured image for a post.
     *
     * @param int $postId Post ID
     * @param int $mediaId Media ID
     * @param string|null $username Override username (optional)
     * @param string|null $appPassword Override app password (optional)
     * @return array Response
     */
    public function setFeaturedImage(int $postId, int $mediaId, ?string $username = null, ?string $appPassword = null): array
    {
        if ($postId <= 0 || $mediaId <= 0) {
            return $this->errorResponse('Invalid post ID or media ID.');
        }

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
            ['featured_media' => $mediaId],
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Get a single media item by ID.
     */
    public function get(int $mediaId, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getMediaEndpoint();
        
        if ($endpoint === '') {
            $endpoint = $this->config->deriveEndpoint($this->config->getPostsEndpoint(), 'media');
        }

        if ($endpoint === '') {
            return $this->errorResponse('Media endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $mediaId;

        return $this->client->get($url, $user, $pass, $this->config->verifySsl());
    }

    /**
     * List media items with optional filters.
     */
    public function list(array $params = [], ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getMediaEndpoint();
        
        if ($endpoint === '') {
            $endpoint = $this->config->deriveEndpoint($this->config->getPostsEndpoint(), 'media');
        }

        if ($endpoint === '') {
            return $this->errorResponse('Media endpoint not configured.');
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
     * Update media item metadata.
     */
    public function update(int $mediaId, array $data, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getMediaEndpoint();
        
        if ($endpoint === '') {
            $endpoint = $this->config->deriveEndpoint($this->config->getPostsEndpoint(), 'media');
        }

        if ($endpoint === '') {
            return $this->errorResponse('Media endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $url = rtrim($endpoint, '/') . '/' . $mediaId;

        return $this->client->postJson(
            $url,
            $this->buildMediaFields($data),
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Delete a media item.
     */
    public function delete(int $mediaId, bool $force = true, ?string $username = null, ?string $appPassword = null): array
    {
        $endpoint = $this->config->getMediaEndpoint();
        
        if ($endpoint === '') {
            $endpoint = $this->config->deriveEndpoint($this->config->getPostsEndpoint(), 'media');
        }

        if ($endpoint === '') {
            return $this->errorResponse('Media endpoint not configured.');
        }

        $user = $username ?? $this->config->getUsername();
        $pass = $appPassword ?? $this->config->getAppPassword();

        $url = rtrim($endpoint, '/') . '/' . $mediaId;

        return $this->client->delete($url, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Build media fields array from input data.
     */
    private function buildMediaFields(array $data): array
    {
        $fields = [];

        // Standard media fields
        $mediaFields = ['title', 'alt_text', 'caption', 'description'];
        
        foreach ($mediaFields as $field) {
            if (!empty($data[$field])) {
                $fields[$field] = $data[$field];
            }
        }

        // Attach to post
        if (!empty($data['post']) && (int) $data['post'] > 0) {
            $fields['post'] = (int) $data['post'];
        }

        return $fields;
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
