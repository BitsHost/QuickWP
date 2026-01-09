# Getting Started with QuickWP

This guide walks you through setting up QuickWP from scratch.

## Prerequisites

- **PHP 7.4+** with cURL extension
- **Composer** for dependency management
- **WordPress site** with REST API enabled (WordPress 4.7+)
- **Application Password** (WordPress 5.6+) or Basic Auth plugin

## Installation

### 1. Clone or Download

```bash
git clone https://github.com/your-repo/QuickWP.git
cd QuickWP
```

### 2. Install Dependencies

```bash
composer install
```

This creates the `vendor/` directory with the PSR-4 autoloader.

### 3. Create Configuration File

Copy the example configuration:

```bash
cp quick-config-example.php quick-config.php
```

Or create a new `quick-config.php`:

```php
<?php
return [
    // Your WordPress REST API endpoints
    'posts_endpoint' => 'https://your-site.com/wp-json/wp/v2/posts',
    'pages_endpoint' => 'https://your-site.com/wp-json/wp/v2/pages',
    'media_endpoint' => 'https://your-site.com/wp-json/wp/v2/media',
    
    // Authentication
    'wp_username'     => 'your-username',
    'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
    
    // Optional settings
    'verify_ssl'     => true,
    'show_auth_form' => true,
    'debug_http'     => false,
];
```

## Creating an Application Password

WordPress 5.6+ includes built-in Application Passwords:

1. Log in to your WordPress admin dashboard
2. Navigate to **Users → Profile**
3. Scroll down to **Application Passwords**
4. Enter a name for this password (e.g., "QuickWP Tools")
5. Click **Add New Application Password**
6. **Important:** Copy the generated password immediately – you won't see it again!

The password will look like: `xxxx xxxx xxxx xxxx xxxx xxxx`

### For Older WordPress Versions

If you're using WordPress < 5.6, install the [Application Passwords plugin](https://wordpress.org/plugins/application-passwords/).

## Determining Your Endpoints

Your REST API endpoints follow this pattern:

```
https://your-site.com/wp-json/wp/v2/{resource}
```

Common resources:
- `posts` – Blog posts
- `pages` – Static pages
- `media` – Media library
- `categories` – Post categories
- `tags` – Post tags

### WordPress in a Subdirectory

If WordPress is installed in a subdirectory:

```php
'posts_endpoint' => 'https://example.com/blog/wp-json/wp/v2/posts',
```

### Using ?rest_route= Style

If pretty permalinks are disabled:

```php
'posts_endpoint' => 'https://example.com/?rest_route=/wp/v2/posts',
```

## Verify Your Setup

### Test Connection via Web Interface

1. Start a local PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```

2. Open `http://localhost:8000` in your browser

3. Try creating a draft post

### Test Connection via Code

Create a test script `test.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use QuickWP\QuickWP;

$qwp = QuickWP::init(__DIR__, enforceAccess: false);

// Test by listing posts
$result = $qwp->posts()->list(['per_page' => 1]);

if (QuickWP::isSuccess($result)) {
    echo "✓ Connection successful!\n";
    echo "Posts found: " . count($result['json']) . "\n";
} else {
    echo "✗ Connection failed!\n";
    echo "Error: " . QuickWP::getError($result) . "\n";
}
```

Run it:

```bash
php test.php
```

## Common Setup Issues

### SSL Certificate Errors

If you see SSL errors during development:

```php
'verify_ssl' => false,  // Only for local development!
```

**Warning:** Never disable SSL verification in production.

### 401 Unauthorized

- Check your username and application password
- Ensure the user has appropriate capabilities
- Verify the Application Password was copied correctly (with spaces)

### 404 Not Found

- Check your endpoint URLs
- Ensure REST API is enabled (not blocked by plugins)
- Try accessing `https://your-site.com/wp-json/` directly

### 403 Forbidden

- The user may lack required capabilities
- Check if any security plugin is blocking REST API access
- Verify CORS settings if accessing from a different domain

## Next Steps

- [Configuration](CONFIGURATION.md) – Learn all configuration options
- [API Reference](API_REFERENCE.md) – Explore the full API
- [Examples](EXAMPLES.md) – See practical code examples
- [Web Interface](WEB_INTERFACE.md) – Use the built-in web tools
