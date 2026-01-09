# QuickWP Documentation

Detailed documentation for the QuickWP WordPress REST API Toolkit.

## Table of Contents

- [Getting Started](GETTING_STARTED.md) – Installation and initial setup
- [Configuration](CONFIGURATION.md) – All configuration options explained
- [API Reference](API_REFERENCE.md) – Complete class and method reference
- [Web Interface](WEB_INTERFACE.md) – Using the web-based tools
- [Examples](EXAMPLES.md) – Code examples and use cases
- [Troubleshooting](TROUBLESHOOTING.md) – Common issues and solutions

## Quick Links

### For Users

If you just want to use the web interface:

1. Run `composer install`
2. Copy `quick-config-example.php` to `quick-config.php`
3. Edit `quick-config.php` with your WordPress site details
4. Open `public/index.php` in your browser

### For Developers

If you want to use QuickWP programmatically:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use QuickWP\QuickWP;

$qwp = QuickWP::init(__DIR__);

// Create content
$qwp->posts()->create(['title' => 'Hello', 'content' => 'World']);
$qwp->pages()->create(['title' => 'About', 'content' => 'About us']);
$qwp->media()->upload($_FILES['image']);
$qwp->taxonomy()->createCategory(['name' => 'News']);
```

## Architecture Overview

QuickWP follows a clean service-oriented architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                      QuickWP (Facade)                       │
├─────────────────────────────────────────────────────────────┤
│  Provides a simple, unified API for all WordPress operations│
└───────────────────────────┬─────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  PostService  │   │  PageService  │   │ MediaService  │
│  CptService   │   │TaxonomyService│   │  MenuService  │
└───────┬───────┘   └───────┬───────┘   └───────┬───────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
                            ▼
                    ┌───────────────┐
                    │  RestClient   │
                    │   (cURL)      │
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │  WordPress    │
                    │   REST API    │
                    └───────────────┘
```

## Key Classes

| Class | Purpose |
|-------|---------|
| `QuickWP` | Main facade – entry point for all operations |
| `Bootstrap` | Factory class for creating services with dependencies |
| `SiteConfig` | Holds site configuration (endpoints, credentials) |
| `ConfigLoader` | Loads and merges configuration from files |
| `RestClient` | cURL-based HTTP client for REST API calls |
| `AccessControl` | Handles access protection (basic auth, token) |
| `PostService` | CRUD operations for posts |
| `PageService` | CRUD operations for pages |
| `CptService` | CRUD operations for custom post types |
| `MediaService` | Media upload and management |
| `TaxonomyService` | Categories, tags, and custom taxonomies |
| `MenuService` | WordPress menu operations |
| `TemplateService` | Fetch available page/post templates |

## Design Principles

1. **Dependency Injection** – Services receive their dependencies via constructor
2. **Immutable Config** – Configuration objects are read-only after creation
3. **Standardized Responses** – All operations return consistent response arrays
4. **Lazy Loading** – Services are created on first access
5. **Single Responsibility** – Each service handles one type of content

## Contributing

See the main [README.md](../README.md) for development setup instructions.
