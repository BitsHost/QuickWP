<?php

namespace QuickWP\Http;

use QuickWP\Config\SiteConfig;

/**
 * Handles access control for QuickWP tools (basic auth, token auth).
 */
class AccessControl
{
    private SiteConfig $config;

    public function __construct(SiteConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Check access and terminate with error if unauthorized.
     */
    public function enforce(): void
    {
        $mode = $this->config->getAccessMode();

        if ($mode === 'basic') {
            $this->enforceBasicAuth();
        } elseif ($mode === 'token') {
            $this->enforceTokenAuth();
        }
        // 'none' mode = no access control
    }

    /**
     * Check if access is granted (non-terminating).
     */
    public function isGranted(): bool
    {
        $mode = $this->config->getAccessMode();

        if ($mode === 'basic') {
            return $this->checkBasicAuth();
        }

        if ($mode === 'token') {
            return $this->checkTokenAuth();
        }

        return true; // 'none' mode
    }

    /**
     * Enforce HTTP Basic Auth.
     */
    private function enforceBasicAuth(): void
    {
        if (!$this->checkBasicAuth()) {
            header('WWW-Authenticate: Basic realm="WP Quick Tools"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authentication required.';
            exit;
        }
    }

    /**
     * Check HTTP Basic Auth credentials.
     */
    private function checkBasicAuth(): bool
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? null;
        $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

        $expectedUser = $this->config->get(SiteConfig::KEY_ACCESS_BASIC_USER, '');
        $expectedPass = $this->config->get(SiteConfig::KEY_ACCESS_BASIC_PASSWORD, '');

        return $user === $expectedUser && $pass === $expectedPass;
    }

    /**
     * Enforce URL token auth.
     */
    private function enforceTokenAuth(): void
    {
        if (!$this->checkTokenAuth()) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }

    /**
     * Check URL token.
     */
    private function checkTokenAuth(): bool
    {
        $token = $_GET['token'] ?? '';
        $expectedToken = $this->config->get(SiteConfig::KEY_ACCESS_TOKEN, '');

        return $token !== '' && $token === $expectedToken;
    }
}
