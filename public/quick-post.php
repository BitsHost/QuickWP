<?php
/**
 * QuickWP v2 - Create Post Controller
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

// Get sites for navigation
$sites = $loader->getSites();
$siteSuffix = $siteKey !== '' ? '?site=' . urlencode($siteKey) : '';

// Initialize QuickWP (without enforcing access again)
$qwp = new QuickWP($config);

$message = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $slug = trim($_POST['slug'] ?? '');
    $categories = trim($_POST['categories'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $featuredMedia = (int)($_POST['featured_media'] ?? 0);
    
    // Handle template (dropdown + custom input)
    $template = trim($_POST['template'] ?? '');
    if ($template === '__custom__') {
        $template = trim($_POST['template_custom'] ?? '');
    }

    if ($title === '' || $content === '') {
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
        if ($featuredMedia > 0) $data['featured_media'] = $featuredMedia;
        if ($template !== '') $data['template'] = $template;

        $result = $qwp->posts()->create($data);

        if (QuickWP::isSuccess($result)) {
            $postId = QuickWP::getId($result);
            $postLink = QuickWP::getLink($result);

            if ($postLink) {
                $message = 'Post created: <a href="' . htmlspecialchars($postLink, ENT_QUOTES, 'UTF-8') . '" target="_blank">View Post</a>';
            } elseif ($postId) {
                $message = 'Post created. ID: ' . $postId;
            } else {
                $message = 'Post created successfully.';
            }
        } else {
            $error = 'Error: ' . QuickWP::getError($result);
        }
    }
}

// Navigation links
$navLinks = [
    ['url' => 'index.php', 'label' => 'Home'],
    ['url' => 'quick-post.php', 'label' => 'Post', 'active' => true],
    ['url' => 'quick-page.php', 'label' => 'Page'],
    ['url' => 'quick-media.php', 'label' => 'Media'],
    ['url' => 'quick-cpt.php', 'label' => 'CPT'],
    ['url' => 'quick-taxonomy.php', 'label' => 'Taxonomy'],
    ['url' => 'quick-list.php', 'label' => 'Browse'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Post - QuickWP</title>
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
        .picker-select { 
            width: 100%; 
            padding: 8px; 
            border-radius: 4px; 
            border: 1px solid var(--border); 
            font-size: 0.9rem; 
            background: #fff; 
        }
        .picker-info { margin-top: 6px; }
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

    <h1>Create Post</h1>

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
                <p class="hint">Select above or enter comma-separated IDs</p>
            </div>
            <div>
                <label for="tags">Tags</label>
                <div id="tags-picker" class="picker-container">
                    <small class="picker-empty">Loading tags...</small>
                </div>
                <input type="text" id="tags" name="tags" placeholder="e.g. 3, 7" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
                <p class="hint">Select above or enter comma-separated IDs</p>
            </div>
        </div>

        <div class="row">
            <div>
                <label for="featured_media">Featured Image (Media ID)</label>
                <input type="number" id="featured_media" name="featured_media" min="0" value="<?= (int)($_POST['featured_media'] ?? 0) ?>">
                <p class="hint">Enter media ID from <a href="quick-media.php<?= $siteSuffix ?>">Media Upload</a> or leave 0</p>
            </div>
            <div>
                <label for="template">Post Template</label>
                <?php 
                // Fetch templates dynamically from WordPress (with config fallback)
                $postTemplates = $qwp->templates()->getPostTemplates();
                $selectedTemplate = $_POST['template'] ?? '';
                if (!empty($postTemplates)): ?>
                    <select id="template" name="template">
                        <?php foreach ($postTemplates as $file => $name): ?>
                            <option value="<?= htmlspecialchars($file) ?>" <?= $selectedTemplate === $file ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="__custom__" <?= ($selectedTemplate !== '' && !isset($postTemplates[$selectedTemplate])) ? 'selected' : '' ?>>Custom...</option>
                    </select>
                    <input type="text" id="template_custom" name="template_custom" placeholder="e.g. single-custom.php" 
                           value="<?= ($selectedTemplate !== '' && !isset($postTemplates[$selectedTemplate])) ? htmlspecialchars($selectedTemplate) : '' ?>"
                           style="margin-top: 6px; <?= ($selectedTemplate === '' || isset($postTemplates[$selectedTemplate])) ? 'display: none;' : '' ?>">
                    <p class="hint">Post templates require theme support (WP 4.7+)</p>
                <?php else: ?>
                    <input type="text" id="template" name="template" placeholder="e.g. single-full.php" value="<?= htmlspecialchars($selectedTemplate) ?>">
                    <p class="hint">Optional - requires theme support</p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit">Create Post</button>
    </form>
</div>

<script>
TaxonomyPicker.init({
    categoriesEndpoint: <?= json_encode($config->getCategoriesEndpoint()) ?>,
    tagsEndpoint: <?= json_encode($config->getTagsEndpoint()) ?>
});

// Template dropdown toggle
(function() {
    const templateSelect = document.getElementById('template');
    const templateCustom = document.getElementById('template_custom');
    
    if (templateSelect && templateSelect.tagName === 'SELECT' && templateCustom) {
        templateSelect.addEventListener('change', function() {
            if (this.value === '__custom__') {
                templateCustom.style.display = '';
                templateCustom.focus();
            } else {
                templateCustom.style.display = 'none';
                templateCustom.value = '';
            }
        });
    }
})();
</script>
</body>
</html>
