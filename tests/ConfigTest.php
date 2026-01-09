<?php

declare(strict_types=1);

namespace QuickWP\Tests;

use PHPUnit\Framework\TestCase;
use QuickWP\Config\Config;
use QuickWP\Config\SiteConfig;

class ConfigTest extends TestCase
{
    public function testConfigStoresData(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $config = new Config($data);
        
        $this->assertEquals('bar', $config->get('foo'));
        $this->assertEquals(123, $config->get('baz'));
    }

    public function testConfigGetWithDefault(): void
    {
        $config = new Config(['existing' => 'value']);
        
        $this->assertEquals('value', $config->get('existing'));
        $this->assertEquals('default', $config->get('missing', 'default'));
        $this->assertNull($config->get('missing'));
    }

    public function testConfigHasKey(): void
    {
        $config = new Config(['exists' => true]);
        
        $this->assertTrue($config->has('exists'));
        $this->assertFalse($config->has('missing'));
    }

    public function testSiteConfigGetPageTemplates(): void
    {
        $templates = ['' => 'Default', 'full.php' => 'Full Width'];
        $config = new SiteConfig(['page_templates' => $templates]);
        
        $this->assertEquals($templates, $config->getPageTemplates());
    }

    public function testSiteConfigGetPostTemplates(): void
    {
        $templates = ['' => 'Default'];
        $config = new SiteConfig(['post_templates' => $templates]);
        
        $this->assertEquals($templates, $config->getPostTemplates());
    }

    public function testSiteConfigDeriveEndpoint(): void
    {
        $config = new SiteConfig(['posts_endpoint' => 'https://example.com/wp-json/wp/v2/posts']);
        
        $derived = $config->deriveEndpoint('https://example.com/wp-json/wp/v2/posts', 'pages');
        $this->assertEquals('https://example.com/wp-json/wp/v2/pages', $derived);
    }

    public function testSiteConfigGetBaseEndpoint(): void
    {
        $config = new SiteConfig(['posts_endpoint' => 'https://example.com/wp-json/wp/v2/posts']);
        
        $base = $config->getBaseEndpoint();
        $this->assertEquals('https://example.com/wp-json/wp/v2', $base);
    }
}
