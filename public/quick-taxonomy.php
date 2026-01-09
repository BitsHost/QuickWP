<?php
/**
 * QuickWP v2 - Taxonomy (Categories/Tags) Controller
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
$categories = [];
$tags = [];

// Action handling
$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_category') {
        $name = trim($_POST['category_name'] ?? '');
        $slug = trim($_POST['category_slug'] ?? '');
        $description = trim($_POST['category_description'] ?? '');
        $parent = (int)($_POST['category_parent'] ?? 0);

        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            $data = ['name' => $name];
            if ($slug !== '') $data['slug'] = $slug;
            if ($description !== '') $data['description'] = $description;
            if ($parent > 0) $data['parent'] = $parent;

            $result = $qwp->taxonomy()->createCategory($data);

            if (QuickWP::isSuccess($result)) {
                $termId = QuickWP::getId($result);
                $message = 'Category created. ID: ' . $termId;
            } else {
                $error = 'Error: ' . QuickWP::getError($result);
            }
        }
    } elseif ($action === 'create_tag') {
        $name = trim($_POST['tag_name'] ?? '');
        $slug = trim($_POST['tag_slug'] ?? '');
        $description = trim($_POST['tag_description'] ?? '');

        if ($name === '') {
            $error = 'Tag name is required.';
        } else {
            $data = ['name' => $name];
            if ($slug !== '') $data['slug'] = $slug;
            if ($description !== '') $data['description'] = $description;

            $result = $qwp->taxonomy()->createTag($data);

            if (QuickWP::isSuccess($result)) {
                $termId = QuickWP::getId($result);
                $message = 'Tag created. ID: ' . $termId;
            } else {
                $error = 'Error: ' . QuickWP::getError($result);
            }
        }
    }
}

// Fetch existing categories and tags for reference
$categoriesResult = $qwp->taxonomy()->listCategories(['per_page' => 100]);
if (QuickWP::isSuccess($categoriesResult)) {
    $categories = $categoriesResult['json'] ?? [];
}

$tagsResult = $qwp->taxonomy()->listTags(['per_page' => 100]);
if (QuickWP::isSuccess($tagsResult)) {
    $tags = $tagsResult['json'] ?? [];
}

// Navigation links
$navLinks = [
    ['url' => 'index.php', 'label' => 'Home'],
    ['url' => 'quick-post.php', 'label' => 'Post'],
    ['url' => 'quick-page.php', 'label' => 'Page'],
    ['url' => 'quick-media.php', 'label' => 'Media'],
    ['url' => 'quick-cpt.php', 'label' => 'CPT'],
    ['url' => 'quick-taxonomy.php', 'label' => 'Taxonomy', 'active' => true],
    ['url' => 'quick-list.php', 'label' => 'Browse'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Taxonomy - QuickWP</title>
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
            max-width: 900px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            padding: 20px 24px; 
            border-radius: var(--radius); 
            box-shadow: 0 2px 6px rgba(0,0,0,0.08); 
        }
        h1 { font-size: 1.4rem; margin: 0 0 16px; }
        h2 { font-size: 1.1rem; margin: 20px 0 12px; color: var(--text); }
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
        
        label { display: block; margin: 10px 0 4px; font-weight: 600; font-size: 0.9rem; }
        input[type="text"], input[type="number"], textarea { 
            width: 100%; 
            padding: 8px 10px; 
            border-radius: 4px; 
            border: 1px solid var(--border); 
            font-size: 0.9rem; 
        }
        textarea { min-height: 60px; resize: vertical; font-family: inherit; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 700px) { .grid-2 { grid-template-columns: 1fr; } }
        
        .form-card {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
        }
        
        button { 
            margin-top: 12px; 
            padding: 10px 20px; 
            background: var(--primary); 
            color: #fff; 
            border: none; 
            border-radius: 4px; 
            font-size: 0.9rem; 
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
        
        .list-section {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .term-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .term-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #e5f2fa;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .term-item .id {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .row { grid-template-columns: 1fr; } }
    </style>
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

    <h1>Manage Taxonomies</h1>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Create Category Form -->
        <div class="form-card">
            <h2>Create Category</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_category">
                <?php if ($siteKey !== ''): ?>
                    <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
                <?php endif; ?>

                <label for="category_name">Name *</label>
                <input type="text" id="category_name" name="category_name" required>

                <label for="category_slug">Slug</label>
                <input type="text" id="category_slug" name="category_slug">

                <label for="category_description">Description</label>
                <textarea id="category_description" name="category_description"></textarea>

                <label for="category_parent">Parent ID</label>
                <input type="number" id="category_parent" name="category_parent" min="0" value="0">

                <button type="submit">Create Category</button>
            </form>
        </div>

        <!-- Create Tag Form -->
        <div class="form-card">
            <h2>Create Tag</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_tag">
                <?php if ($siteKey !== ''): ?>
                    <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
                <?php endif; ?>

                <label for="tag_name">Name *</label>
                <input type="text" id="tag_name" name="tag_name" required>

                <label for="tag_slug">Slug</label>
                <input type="text" id="tag_slug" name="tag_slug">

                <label for="tag_description">Description</label>
                <textarea id="tag_description" name="tag_description"></textarea>

                <button type="submit">Create Tag</button>
            </form>
        </div>
    </div>

    <!-- Existing Terms -->
    <div class="list-section">
        <h2>Existing Categories</h2>
        <?php if (!empty($categories)): ?>
            <div class="term-list">
                <?php foreach ($categories as $cat): ?>
                    <span class="term-item">
                        <?= htmlspecialchars($cat['name'] ?? 'Untitled') ?>
                        <span class="id">(ID: <?= (int)($cat['id'] ?? 0) ?>)</span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No categories found or unable to fetch.</p>
        <?php endif; ?>

        <h2>Existing Tags</h2>
        <?php if (!empty($tags)): ?>
            <div class="term-list">
                <?php foreach ($tags as $tag): ?>
                    <span class="term-item">
                        <?= htmlspecialchars($tag['name'] ?? 'Untitled') ?>
                        <span class="id">(ID: <?= (int)($tag['id'] ?? 0) ?>)</span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No tags found or unable to fetch.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
