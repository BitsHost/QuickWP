<?php

namespace QuickWP\Config;

/**
 * Represents a single site's configuration (endpoints, credentials, flags).
 * Provides convenience methods for common endpoint operations.
 */
class SiteConfig extends Config
{
    // Common config keys
    public const KEY_POSTS_ENDPOINT = 'posts_endpoint';
    public const KEY_PAGES_ENDPOINT = 'pages_endpoint';
    public const KEY_MEDIA_ENDPOINT = 'media_endpoint';
    public const KEY_CATEGORIES_ENDPOINT = 'categories_endpoint';
    public const KEY_TAGS_ENDPOINT = 'tags_endpoint';
    public const KEY_USERNAME = 'wp_username';
    public const KEY_APP_PASSWORD = 'wp_app_password';
    public const KEY_VERIFY_SSL = 'verify_ssl';
    public const KEY_DEBUG_HTTP = 'debug_http';
    public const KEY_SHOW_AUTH_FORM = 'show_auth_form';
    public const KEY_ACCESS_MODE = 'access_mode';
    public const KEY_ACCESS_BASIC_USER = 'access_basic_user';
    public const KEY_ACCESS_BASIC_PASSWORD = 'access_basic_password';
    public const KEY_ACCESS_TOKEN = 'access_token';
    public const KEY_CPT_DEFAULT_SLUG = 'cpt_default_slug';
    public const KEY_PAGE_TEMPLATES = 'page_templates';
    public const KEY_POST_TEMPLATES = 'post_templates';

    /**
     * Get posts endpoint URL.
     */
    public function getPostsEndpoint(): string
    {
        return $this->get(self::KEY_POSTS_ENDPOINT, '');
    }

    /**
     * Get pages endpoint URL.
     */
    public function getPagesEndpoint(): string
    {
        return $this->get(self::KEY_PAGES_ENDPOINT, '');
    }

    /**
     * Get media endpoint URL.
     */
    public function getMediaEndpoint(): string
    {
        return $this->get(self::KEY_MEDIA_ENDPOINT, '');
    }

    /**
     * Get categories endpoint URL (or derive from posts endpoint).
     */
    public function getCategoriesEndpoint(): string
    {
        $endpoint = $this->get(self::KEY_CATEGORIES_ENDPOINT, '');
        if ($endpoint === '') {
            $endpoint = $this->deriveEndpoint($this->getPostsEndpoint(), 'categories');
        }
        return $endpoint;
    }

    /**
     * Get tags endpoint URL (or derive from posts endpoint).
     */
    public function getTagsEndpoint(): string
    {
        $endpoint = $this->get(self::KEY_TAGS_ENDPOINT, '');
        if ($endpoint === '') {
            $endpoint = $this->deriveEndpoint($this->getPostsEndpoint(), 'tags');
        }
        return $endpoint;
    }

    /**
     * Get WordPress username.
     */
    public function getUsername(): string
    {
        return $this->get(self::KEY_USERNAME, '');
    }

    /**
     * Get WordPress Application Password.
     */
    public function getAppPassword(): string
    {
        return $this->get(self::KEY_APP_PASSWORD, '');
    }

    /**
     * Check if SSL verification is enabled.
     */
    public function verifySsl(): bool
    {
        return (bool) $this->get(self::KEY_VERIFY_SSL, true);
    }

    /**
     * Check if HTTP debug is enabled.
     */
    public function debugHttp(): bool
    {
        return (bool) $this->get(self::KEY_DEBUG_HTTP, false);
    }

    /**
     * Check if auth form should be shown.
     */
    public function showAuthForm(): bool
    {
        return (bool) $this->get(self::KEY_SHOW_AUTH_FORM, true);
    }

    /**
     * Get access mode (none, basic, token).
     */
    public function getAccessMode(): string
    {
        return $this->get(self::KEY_ACCESS_MODE, 'none');
    }

    /**
     * Get default CPT slug.
     */
    public function getDefaultCptSlug(): string
    {
        return $this->get(self::KEY_CPT_DEFAULT_SLUG, 'post');
    }

    /**
     * Build CPT endpoint from posts endpoint and CPT slug.
     */
    public function buildCptEndpoint(string $slug, ?string $customEndpoint = null): string
    {
        $slug = trim($slug);
        if ($customEndpoint !== null && $customEndpoint !== '') {
            return $customEndpoint;
        }
        if ($slug === '') {
            return $this->getPostsEndpoint();
        }

        return $this->deriveEndpoint($this->getPostsEndpoint(), $slug);
    }

    /**
     * Derive a taxonomy or CPT endpoint from a base endpoint.
     */
    public function deriveEndpoint(string $baseEndpoint, string $type): string
    {
        if ($baseEndpoint === '') {
            return '';
        }

        // Pretty permalinks: https://example.com/wp-json/wp/v2/posts
        if (preg_match('~^(.*?/wp-json/wp/v2/)([^/?]+)(.*)$~', $baseEndpoint, $m)) {
            return $m[1] . $type . $m[3];
        }

        // rest_route style: https://example.com/?rest_route=/wp/v2/posts
        if (preg_match('~(rest_route=/wp/v2/)([^&]+)~', $baseEndpoint)) {
            return preg_replace('~(rest_route=/wp/v2/)([^&]+)~', '$1' . $type, $baseEndpoint);
        }

        // Fallback: replace trailing "posts" or "pages" with the new type
        foreach (['posts', 'pages', 'media'] as $suffix) {
            if (substr($baseEndpoint, -strlen($suffix)) === $suffix) {
                return substr($baseEndpoint, 0, -strlen($suffix)) . $type;
            }
        }

        return $baseEndpoint;
    }

    /**
     * Get base REST API endpoint (without specific resource type).
     * Returns: https://example.com/wp-json/wp/v2
     */
    public function getBaseEndpoint(): string
    {
        $postsEndpoint = $this->getPostsEndpoint();
        if ($postsEndpoint === '') {
            return '';
        }

        // Pretty permalinks: extract base from /wp-json/wp/v2/posts
        if (preg_match('~^(.*?/wp-json/wp/v2)/[^/?]+~', $postsEndpoint, $m)) {
            return $m[1];
        }

        // rest_route style: https://example.com/?rest_route=/wp/v2/posts
        if (preg_match('~^(.*?)\?rest_route=/wp/v2/~', $postsEndpoint, $m)) {
            return $m[1] . '?rest_route=/wp/v2';
        }

        // Fallback: remove trailing resource type
        foreach (['posts', 'pages', 'media'] as $suffix) {
            if (substr($postsEndpoint, -strlen($suffix)) === $suffix) {
                return substr($postsEndpoint, 0, -strlen($suffix) - 1); // -1 for trailing slash
            }
        }

        return $postsEndpoint;
    }

    /**
     * Check if credentials are configured.
     */
    public function hasCredentials(): bool
    {
        return $this->getUsername() !== '' && $this->getAppPassword() !== '';
    }

    /**
     * Get page templates configuration.
     */
    public function getPageTemplates(): array
    {
        return $this->get(self::KEY_PAGE_TEMPLATES, []);
    }

    /**
     * Get post templates configuration.
     */
    public function getPostTemplates(): array
    {
        return $this->get(self::KEY_POST_TEMPLATES, []);
    }
}
