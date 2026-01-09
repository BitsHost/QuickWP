<?php

namespace QuickWP;

use QuickWP\Config\Config;
use QuickWP\Config\ConfigLoader;
use QuickWP\Config\SiteConfig;
use QuickWP\Http\AccessControl;
use QuickWP\Http\RestClient;
use QuickWP\Service\PostService;
use QuickWP\Service\PageService;
use QuickWP\Service\CptService;
use QuickWP\Service\MediaService;
use QuickWP\Service\TaxonomyService;

/**
 * Main factory/bootstrap for wiring QuickWP components.
 * Provides convenient static methods for creating services with proper dependencies.
 */
class Bootstrap
{
    private static ?RestClient $restClient = null;
    private static ?ConfigLoader $configLoader = null;

    /**
     * Create a ConfigLoader instance.
     */
    public static function createConfigLoader(string $baseDir): ConfigLoader
    {
        return new ConfigLoader($baseDir);
    }

    /**
     * Get or create the singleton ConfigLoader.
     */
    public static function getConfigLoader(string $baseDir = ''): ConfigLoader
    {
        if (self::$configLoader === null) {
            if ($baseDir === '') {
                $baseDir = dirname(__DIR__);
            }
            self::$configLoader = new ConfigLoader($baseDir);
        }
        return self::$configLoader;
    }

    /**
     * Create a SiteConfig from arrays.
     */
    public static function createSiteConfig(array $base, array $siteOverrides = []): SiteConfig
    {
        return new SiteConfig(array_merge($base, $siteOverrides));
    }

    /**
     * Load SiteConfig from config files for a given site key.
     */
    public static function loadSiteConfig(string $siteKey = '', string $baseDir = ''): SiteConfig
    {
        $loader = self::getConfigLoader($baseDir);
        return $loader->createSiteConfig($siteKey);
    }

    /**
     * Get or create the singleton RestClient.
     */
    public static function getRestClient(): RestClient
    {
        if (self::$restClient === null) {
            self::$restClient = new RestClient();
        }
        return self::$restClient;
    }

    /**
     * Create a new RestClient instance.
     */
    public static function createRestClient(): RestClient
    {
        return new RestClient();
    }

    /**
     * Create an AccessControl instance.
     */
    public static function createAccessControl(SiteConfig $config): AccessControl
    {
        return new AccessControl($config);
    }

    /**
     * Create PostService with dependencies.
     */
    public static function createPostService(SiteConfig $config, ?RestClient $client = null): PostService
    {
        return new PostService($config, $client ?? self::getRestClient());
    }

    /**
     * Create PageService with dependencies.
     */
    public static function createPageService(SiteConfig $config, ?RestClient $client = null): PageService
    {
        return new PageService($config, $client ?? self::getRestClient());
    }

    /**
     * Create CptService with dependencies.
     */
    public static function createCptService(SiteConfig $config, ?RestClient $client = null): CptService
    {
        return new CptService($config, $client ?? self::getRestClient());
    }

    /**
     * Create MediaService with dependencies.
     */
    public static function createMediaService(SiteConfig $config, ?RestClient $client = null): MediaService
    {
        return new MediaService($config, $client ?? self::getRestClient());
    }

    /**
     * Create TaxonomyService with dependencies.
     */
    public static function createTaxonomyService(SiteConfig $config, ?RestClient $client = null): TaxonomyService
    {
        return new TaxonomyService($config, $client ?? self::getRestClient());
    }

    /**
     * Initialize and enforce access control.
     */
    public static function enforceAccess(SiteConfig $config): void
    {
        $access = new AccessControl($config);
        $access->enforce();
    }

    /**
     * Quick setup: load config, enforce access, and return config.
     * Useful for the new public controllers.
     */
    public static function init(string $baseDir = ''): SiteConfig
    {
        $loader = self::getConfigLoader($baseDir);
        $siteKey = $loader->resolveSiteKey();
        $config = $loader->createSiteConfig($siteKey);

        self::enforceAccess($config);

        return $config;
    }

    /**
     * Reset singleton instances (useful for testing).
     */
    public static function reset(): void
    {
        self::$restClient = null;
        self::$configLoader = null;
    }
}
