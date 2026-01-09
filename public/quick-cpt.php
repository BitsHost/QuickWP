<?php
/**
 * QuickWP v2 - Custom Post Type Controller
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QuickWP\QuickWP;
use QuickWP\Config\ConfigLoader;
use QuickWP\Http\AccessControl;

// Initialize
$baseDir = dirname(__DIR__);
$loader = new ConfigLoader($baseDir);
$siteKey = $loader->resolveSiteKey();
$config = $loader->createSiteConfig($siteKey);

// Enforce access
$access = new AccessControl($config);
$access->enforce();

$siteSuffix = $siteKey !== '' ? '?site=' . urlencode($siteKey) : '';

// Initialize QuickWP
$qwp = new QuickWP($config);

$message = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cptSlug = trim($_POST['cpt_slug'] ?? $config->getDefaultCptSlug());
    $customEndpoint = trim($_POST['cpt_endpoint'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $slug = trim($_POST['slug'] ?? '');
    $categories = trim($_POST['categories'] ?? '');
    $tags = trim($_POST['tags'] ?? '');

    if ($cptSlug === '') {
        $error = 'CPT slug is required.';
    } elseif ($title === '' || $content === '') {
        $error = 'Title and Content are required.';
    } else {
        $data = [
            'title' => $title,
            'content' => $content,
            'status' => $status,
        ];

        if ($excerpt !== '') $data['excerpt'] = $excerpt;
        if ($slug !== '') $data['slug'] = $slug;
        if ($categories !== '') $data['categories'] = $categories;
        if ($tags !== '') $data['tags'] = $tags;

        $result = $qwp->cpt()->create(
            $cptSlug,
            $data,
            $customEndpoint !== '' ? $customEndpoint : null
        );

        if (QuickWP::isSuccess($result)) {
            $itemId = QuickWP::getId($result);
            $itemLink = QuickWP::getLink($result);

            if ($itemLink) {
                $message = ucfirst($cptSlug) . ' created: <a href="' . htmlspecialchars($itemLink, ENT_QUOTES, 'UTF-8') . '" target="_blank">View</a>';
            } elseif ($itemId) {
                $message = ucfirst($cptSlug) . ' created. ID: ' . $itemId;
            } else {
                $message = ucfirst($cptSlug) . ' created successfully.';
            }
        } else {
            $error = 'Error: ' . QuickWP::getError($result);
        }
    }
}

// Navigation links
$navLinks = [
    ['url' => 'index.php', 'label' => 'Home'],
    ['url' => 'quick-post.php', 'label' => 'Post'],
    ['url' => 'quick-page.php', 'label' => 'Page'],
    ['url' => 'quick-media.php', 'label' => 'Media'],
    ['url' => 'quick-cpt.php', 'label' => 'CPT', 'active' => true],
    ['url' => 'quick-taxonomy.php', 'label' => 'Taxonomy'],
    ['url' => 'quick-list.php', 'label' => 'Browse'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Custom Post Type - QuickWP</title>
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
            --error: #dc3232;
            --radius: 8px;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: var(--bg); 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            padding: 20px 24px; 
            border-radius: var(--radius); 
            box-shadow: 0 2px 6px rgba(0,0,0,0.08); 
        }
        h1 { font-size: 1.4rem; margin: 0 0 16px; }
        .nav { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 6px; 
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .nav a { 
            padding: 6px 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            color: var(--primary); 
            background: #e5f2fa; 
            font-size: 0.9rem; 
        }
        .nav a:hover { background: #d0e7f6; }
        .nav a.active { background: var(--primary); color: #fff; }
        
        label { display: block; margin: 14px 0 4px; font-weight: 600; }
        input[type="text"], textarea, select { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 4px; 
            border: 1px solid var(--border); 
            font-size: 0.95rem; 
        }
        textarea { min-height: 180px; resize: vertical; font-family: inherit; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .row { grid-template-columns: 1fr; } }
        
        button { 
            margin-top: 20px; 
            padding: 12px 24px; 
            background: var(--primary); 
            color: #fff; 
            border: none; 
            border-radius: var(--radius); 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
        }
        button:hover { background: var(--primary-hover); }
        
        .message { 
            padding: 12px 16px; 
            border-radius: 4px; 
            margin-bottom: 16px; 
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .message a { color: inherit; }
        
        .cpt-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        .hint { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }
        
        /* Taxonomy Picker Styles */
        .picker-container { 
            margin: 4px 0 8px; 
            max-height: 180px; 
            overflow: auto; 
            border: 1px solid var(--border); 
            padding: 8px; 
            border-radius: 4px; 
            background: #fafafa; 
            font-size: 0.9rem; 
        }
        .picker-chip { 
            display: inline-block; 
            margin: 3px 6px 3px 0; 
            white-space: nowrap; 
            cursor: pointer;
        }
        .picker-chip:hover { background: #e5f2fa; border-radius: 3px; padding: 2px 4px; margin: 1px 4px 1px -2px; }
        .picker-chip input { margin-right: 4px; }
        .picker-id { color: var(--text-muted); font-size: 0.8rem; }
        .picker-empty { color: var(--text-muted); font-size: 0.85rem; }
    </style>
    <script src="assets/taxonomy-picker.js"></script>
</head>
<body>
<div class="container">
    <nav class="nav">
        <?php foreach ($navLinks as $link): ?>
            <a href="<?= $link['url'] . $siteSuffix ?>" class="<?= !empty($link['active']) ? 'active' : '' ?>">
                <?= htmlspecialchars($link['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <h1>Create Custom Post Type Item</h1>

    <?php if ($message): ?>
        <div class="message success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if ($siteKey !== ''): ?>
            <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
        <?php endif; ?>

        <div class="cpt-section" style="margin-top: 0; padding-top: 0; border-top: none;">
            <div class="row">
                <div>
                    <label for="cpt_slug">CPT Slug *</label>
                    <input type="text" id="cpt_slug" name="cpt_slug" required 
                           placeholder="e.g. product, portfolio, event"
                           value="<?= htmlspecialchars($_POST['cpt_slug'] ?? $config->getDefaultCptSlug()) ?>">
                    <p class="hint">The REST API slug for the custom post type</p>
                </div>
                <div>
                    <label for="cpt_endpoint">Custom Endpoint (optional)</label>
                    <input type="text" id="cpt_endpoint" name="cpt_endpoint" 
                           placeholder="Full URL if different from derived"
                           value="<?= htmlspecialchars($_POST['cpt_endpoint'] ?? '') ?>">
                    <p class="hint">Override the auto-derived endpoint</p>
                </div>
            </div>
        </div>

        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

        <label for="content">Content *</label>
        <textarea id="content" name="content" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>

        <label for="excerpt">Excerpt</label>
        <textarea id="excerpt" name="excerpt" style="min-height: 80px;"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>

        <div class="row">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="publish" <?= ($_POST['status'] ?? '') === 'publish' ? 'selected' : '' ?>>Publish</option>
                    <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="private" <?= ($_POST['status'] ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                </select>
            </div>
            <div>
                <label for="slug">Slug (optional)</label>
                <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div>
                <label for="categories">Categories</label>
                <div id="categories-picker" class="picker-container">
                    <small class="picker-empty">Loading categories...</small>
                </div>
                <input type="text" id="categories" name="categories" placeholder="e.g. 1, 5, 12" value="<?= htmlspecialchars($_POST['categories'] ?? '') ?>">
                <p class="hint">If CPT supports categories</p>
            </div>
            <div>
                <label for="tags">Tags</label>
                <div id="tags-picker" class="picker-container">
                    <small class="picker-empty">Loading tags...</small>
                </div>
                <input type="text" id="tags" name="tags" placeholder="e.g. 3, 7" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
                <p class="hint">If CPT supports tags</p>
            </div>
        </div>

        <button type="submit">Create CPT Item</button>
    </form>
</div>

<script>
TaxonomyPicker.init({
    categoriesEndpoint: <?= json_encode($config->getCategoriesEndpoint()) ?>,
    tagsEndpoint: <?= json_encode($config->getTagsEndpoint()) ?>
});
</script>
</body>
</html>
