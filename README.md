# QuickWP – WordPress REST API Toolkit

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/bitshost/quickwp)](https://packagist.org/packages/bitshost/quickwp)

QuickWP is a modern, class-based PHP toolkit for managing WordPress sites via the REST API. It provides both a clean programmatic API and ready-to-use web tools for common WordPress operations.

## 📦 Installation

```bash
composer require bitshost/quickwp
```

## 📋 Requirements

- PHP 7.4+
- WordPress site with REST API enabled
- WordPress Application Password for authentication

## ✨ Features

- **Clean OOP Architecture** – PSR-4 autoloaded classes with dependency injection
- **Fluent API** – Intuitive, chainable interface for all operations
- **Multi-Site Support** – Manage multiple WordPress sites from one installation
- **Web UI Tools** – Ready-to-use forms for posts, pages, media, taxonomies, and CPTs
- **Flexible Access Control** – HTTP Basic Auth, token auth, or no protection
- **Service-Based Design** – Separate services for Posts, Pages, Media, Taxonomies, and CPTs

## 📁 Project Structure

```
QuickWP/
├── src/                          # Core library (PSR-4: QuickWP\)
│   ├── QuickWP.php               # Main facade class
│   ├── Bootstrap.php             # Factory for service creation
│   ├── Config/
│   │   ├── Config.php            # Base immutable config wrapper
│   │   ├── ConfigLoader.php      # Loads config from files
│   │   └── SiteConfig.php        # Site-specific configuration
│   ├── Http/
│   │   ├── AccessControl.php     # Access control middleware
│   │   └── RestClient.php        # cURL-based REST client
│   └── Service/
│       ├── PostService.php       # Post CRUD operations
│       ├── PageService.php       # Page CRUD operations
│       ├── CptService.php        # Custom Post Type operations
│       ├── MediaService.php      # Media upload & management
│       ├── MenuService.php       # Menu operations
│       ├── TaxonomyService.php   # Categories/Tags management
│       └── TemplateService.php   # Template fetching
├── public/                       # Web interface
│   ├── index.php                 # Dashboard
│   ├── quick-post.php            # Create/edit posts
│   ├── quick-page.php            # Create/edit pages
│   ├── quick-media.php           # Upload media
│   ├── quick-cpt.php             # Custom post types
│   ├── quick-edit.php            # Edit by ID
│   ├── quick-taxonomy.php        # Categories/Tags
│   └── quick-wp.php              # Legacy entry point
├── tests/                        # PHPUnit tests
├── quick-config.php              # Your site configuration
├── quick-sites.php               # Multi-site configuration (optional)
└── composer.json                 # PSR-4 autoloader
```

## 🚀 Quick Start

### 1. Install via Composer

```bash
composer require bitshost/quickwp
```

Or clone the repository and run `composer install`.

### 2. Configure Your Site

Copy the example config and edit with your WordPress site details:

```bash
cp quick-config.example.php quick-config.php
```

```php
<?php
return [
    // REST API Endpoints
    'posts_endpoint' => 'https://your-site.com/wp-json/wp/v2/posts',
    'pages_endpoint' => 'https://your-site.com/wp-json/wp/v2/pages',
    'media_endpoint' => 'https://your-site.com/wp-json/wp/v2/media',
    
    // WordPress Credentials (Application Password)
    'wp_username'     => 'your-username',
    'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    
    // Options
    'show_auth_form' => true,   // Show credentials in forms
    'verify_ssl'     => true,   // Verify SSL certificates
    'debug_http'     => false,  // Debug cURL requests
];
```

### 3. Create an Application Password

1. Log in to your WordPress admin
2. Go to **Users → Profile → Application Passwords**
3. Enter a name (e.g., "QuickWP") and click **Add New**
4. Copy the generated password to your `quick-config.php`

### 4. Access the Web Interface

Point your browser to `public/index.php`

## 💻 Programmatic Usage

### Basic Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use QuickWP\QuickWP;

// Initialize with config directory
$qwp = QuickWP::init(__DIR__);

// Create a post
$result = $qwp->posts()->create([
    'title'   => 'My New Post',
    'content' => '<p>Hello from QuickWP!</p>',
    'status'  => 'draft',
]);

if (QuickWP::isSuccess($result)) {
    echo 'Post created! ID: ' . QuickWP::getId($result);
    echo 'Link: ' . QuickWP::getLink($result);
} else {
    echo 'Error: ' . QuickWP::getError($result);
}
```

### Using Services

```php
// Posts
$posts = $qwp->posts();
$posts->create(['title' => 'New Post', 'status' => 'publish']);
$posts->update(123, ['title' => 'Updated Title']);
$posts->get(123);
$posts->list(['per_page' => 10, 'status' => 'draft']);
$posts->delete(123, force: true);

// Pages
$pages = $qwp->pages();
$pages->create(['title' => 'New Page', 'parent' => 0, 'template' => 'full-width.php']);

// Media
$media = $qwp->media();
$media->upload($_FILES['file'], ['title' => 'My Image', 'alt_text' => 'Alt text']);
$media->setFeaturedImage(postId: 123, mediaId: 456);

// Taxonomies
$tax = $qwp->taxonomy();
$tax->createCategory(['name' => 'News', 'slug' => 'news']);
$tax->createTag(['name' => 'Featured']);
$tax->listCategories(['hide_empty' => false]);

// Custom Post Types
$cpt = $qwp->cpt();
$cpt->create('product', ['title' => 'New Product', 'status' => 'publish']);
$cpt->list('product', ['per_page' => 20]);
```

### Alternative Initialization

```php
// From explicit config array
$qwp = QuickWP::withConfig([
    'posts_endpoint'  => 'https://example.com/wp-json/wp/v2/posts',
    'wp_username'     => 'admin',
    'wp_app_password' => 'xxxx xxxx xxxx',
]);

// With specific site from multi-site config
$qwp = QuickWP::init(__DIR__, siteKey: 'staging');

// Without access control enforcement
$qwp = QuickWP::init(__DIR__, enforceAccess: false);
```

## 📚 API Reference

### QuickWP Facade

| Method | Description |
|--------|-------------|
| `QuickWP::init($baseDir, $siteKey, $enforceAccess)` | Initialize from config files |
| `QuickWP::withConfig($array)` | Initialize with explicit config |
| `$qwp->posts()` | Get PostService instance |
| `$qwp->pages()` | Get PageService instance |
| `$qwp->cpt()` | Get CptService instance |
| `$qwp->media()` | Get MediaService instance |
| `$qwp->taxonomy()` | Get TaxonomyService instance |
| `$qwp->menus()` | Get MenuService instance |
| `$qwp->templates()` | Get TemplateService instance |
| `$qwp->getConfig()` | Get current SiteConfig |
| `$qwp->getClient()` | Get RestClient instance |

### Static Helpers

| Method | Description |
|--------|-------------|
| `QuickWP::isSuccess($result)` | Check if operation succeeded |
| `QuickWP::getError($result)` | Get error message from result |
| `QuickWP::getId($result)` | Get created/updated item ID |
| `QuickWP::getLink($result)` | Get item URL/link |

### Shortcut Methods

```php
$qwp->createPost($data);              // Create a post
$qwp->createPage($data);              // Create a page
$qwp->createCptItem($slug, $data);    // Create CPT item
$qwp->uploadMedia($fileInfo, $data);  // Upload media
$qwp->createCategory($data);          // Create category
$qwp->createTag($data);               // Create tag
```

### Service Methods

Each service follows a consistent CRUD pattern:

```php
$service->create($data);              // Create item
$service->update($id, $data);         // Update item
$service->get($id);                   // Get single item
$service->list($params);              // List items with filters
$service->delete($id, $force);        // Delete item
```

## 🌐 Multi-Site Configuration

Create `quick-sites.php` to manage multiple WordPress sites:

```php
<?php
return [
    'default_site' => 'production',
    'sites' => [
        'production' => [
            'label' => 'Production Site',
            'posts_endpoint' => 'https://example.com/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://example.com/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://example.com/wp-json/wp/v2/media',
            'wp_username'    => 'prod-admin',
            'wp_app_password' => 'xxxx xxxx xxxx xxxx',
        ],
        'staging' => [
            'label' => 'Staging Site',
            'posts_endpoint' => 'https://staging.example.com/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://staging.example.com/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://staging.example.com/wp-json/wp/v2/media',
            'wp_username'    => 'staging-admin',
            'wp_app_password' => 'yyyy yyyy yyyy yyyy',
        ],
    ],
];
```

Switch sites via URL parameter: `?site=staging`

Or programmatically:

```php
$qwp = QuickWP::init(__DIR__, siteKey: 'staging');
```

## 🔒 Access Control

Configure access protection in `quick-config.php`:

```php
// No protection (default)
'access_mode' => 'none',

// HTTP Basic Auth
'access_mode'           => 'basic',
'access_basic_user'     => 'admin',
'access_basic_password' => 'secret-password',

// Token in URL
'access_mode'   => 'token',
'access_token'  => 'my-secret-token',
// Access via: ?token=my-secret-token
```

## ⚙️ Configuration Reference

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `posts_endpoint` | string | `''` | WordPress posts REST endpoint |
| `pages_endpoint` | string | `''` | WordPress pages REST endpoint |
| `media_endpoint` | string | `''` | WordPress media REST endpoint |
| `categories_endpoint` | string | derived | Categories endpoint (auto-derived if empty) |
| `tags_endpoint` | string | derived | Tags endpoint (auto-derived if empty) |
| `wp_username` | string | `''` | WordPress username |
| `wp_app_password` | string | `''` | WordPress Application Password |
| `verify_ssl` | bool | `true` | Verify SSL certificates |
| `debug_http` | bool | `false` | Show cURL debug info |
| `show_auth_form` | bool | `true` | Show credentials in web forms |
| `access_mode` | string | `'none'` | Access control: `none`, `basic`, or `token` |
| `access_basic_user` | string | `''` | HTTP Basic Auth username |
| `access_basic_password` | string | `''` | HTTP Basic Auth password |
| `access_token` | string | `''` | URL token for token auth |
| `cpt_default_slug` | string | `'post'` | Default custom post type slug |
| `page_templates` | array | `[]` | Available page templates |
| `post_templates` | array | `[]` | Available post templates |

## 🔧 Response Format

All service methods return a standardized response array:

```php
[
    'ok'         => true,              // Success flag
    'http_code'  => 201,               // HTTP status code
    'json'       => [...],             // Decoded JSON response
    'raw_body'   => '...',             // Raw response body
    'curl_error' => null,              // cURL error message (if any)
    'info'       => [...],             // cURL info array
    'headers'    => [...],             // Response headers
]
```

## 🛡️ Security Best Practices

1. **Never commit credentials** – Keep `quick-config.php` and `quick-sites.php` out of version control
2. **Use Application Passwords** – Don't use your WordPress login password
3. **Enable SSL verification** – Keep `verify_ssl => true` in production
4. **Protect the tools folder** – Use `access_mode`, HTTP auth, or IP restrictions
5. **Use minimal permissions** – Create WordPress users with only the capabilities needed
6. **Revoke leaked passwords** – If credentials are exposed, revoke them immediately in WordPress

## 📋 Requirements

- PHP 7.4 or higher
- cURL extension
- WordPress 5.6+ (for Application Passwords)
- WordPress REST API enabled

## 🧪 Development

```bash
# Run tests
composer test

# Static analysis
composer analyse
```

## 📄 License

MIT License – see [LICENSE](LICENSE) for details.
