<?php
/**
 * QuickWP v2 - Main Entry Point
 * 
 * Modern class-based front controller for WordPress Quick Tools.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QuickWP\QuickWP;
use QuickWP\Config\ConfigLoader;
use QuickWP\Http\AccessControl;

// Initialize config loader
$baseDir = dirname(__DIR__);
$loader = new ConfigLoader($baseDir);
$siteKey = $loader->resolveSiteKey();
$config = $loader->createSiteConfig($siteKey);

// Enforce access control
$access = new AccessControl($config);
$access->enforce();

// Get available sites
$sites = $loader->getSites();
$siteLabel = $loader->getSiteLabel($siteKey);
$siteSuffix = $siteKey !== '' ? '?site=' . urlencode($siteKey) : '';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QuickWP - WordPress Quick Tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --primary: #0073aa;
            --primary-hover: #006298;
            --bg: #f5f5f5;
            --card-bg: #fff;
            --text: #333;
            --text-muted: #666;
            --border: #ddd;
            --success: #46b450;
            --radius: 8px;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: var(--bg); 
            color: var(--text);
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            padding: 24px 26px; 
            border-radius: var(--radius); 
            box-shadow: 0 2px 6px rgba(0,0,0,0.08); 
        }
        h1 { 
            font-size: 1.5rem; 
            margin: 0 0 8px; 
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: var(--text-muted);
            margin: 0 0 20px;
        }
        .version-badge {
            display: inline-block;
            background: var(--success);
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            vertical-align: middle;
            margin-left: 8px;
        }
        .site-select { 
            text-align: center;
            margin-bottom: 20px; 
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .site-select form { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }
        .site-select select { 
            padding: 6px 10px; 
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.95rem;
        }
        .actions { 
            display: flex; 
            flex-direction: column; 
            gap: 12px; 
        }
        a.button { 
            display: block; 
            padding: 14px 16px; 
            border-radius: var(--radius); 
            text-decoration: none; 
            color: #fff; 
            background: var(--primary); 
            font-weight: 600; 
            text-align: center;
            transition: background 0.2s;
        }
        a.button:hover { 
            background: var(--primary-hover); 
        }
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 500px) {
            .button-group {
                grid-template-columns: 1fr;
            }
        }
        small { 
            display: block; 
            margin-top: 20px; 
            color: var(--text-muted); 
            text-align: center;
            font-size: 0.85rem;
        }
        .footer-links {
            margin-top: 16px;
            text-align: center;
            font-size: 0.85rem;
        }
        .footer-links a {
            color: var(--primary);
            text-decoration: none;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>QuickWP <span class="version-badge">v2</span></h1>
    <p class="subtitle">WordPress REST API Tools</p>

    <?php if (!empty($sites)): ?>
        <div class="site-select">
            <form method="get">
                <label for="site">Site:</label>
                <select id="site" name="site" onchange="this.form.submit()">
                    <?php foreach ($sites as $key => $site): ?>
                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= ($key === $siteKey) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($site['label'] ?? $key, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">Switch</button></noscript>
            </form>
        </div>
    <?php endif; ?>

    <div class="actions">
        <div class="button-group">
            <a class="button" href="quick-post.php<?= $siteSuffix ?>">Create Post</a>
            <a class="button" href="quick-page.php<?= $siteSuffix ?>">Create Page</a>
        </div>
        <a class="button" href="quick-media.php<?= $siteSuffix ?>">Upload Media</a>
        <a class="button" href="quick-cpt.php<?= $siteSuffix ?>">Custom Post Type</a>
        <a class="button" href="quick-taxonomy.php<?= $siteSuffix ?>">Categories / Tags</a>
        <div class="button-group">
            <a class="button" href="quick-edit.php<?= $siteSuffix ?>">Quick Edit</a>
            <a class="button" href="quick-list.php<?= $siteSuffix ?>">Browse Content</a>
        </div>
    </div>

    <small>Configure in <code>quick-config.php</code> and <code>quick-sites.php</code></small>
    
    <div class="footer-links">
        <a href="../README.md">Documentation</a>
    </div>
</div>
</body>
</html>
