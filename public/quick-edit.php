<?php
/**
 * QuickWP v2 - Quick Edit by ID Controller
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
$loadedItem = null;

// Handle actions
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$itemType = $_POST['item_type'] ?? ($_GET['item_type'] ?? 'post');
$cptSlug = $_POST['cpt_slug'] ?? ($_GET['cpt_slug'] ?? '');
$itemId = (int)($_POST['item_id'] ?? ($_GET['item_id'] ?? 0));

// Helper to get appropriate service
function getServiceForType(QuickWP $qwp, string $itemType, string $cptSlug = '') {
    if ($itemType === 'cpt' && $cptSlug !== '') {
        return ['service' => $qwp->cpt(), 'slug' => $cptSlug];
    } elseif ($itemType === 'page') {
        return ['service' => $qwp->pages(), 'slug' => null];
    }
    return ['service' => $qwp->posts(), 'slug' => null];
}

// Load item for editing
if ($action === 'load' && $itemId > 0) {
    $svcInfo = getServiceForType($qwp, $itemType, $cptSlug);
    
    if ($svcInfo['slug'] !== null) {
        $result = $svcInfo['service']->get($svcInfo['slug'], $itemId);
    } else {
        $result = $svcInfo['service']->get($itemId);
    }

    if (QuickWP::isSuccess($result)) {
        $loadedItem = $result['json'];
    } else {
        $error = 'Could not load item: ' . QuickWP::getError($result);
    }
}

// Save changes
if ($action === 'save' && $itemId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];

    if (isset($_POST['title'])) $data['title'] = trim($_POST['title']);
    if (isset($_POST['content'])) $data['content'] = trim($_POST['content']);
    if (isset($_POST['excerpt'])) $data['excerpt'] = trim($_POST['excerpt']);
    if (isset($_POST['status'])) $data['status'] = $_POST['status'];
    if (isset($_POST['slug']) && trim($_POST['slug']) !== '') $data['slug'] = trim($_POST['slug']);
    
    // Featured image (all types)
    if (isset($_POST['featured_media'])) {
        $data['featured_media'] = (int)$_POST['featured_media'];
    }
    
    // Template (posts and pages)
    if (isset($_POST['template'])) {
        $template = trim($_POST['template']);
        if ($template === '__custom__') {
            $template = trim($_POST['template_custom'] ?? '');
        }
        $data['template'] = $template;
    }
    
    // Post and CPT: categories & tags
    if ($itemType === 'post' || $itemType === 'cpt') {
        if (isset($_POST['categories']) && trim($_POST['categories']) !== '') {
            $data['categories'] = trim($_POST['categories']);
        }
        if (isset($_POST['tags']) && trim($_POST['tags']) !== '') {
            $data['tags'] = trim($_POST['tags']);
        }
    }
    
    // Page-specific: parent & menu_order
    if ($itemType === 'page') {
        if (isset($_POST['parent'])) {
            $data['parent'] = (int)$_POST['parent'];
        }
        if (isset($_POST['menu_order'])) {
            $data['menu_order'] = (int)$_POST['menu_order'];
        }
    }

    $svcInfo = getServiceForType($qwp, $itemType, $cptSlug);
    
    if ($svcInfo['slug'] !== null) {
        $result = $svcInfo['service']->update($svcInfo['slug'], $itemId, $data);
    } else {
        $result = $svcInfo['service']->update($itemId, $data);
    }

    if (QuickWP::isSuccess($result)) {
        $message = ucfirst($itemType === 'cpt' ? $cptSlug : $itemType) . ' updated successfully.';
        $loadedItem = $result['json'];
    } else {
        $error = 'Update failed: ' . QuickWP::getError($result);
        // Reload the item
        if ($svcInfo['slug'] !== null) {
            $loadResult = $svcInfo['service']->get($svcInfo['slug'], $itemId);
        } else {
            $loadResult = $svcInfo['service']->get($itemId);
        }
        if (QuickWP::isSuccess($loadResult)) {
            $loadedItem = $loadResult['json'];
        }
    }
}

// Delete item
if ($action === 'delete' && $itemId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $svcInfo = getServiceForType($qwp, $itemType, $cptSlug);
    $force = isset($_POST['force_delete']) && $_POST['force_delete'] === '1';
    
    if ($svcInfo['slug'] !== null) {
        $result = $svcInfo['service']->delete($svcInfo['slug'], $itemId, $force);
    } else {
        $result = $svcInfo['service']->delete($itemId, $force);
    }

    if (QuickWP::isSuccess($result)) {
        $message = ucfirst($itemType === 'cpt' ? $cptSlug : $itemType) . ' deleted successfully.' . ($force ? ' (permanently)' : ' (trashed)');
        $loadedItem = null; // Clear loaded item
        $itemId = 0;
    } else {
        $error = 'Delete failed: ' . QuickWP::getError($result);
        // Reload the item if it still exists
        if ($svcInfo['slug'] !== null) {
            $loadResult = $svcInfo['service']->get($svcInfo['slug'], $itemId);
        } else {
            $loadResult = $svcInfo['service']->get($itemId);
        }
        if (QuickWP::isSuccess($loadResult)) {
            $loadedItem = $loadResult['json'];
        }
    }
}

// Navigation links
$navLinks = [
    ['url' => 'index.php', 'label' => 'Home'],
    ['url' => 'quick-post.php', 'label' => 'Post'],
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
    <title>Quick Edit - QuickWP</title>
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
        input[type="text"], input[type="number"], textarea, select { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 4px; 
            border: 1px solid var(--border); 
            font-size: 0.95rem; 
        }
        textarea { min-height: 180px; resize: vertical; font-family: inherit; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .row-4 { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
        @media (max-width: 700px) { 
            .row, .row-3, .row-4 { grid-template-columns: 1fr; } 
        }
        
        button { 
            margin-top: 16px; 
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
        button.secondary {
            background: #666;
        }
        button.secondary:hover {
            background: #555;
        }
        
        .button-row {
            margin-top: 20px;
        }
        
        .delete-form {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #f5c6cb;
        }
        .delete-section {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .checkbox-label input { margin: 0; }
        .btn-delete {
            background: #dc3545;
            margin-top: 0;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        
        .message { 
            padding: 12px 16px; 
            border-radius: 4px; 
            margin-bottom: 16px; 
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .load-section {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .hint { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }
        
        /* Item Metadata Display */
        .item-meta {
            background: #f0f7fc;
            border: 1px solid #c3ddf0;
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-bottom: 20px;
        }
        .item-meta h3 { margin: 0 0 10px; font-size: 1rem; color: var(--primary); }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px 16px;
            font-size: 0.9rem;
        }
        .meta-item { display: flex; flex-wrap: wrap; gap: 4px; }
        .meta-label { font-weight: 600; color: var(--text); }
        .meta-value { color: var(--text-muted); word-break: break-word; }
        .meta-value a { color: var(--primary); }
        
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

    <h1>Quick Edit by ID</h1>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Load Item Form -->
    <div class="load-section">
        <form method="get">
            <input type="hidden" name="action" value="load">
            <?php if ($siteKey !== ''): ?>
                <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
            <?php endif; ?>

            <div class="row-4">
                <div>
                    <label for="item_type">Type</label>
                    <select id="item_type" name="item_type" onchange="toggleCptField()">
                        <option value="post" <?= $itemType === 'post' ? 'selected' : '' ?>>Post</option>
                        <option value="page" <?= $itemType === 'page' ? 'selected' : '' ?>>Page</option>
                        <option value="cpt" <?= $itemType === 'cpt' ? 'selected' : '' ?>>Custom Post Type</option>
                    </select>
                </div>
                <div id="cpt-slug-field" style="<?= $itemType === 'cpt' ? '' : 'display: none;' ?>">
                    <label for="cpt_slug">CPT Slug</label>
                    <input type="text" id="cpt_slug" name="cpt_slug" value="<?= htmlspecialchars($cptSlug) ?>" placeholder="e.g. product">
                </div>
                <div>
                    <label for="item_id">Item ID</label>
                    <input type="number" id="item_id" name="item_id" min="1" value="<?= $itemId > 0 ? $itemId : '' ?>" required>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="secondary" style="margin-top: 0;">Load Item</button>
                </div>
            </div>
            <p class="hint" id="cpt-hint" style="<?= $itemType === 'cpt' ? '' : 'display: none;' ?>">Enter the CPT slug (e.g., <code>product</code>, <code>portfolio</code>, <code>event</code>)</p>
        </form>
    </div>

    <?php if ($loadedItem): ?>
        <!-- Item Metadata -->
        <div class="item-meta">
            <h3>📋 Item Info</h3>
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">ID:</span>
                    <span class="meta-value"><?= $loadedItem['id'] ?? 'N/A' ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Type:</span>
                    <span class="meta-value"><?= htmlspecialchars($loadedItem['type'] ?? $itemType) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Status:</span>
                    <span class="meta-value"><?= htmlspecialchars($loadedItem['status'] ?? 'unknown') ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Slug:</span>
                    <span class="meta-value"><?= htmlspecialchars($loadedItem['slug'] ?? '') ?></span>
                </div>
                <?php if (!empty($loadedItem['link'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Link:</span>
                    <span class="meta-value"><a href="<?= htmlspecialchars($loadedItem['link']) ?>" target="_blank" rel="noopener">View &rarr;</a></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['categories'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Category IDs:</span>
                    <span class="meta-value"><?= implode(', ', (array)$loadedItem['categories']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['tags'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Tag IDs:</span>
                    <span class="meta-value"><?= implode(', ', (array)$loadedItem['tags']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($loadedItem['parent']) && $loadedItem['parent'] > 0): ?>
                <div class="meta-item">
                    <span class="meta-label">Parent ID:</span>
                    <span class="meta-value"><?= (int)$loadedItem['parent'] ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['featured_media'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Featured Image ID:</span>
                    <span class="meta-value"><?= (int)$loadedItem['featured_media'] ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['author'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Author ID:</span>
                    <span class="meta-value"><?= (int)$loadedItem['author'] ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['date'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Created:</span>
                    <span class="meta-value"><?= htmlspecialchars($loadedItem['date']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($loadedItem['modified'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Modified:</span>
                    <span class="meta-value"><?= htmlspecialchars($loadedItem['modified']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Form -->
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="item_type" value="<?= htmlspecialchars($itemType) ?>">
            <input type="hidden" name="cpt_slug" value="<?= htmlspecialchars($cptSlug) ?>">
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <?php if ($siteKey !== ''): ?>
                <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
            <?php endif; ?>

            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($loadedItem['title']['rendered'] ?? $loadedItem['title']['raw'] ?? '') ?>">

            <label for="content">Content</label>
            <textarea id="content" name="content"><?= htmlspecialchars($loadedItem['content']['raw'] ?? $loadedItem['content']['rendered'] ?? '') ?></textarea>

            <label for="excerpt">Excerpt</label>
            <textarea id="excerpt" name="excerpt" style="min-height: 80px;"><?= htmlspecialchars($loadedItem['excerpt']['raw'] ?? $loadedItem['excerpt']['rendered'] ?? '') ?></textarea>

            <div class="row">
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php 
                        $currentStatus = $loadedItem['status'] ?? 'draft';
                        $statuses = ['draft', 'publish', 'pending', 'private'];
                        foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($loadedItem['slug'] ?? '') ?>">
                </div>
            </div>

            <?php if ($itemType === 'post' || $itemType === 'cpt'): ?>
            <div class="row">
                <div>
                    <label for="categories">Categories</label>
                    <div id="categories-picker" class="picker-container">
                        <small class="picker-empty">Loading categories...</small>
                    </div>
                    <input type="text" id="categories" name="categories" placeholder="e.g. 1, 5, 12" value="<?= htmlspecialchars(implode(', ', (array)($loadedItem['categories'] ?? []))) ?>">
                    <p class="hint">Select above or enter comma-separated IDs</p>
                </div>
                <div>
                    <label for="tags">Tags</label>
                    <div id="tags-picker" class="picker-container">
                        <small class="picker-empty">Loading tags...</small>
                    </div>
                    <input type="text" id="tags" name="tags" placeholder="e.g. 3, 7" value="<?= htmlspecialchars(implode(', ', (array)($loadedItem['tags'] ?? []))) ?>">
                    <p class="hint">Select above or enter comma-separated IDs</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($itemType === 'page'): ?>
            <div class="row">
                <div>
                    <label for="parent_id">Parent Page</label>
                    <div id="parent-picker" class="picker-container">
                        <small class="picker-empty">Loading pages...</small>
                    </div>
                    <input type="number" id="parent_id" name="parent" min="0" value="<?= (int)($loadedItem['parent'] ?? 0) ?>">
                    <p class="hint">Select above or enter ID (0 = no parent)</p>
                </div>
                <div>
                    <label for="menu_order">Menu Order</label>
                    <input type="number" id="menu_order" name="menu_order" min="0" value="<?= (int)($loadedItem['menu_order'] ?? 0) ?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div>
                    <label for="featured_media">Featured Image (Media ID)</label>
                    <input type="number" id="featured_media" name="featured_media" min="0" value="<?= (int)($loadedItem['featured_media'] ?? 0) ?>">
                    <p class="hint">Enter media ID or leave 0 for no featured image</p>
                </div>
                <div>
                    <label for="template">Template</label>
                    <?php 
                    // Fetch templates dynamically from WordPress (with config fallback)
                    $templates = ($itemType === 'page') 
                        ? $qwp->templates()->getPageTemplates() 
                        : $qwp->templates()->getPostTemplates();
                    $currentTemplate = $loadedItem['template'] ?? '';
                    if (!empty($templates)): ?>
                        <select id="template" name="template">
                            <?php foreach ($templates as $file => $name): ?>
                                <option value="<?= htmlspecialchars($file) ?>" <?= $currentTemplate === $file ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?= ($currentTemplate !== '' && !isset($templates[$currentTemplate])) ? 'selected' : '' ?>>Custom...</option>
                        </select>
                        <input type="text" id="template_custom" name="template_custom" 
                               value="<?= ($currentTemplate !== '' && !isset($templates[$currentTemplate])) ? htmlspecialchars($currentTemplate) : '' ?>"
                               style="margin-top: 6px; <?= ($currentTemplate === '' || isset($templates[$currentTemplate])) ? 'display: none;' : '' ?>">
                    <?php else: ?>
                        <input type="text" id="template" name="template" value="<?= htmlspecialchars($currentTemplate) ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="button-row">
                <button type="submit">Save Changes</button>
            </div>
        </form>

        <!-- Delete Form (separate to avoid accidental submission) -->
        <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this <?= $itemType === 'cpt' ? htmlspecialchars($cptSlug) : $itemType ?>?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="item_type" value="<?= htmlspecialchars($itemType) ?>">
            <input type="hidden" name="cpt_slug" value="<?= htmlspecialchars($cptSlug) ?>">
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <?php if ($siteKey !== ''): ?>
                <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
            <?php endif; ?>
            
            <div class="delete-section">
                <label class="checkbox-label">
                    <input type="checkbox" name="force_delete" value="1">
                    <span>Permanently delete (skip trash)</span>
                </label>
                <button type="submit" class="btn-delete">Delete <?= ucfirst($itemType === 'cpt' ? $cptSlug : $itemType) ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($loadedItem): ?>
<script>
TaxonomyPicker.init({
    <?php if ($itemType === 'post' || $itemType === 'cpt'): ?>
    categoriesEndpoint: <?= json_encode($config->getCategoriesEndpoint()) ?>,
    tagsEndpoint: <?= json_encode($config->getTagsEndpoint()) ?>
    <?php else: ?>
    pagesEndpoint: <?= json_encode($config->getPagesEndpoint()) ?>
    <?php endif; ?>
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
<?php endif; ?>

<script>
function toggleCptField() {
    const typeSelect = document.getElementById('item_type');
    const cptField = document.getElementById('cpt-slug-field');
    const cptHint = document.getElementById('cpt-hint');
    
    if (typeSelect.value === 'cpt') {
        cptField.style.display = '';
        cptHint.style.display = '';
    } else {
        cptField.style.display = 'none';
        cptHint.style.display = 'none';
    }
}
</script>
</body>
</html>
