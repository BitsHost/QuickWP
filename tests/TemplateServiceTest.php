<?php

declare(strict_types=1);

namespace QuickWP\Tests;

use PHPUnit\Framework\TestCase;
use QuickWP\Service\TemplateService;
use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

class TemplateServiceTest extends TestCase
{
    private function createMockConfig(array $data = []): SiteConfig
    {
        $defaults = [
            'posts_endpoint' => 'https://example.com/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://example.com/wp-json/wp/v2/pages',
            'wp_username' => 'admin',
            'wp_app_password' => 'test',
            'page_templates' => [],
            'post_templates' => [],
        ];
        return new SiteConfig(array_merge($defaults, $data));
    }

    public function testFormatTemplateNameConvertsFilename(): void
    {
        $config = $this->createMockConfig();
        $client = $this->createMock(RestClient::class);
        $service = new TemplateService($config, $client);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatTemplateName');
        $method->setAccessible(true);
        
        // Tests based on actual implementation behavior
        $this->assertEquals('Full Width', $method->invoke($service, 'full-width.php'));
        $this->assertEquals('Landing Page', $method->invoke($service, 'landing-page.php'));
        $this->assertEquals('Product', $method->invoke($service, 'single-product.php')); // 'single-' prefix is removed
        $this->assertEquals('Full Width', $method->invoke($service, 'template-full-width.php')); // 'template-' prefix is removed
    }

    public function testFormatTemplateNameHandlesEmpty(): void
    {
        $config = $this->createMockConfig();
        $client = $this->createMock(RestClient::class);
        $service = new TemplateService($config, $client);
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatTemplateName');
        $method->setAccessible(true);
        
        // Empty string returns empty (default template handled separately)
        $this->assertEquals('', $method->invoke($service, ''));
    }

    public function testGetPageTemplatesReturnsConfigIfApiFails(): void
    {
        $configTemplates = ['full-width.php' => 'Full Width'];
        $config = $this->createMockConfig(['page_templates' => $configTemplates]);
        
        $client = $this->createMock(RestClient::class);
        $client->method('options')
            ->willReturn(['ok' => false, 'http_code' => 401, 'json' => null]);
        
        $service = new TemplateService($config, $client);
        $templates = $service->getPageTemplates();
        
        // Service adds default template and merges config templates
        $this->assertArrayHasKey('', $templates);
        $this->assertEquals('— Default Template —', $templates['']);
        $this->assertEquals('Full Width', $templates['full-width.php']);
    }

    public function testGetPostTemplatesReturnsConfigIfApiFails(): void
    {
        $configTemplates = ['single-full.php' => 'Full Post'];
        $config = $this->createMockConfig(['post_templates' => $configTemplates]);
        
        $client = $this->createMock(RestClient::class);
        $client->method('options')
            ->willReturn(['ok' => false, 'http_code' => 401, 'json' => null]);
        
        $service = new TemplateService($config, $client);
        $templates = $service->getPostTemplates();
        
        // Service adds default template and merges config templates
        $this->assertArrayHasKey('', $templates);
        $this->assertEquals('— Default Template —', $templates['']);
        $this->assertEquals('Full Post', $templates['single-full.php']);
    }
}
