<?php

namespace QuickWP\Service;

use QuickWP\Config\SiteConfig;
use QuickWP\Http\RestClient;

/**
 * Service for managing WordPress Menus via REST API.
 * 
 * Note: The WordPress REST API has limited menu support out of the box.
 * This service works with the Menus endpoint if available (WP 5.9+) or
 * requires a plugin like WP REST API Menus for full functionality.
 */
class MenuService
{
    private SiteConfig $config;
    private RestClient $client;

    public function __construct(SiteConfig $config, RestClient $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Get all registered menus.
     * 
     * @return array Response with menu locations and their IDs
     */
    public function getMenus(): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-locations';
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->get($endpoint, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Get a specific menu by location slug.
     * 
     * @param string $location Menu location slug (e.g., 'primary', 'footer')
     * @return array Response with menu items
     */
    public function getMenuByLocation(string $location): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-locations/' . urlencode($location);
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->get($endpoint, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Get all menu items for a specific menu ID.
     * 
     * @param int $menuId The menu ID
     * @return array Response with menu items
     */
    public function getMenuItems(int $menuId): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-items?menus=' . $menuId . '&per_page=100';
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->get($endpoint, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Create a new menu item.
     * 
     * @param int $menuId The menu ID to add item to
     * @param array $data Menu item data
     * @return array Response
     */
    public function createMenuItem(int $menuId, array $data): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-items';
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $payload = $this->buildMenuItemPayload($menuId, $data);

        return $this->client->postJson(
            $endpoint,
            $payload,
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Update an existing menu item.
     * 
     * @param int $itemId Menu item ID
     * @param array $data Fields to update
     * @return array Response
     */
    public function updateMenuItem(int $itemId, array $data): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-items/' . $itemId;
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        return $this->client->postJson(
            $endpoint,
            $data,
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Delete a menu item.
     * 
     * @param int $itemId Menu item ID
     * @param bool $force Force permanent deletion
     * @return array Response
     */
    public function deleteMenuItem(int $itemId, bool $force = false): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menu-items/' . $itemId;
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->delete($endpoint, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Get all navigation menus (requires WP 5.9+).
     * 
     * @return array Response with all nav menus
     */
    public function getNavMenus(): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menus?per_page=100';
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->get($endpoint, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Get a single navigation menu by ID.
     * 
     * @param int $menuId Menu ID
     * @return array Response
     */
    public function getNavMenu(int $menuId): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menus/' . $menuId;
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->get($endpoint, $user, $pass, $this->config->verifySsl());
    }

    /**
     * Create a new navigation menu.
     * 
     * @param string $name Menu name
     * @param string $slug Optional slug
     * @return array Response
     */
    public function createNavMenu(string $name, string $slug = ''): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menus';
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        if ($user === '' || $pass === '') {
            return $this->errorResponse('WordPress credentials not configured.');
        }

        $payload = ['name' => $name];
        if ($slug !== '') {
            $payload['slug'] = $slug;
        }

        return $this->client->postJson(
            $endpoint,
            $payload,
            $user,
            $pass,
            $this->config->verifySsl()
        );
    }

    /**
     * Delete a navigation menu.
     * 
     * @param int $menuId Menu ID
     * @param bool $force Force deletion
     * @return array Response
     */
    public function deleteNavMenu(int $menuId, bool $force = true): array
    {
        $endpoint = $this->config->getBaseEndpoint() . '/menus/' . $menuId;
        
        $user = $this->config->getUsername();
        $pass = $this->config->getAppPassword();

        return $this->client->delete($endpoint, $user, $pass, $this->config->verifySsl(), $force);
    }

    /**
     * Build menu item payload.
     */
    private function buildMenuItemPayload(int $menuId, array $data): array
    {
        $payload = [
            'menus' => $menuId,
            'status' => 'publish',
        ];

        // Title (required)
        if (!empty($data['title'])) {
            $payload['title'] = $data['title'];
        }

        // URL for custom links
        if (!empty($data['url'])) {
            $payload['url'] = $data['url'];
            $payload['type'] = 'custom';
        }

        // Object type (post, page, category, etc.)
        if (!empty($data['type'])) {
            $payload['type'] = $data['type'];
        }

        // Object ID (for linking to posts, pages, categories)
        if (!empty($data['object_id'])) {
            $payload['object_id'] = (int)$data['object_id'];
        }

        // Object type (post_type or taxonomy)
        if (!empty($data['object'])) {
            $payload['object'] = $data['object'];
        }

        // Parent menu item (for hierarchical menus)
        if (!empty($data['parent'])) {
            $payload['parent'] = (int)$data['parent'];
        }

        // Menu order
        if (isset($data['menu_order'])) {
            $payload['menu_order'] = (int)$data['menu_order'];
        }

        // Target (_blank, etc.)
        if (!empty($data['target'])) {
            $payload['target'] = $data['target'];
        }

        // CSS classes
        if (!empty($data['classes'])) {
            $payload['classes'] = is_array($data['classes']) ? $data['classes'] : explode(' ', $data['classes']);
        }

        // Description
        if (!empty($data['description'])) {
            $payload['description'] = $data['description'];
        }

        // Attr title (tooltip)
        if (!empty($data['attr_title'])) {
            $payload['attr_title'] = $data['attr_title'];
        }

        return $payload;
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
