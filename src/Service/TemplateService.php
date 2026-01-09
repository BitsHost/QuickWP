<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for fetching available WordPress templates via REST API.
 */
class TemplateService
{
    private SiteConfig $config;
    private RestClient $client;
    
    private static array $cache = [];

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Get available page templates from WordPress.
     * Fetches from the REST API schema.
     * 
     * @return array ['template-file.php' => 'Template Name', ...]
     */
    public function getPageTemplates(): array
    {
        $cacheKey = 'page_templates_' . md5($this->config->getPagesEndpoint());
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $templates = $this->fetchTemplatesFromSchema($this->config->getPagesEndpoint());
        
        // Merge with config templates (config takes precedence for labels)
        $configTemplates = $this->config->getPageTemplates();
        if (!empty($configTemplates)) {
            foreach ($configTemplates as $file => $name) {
                if ($file !== '' && $file !== '__custom__') {
                    $templates[$file] = $name;
                }
            }
        }
        
        // Ensure default option exists
        if (!isset($templates[''])) {
            $templates = ['' => '— Default Template —'] + $templates;
        }
        
        self::$cache[$cacheKey] = $templates;
        return $templates;
    }

    /**
     * Get available post templates from WordPress.
     * 
     * @return array ['template-file.php' => 'Template Name', ...]
     */
    public function getPostTemplates(): array
    {
        $cacheKey = 'post_templates_' . md5($this->config->getPostsEndpoint());
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $templates = $this->fetchTemplatesFromSchema($this->config->getPostsEndpoint());
        
        // Merge with config templates
        $configTemplates = $this->config->getPostTemplates();
        if (!empty($configTemplates)) {
            foreach ($configTemplates as $file => $name) {
                if ($file !== '' && $file !== '__custom__') {
                    $templates[$file] = $name;
                }
            }
        }
        
        // Ensure default option exists
        if (!isset($templates[''])) {
            $templates = ['' => '— Default Template —'] + $templates;
        }
        
        self::$cache[$cacheKey] = $templates;
        return $templates;
    }

    /**
     * Fetch templates from the REST API schema (OPTIONS request).
     */
    private function fetchTemplatesFromSchema(string $endpoint): array
    {
        $templates = [];
        
        if ($endpoint === '') {
            return $templates;
        }

        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        // Make OPTIONS request to get schema with template enum
        $result = $this->client->options($endpoint, $user, $pass, $this->config->verifySsl());
        
        if (!$result['ok'] || empty($result['json'])) {
            return $templates;
        }

        // Look for template field in schema
        $schema = $result['json']['schema']['properties']['template'] ?? null;
        
        if ($schema && isset($schema['enum'])) {
            // WordPress returns template filenames in enum
            foreach ($schema['enum'] as $templateFile) {
                if ($templateFile === '') {
                    $templates[''] = '— Default Template —';
                } else {
                    // Convert filename to readable name
                    $templates[$templateFile] = $this->formatTemplateName($templateFile);
                }
            }
        }

        return $templates;
    }

    /**
     * Convert template filename to readable name.
     * e.g., "template-full-width.php" -> "Full Width"
     */
    private function formatTemplateName(string $filename): string
    {
        // Remove .php extension
        $name = preg_replace('/\.php$/', '', $filename);
        
        // Remove common prefixes
        $name = preg_replace('/^(template|page|single|tpl)-?/', '', $name);
        
        // Convert dashes/underscores to spaces
        $name = str_replace(['-', '_'], ' ', $name);
        
        // Title case
        $name = ucwords(trim($name));
        
        return $name ?: $filename;
    }

    /**
     * Clear the template cache.
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }
}
