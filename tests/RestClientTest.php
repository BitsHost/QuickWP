<?php

declare(strict_types=1);

namespace QuickWP\Tests;

use PHPUnit\Framework\TestCase;
use QuickWP\Http\RestClient;

class RestClientTest extends TestCase
{
    public function testSetTimeoutReturnsSelf(): void
    {
        $client = new RestClient();
        $result = $client->setTimeout(60);
        
        $this->assertSame($client, $result);
    }

    public function testSetConnectTimeoutReturnsSelf(): void
    {
        $client = new RestClient();
        $result = $client->setConnectTimeout(15);
        
        $this->assertSame($client, $result);
    }

    public function testBuildAuthHeaderWithReflection(): void
    {
        $client = new RestClient();
        
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildAuthHeader');
        $method->setAccessible(true);
        
        $header = $method->invoke($client, 'user', 'pass');
        
        $this->assertStringStartsWith('Basic ', $header);
        $this->assertEquals('Basic ' . base64_encode('user:pass'), $header);
    }

    public function testBuildAuthHeaderEncodesSpecialChars(): void
    {
        $client = new RestClient();
        
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildAuthHeader');
        $method->setAccessible(true);
        
        $header = $method->invoke($client, 'admin@site.com', 'pass:word');
        
        $expected = 'Basic ' . base64_encode('admin@site.com:pass:word');
        $this->assertEquals($expected, $header);
    }
}
