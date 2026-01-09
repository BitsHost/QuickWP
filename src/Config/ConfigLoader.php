<?php

namespace QuickWP\Config;

/**
 * Loads configuration from files and merges site-specific overrides.
 */
class ConfigLoader
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    /**
     * Load the main config file (quick-config.php).
     */
    public function loadBaseConfig(): array
    {
        $configFile = $this->baseDir . '/quick-config.php';
        if (file_exists($configFile)) {
            $cfg = require $configFile;
            if (is_array($cfg)) {
                return $cfg;
            }
        }
        return [];
    }

    /**
     * Load all site configurations from quick-sites.php.
     */
    public function loadSitesConfig(): array
    {
        $sitesFile = $this->baseDir . '/quick-sites.php';
        if (file_exists($sitesFile)) {
            $cfg = require $sitesFile;
            if (is_array($cfg)) {
                return $cfg;
            }
        }
        return [];
    }

    /**
     * Get list of available sites.
     * @return array<string, array> Site key => site config array
     */
    public function getSites(): array
    {
        $sitesCfg = $this->loadSitesConfig();
        return $sitesCfg['sites'] ?? [];
    }

    /**
     * Get the default site key.
     */
    public function getDefaultSiteKey(): string
    {
        $sitesCfg = $this->loadSitesConfig();

        if (!empty($sitesCfg['default_site'])) {
            return $sitesCfg['default_site'];
        }

        // Return first available site key
        $sites = $this->getSites();
        foreach ($sites as $key => $_) {
            return $key;
        }

        return '';
    }

    /**
     * Get configuration for a specific site (merged with base config).
     */
    public function getSiteConfig(string $siteKey = ''): array
    {
        $baseConfig = $this->loadBaseConfig();
        
        if ($siteKey === '') {
            return $baseConfig;
        }

        $sites = $this->getSites();
        if (!isset($sites[$siteKey])) {
            return $baseConfig;
        }

        return array_merge($baseConfig, $sites[$siteKey]);
    }

    /**
     * Create a SiteConfig instance for a given site key.
     */
    public function createSiteConfig(string $siteKey = ''): SiteConfig
    {
        return new SiteConfig($this->getSiteConfig($siteKey));
    }

    /**
     * Resolve site key from request (GET/POST) or return default.
     */
    public function resolveSiteKey(): string
    {
        $siteKey = $_GET['site'] ?? ($_POST['site'] ?? '');
        
        if ($siteKey !== '') {
            $sites = $this->getSites();
            if (isset($sites[$siteKey])) {
                return $siteKey;
            }
        }

        return $this->getDefaultSiteKey();
    }

    /**
     * Get site label for display.
     */
    public function getSiteLabel(string $siteKey): string
    {
        $sites = $this->getSites();
        if (isset($sites[$siteKey])) {
            return $sites[$siteKey]['label'] ?? $siteKey;
        }
        return $siteKey;
    }
}
