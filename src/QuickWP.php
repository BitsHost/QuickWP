<?php

namespace QuickWP;

use QuickWP\Config\ConfigLoader;
use QuickWP\Config\SiteConfig;
use QuickWP\Http\AccessControl;
use QuickWP\Http\RestClient;
use QuickWP\Service\PostService;
use QuickWP\Service\PageService;
use QuickWP\Service\CptService;
use QuickWP\Service\MediaService;
use QuickWP\Service\TaxonomyService;
use QuickWP\Service\MenuService;
use QuickWP\Service\TemplateService;

/**
 * QuickWP - Main entry point and facade for WordPress REST API operations.
 * 
 * Provides a fluent, easy-to-use API for common WordPress operations.
 * 
 * Usage:
 *   $qwp = QuickWP::init(__DIR__);
 *   $result = $qwp->posts()->create(['title' => 'Hello', 'content' => 'World']);
 */
class QuickWP
{
    private SiteConfig $config;
    private RestClient $client;

    private ?PostService $postService = null;
    private ?PageService $pageService = null;
    private ?CptService $cptService = null;
    private ?MediaService $mediaService = null;
    private ?TaxonomyService $taxonomyService = null;
    private ?MenuService $menuService = null;
    private ?TemplateService $templateService = null;

    public function __construct(SiteConfig $config, ?RestClient $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new RestClient();
    }

    /**
     * Initialize QuickWP with config from files.
     * 
     * @param string $baseDir Base directory containing quick-config.php and quick-sites.php
     * @param string $siteKey Optional site key (auto-detected from request if empty)
     * @param bool $enforceAccess Whether to enforce access control (default: true)
     * @return self
     */
    public static function init(string $baseDir, string $siteKey = '', bool $enforceAccess = true): self
    {
        $loader = new ConfigLoader($baseDir);
        
        if ($siteKey === '') {
            $siteKey = $loader->resolveSiteKey();
        }
        
        $config = $loader->createSiteConfig($siteKey);

        if ($enforceAccess) {
            $access = new AccessControl($config);
            $access->enforce();
        }

        return new self($config);
    }

    /**
     * Create instance with explicit config array.
     */
    public static function withConfig(array $config): self
    {
        return new self(new SiteConfig($config));
    }

    /**
     * Get the current configuration.
     */
    public function getConfig(): SiteConfig
    {
        return $this->config;
    }

    /**
     * Get the REST client.
     */
    public function getClient(): RestClient
    {
        return $this->client;
    }

    /**
     * Get PostService for post operations.
     */
    public function posts(): PostService
    {
        if ($this->postService === null) {
            $this->postService = new PostService($this->config, $this->client);
        }
        return $this->postService;
    }

    /**
     * Get PageService for page operations.
     */
    public function pages(): PageService
    {
        if ($this->pageService === null) {
            $this->pageService = new PageService($this->config, $this->client);
        }
        return $this->pageService;
    }

    /**
     * Get CptService for custom post type operations.
     */
    public function cpt(): CptService
    {
        if ($this->cptService === null) {
            $this->cptService = new CptService($this->config, $this->client);
        }
        return $this->cptService;
    }

    /**
     * Get MediaService for media operations.
     */
    public function media(): MediaService
    {
        if ($this->mediaService === null) {
            $this->mediaService = new MediaService($this->config, $this->client);
        }
        return $this->mediaService;
    }

    /**
     * Get TaxonomyService for taxonomy operations.
     */
    public function taxonomy(): TaxonomyService
    {
        if ($this->taxonomyService === null) {
            $this->taxonomyService = new TaxonomyService($this->config, $this->client);
        }
        return $this->taxonomyService;
    }

    /**
     * Get MenuService for menu operations.
     */
    public function menus(): MenuService
    {
        if ($this->menuService === null) {
            $this->menuService = new MenuService($this->config, $this->client);
        }
        return $this->menuService;
    }

    /**
     * Get TemplateService for fetching available templates.
     */
    public function templates(): TemplateService
    {
        if ($this->templateService === null) {
            $this->templateService = new TemplateService($this->config, $this->client);
        }
        return $this->templateService;
    }

    /**
     * Shortcut: Create a post.
     */
    public function createPost(array $data): array
    {
        return $this->posts()->create($data);
    }

    /**
     * Shortcut: Create a page.
     */
    public function createPage(array $data): array
    {
        return $this->pages()->create($data);
    }

    /**
     * Shortcut: Create a CPT item.
     */
    public function createCptItem(string $slug, array $data): array
    {
        return $this->cpt()->create($slug, $data);
    }

    /**
     * Shortcut: Upload media.
     */
    public function uploadMedia(array $fileInfo, array $data = []): array
    {
        return $this->media()->upload($fileInfo, $data);
    }

    /**
     * Shortcut: Create a category.
     */
    public function createCategory(array $data): array
    {
        return $this->taxonomy()->createCategory($data);
    }

    /**
     * Shortcut: Create a tag.
     */
    public function createTag(array $data): array
    {
        return $this->taxonomy()->createTag($data);
    }

    /**
     * Check if the last operation was successful.
     */
    public static function isSuccess(array $result): bool
    {
        return !empty($result['ok']);
    }

    /**
     * Get error message from a failed result.
     */
    public static function getError(array $result): string
    {
        $parts = [];

        if (!empty($result['http_code']) && $result['http_code'] >= 400) {
            $parts[] = 'HTTP ' . $result['http_code'];
        }

        if (!empty($result['curl_error'])) {
            $parts[] = $result['curl_error'];
        }

        if (!empty($result['json']['message'])) {
            $parts[] = $result['json']['message'];
        }

        return implode(' | ', $parts) ?: 'Unknown error';
    }

    /**
     * Get item ID from a successful result.
     */
    public static function getId(array $result): ?int
    {
        return $result['json']['id'] ?? null;
    }

    /**
     * Get item link from a successful result.
     */
    public static function getLink(array $result): ?string
    {
        return $result['json']['link'] ?? ($result['json']['source_url'] ?? null);
    }
}
