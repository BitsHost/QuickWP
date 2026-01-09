<?php
/**
 * QuickWP v2 - Upload Media Controller
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
    $title = trim($_POST['title'] ?? '');
    $altText = trim($_POST['alt_text'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $attachPost = (int)($_POST['attach_post_id'] ?? 0);
    $featuredFor = (int)($_POST['featured_for_post_id'] ?? 0);

    if (empty($_FILES['media_file']['name'])) {
        $error = 'Please select a file to upload.';
    } else {
        $data = [];
        if ($title !== '') $data['title'] = $title;
        if ($altText !== '') $data['alt_text'] = $altText;
        if ($caption !== '') $data['caption'] = $caption;
        if ($description !== '') $data['description'] = $description;
        if ($attachPost > 0) $data['post'] = $attachPost;

        $result = $qwp->media()->upload(
            $_FILES['media_file'],
            $data
        );

        if (QuickWP::isSuccess($result)) {
            $mediaId = QuickWP::getId($result);
            $mediaUrl = $result['json']['source_url'] ?? ($result['json']['guid']['rendered'] ?? null);

            $messageParts = [];
            if ($mediaUrl) {
                $messageParts[] = 'Media uploaded: <a href="' . htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">View File</a>';
            }
            if ($mediaId) {
                $messageParts[] = 'ID: ' . $mediaId;
            }

            // Set as featured image
            if ($featuredFor > 0 && $mediaId) {
                $featuredResult = $qwp->media()->setFeaturedImage(
                    $featuredFor,
                    $mediaId,
                    $username !== '' ? $username : null,
                    $password !== '' ? $password : null
                );

                if (QuickWP::isSuccess($featuredResult)) {
                    $messageParts[] = 'Featured image set for Post ID ' . $featuredFor;
                } else {
                    $messageParts[] = 'Could not set featured image: ' . QuickWP::getError($featuredResult);
                }
            }

            $message = implode(' | ', $messageParts);
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
    ['url' => 'quick-media.php', 'label' => 'Media', 'active' => true],
    ['url' => 'quick-cpt.php', 'label' => 'CPT'],
    ['url' => 'quick-taxonomy.php', 'label' => 'Taxonomy'],
    ['url' => 'quick-list.php', 'label' => 'Browse'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Media - QuickWP</title>
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
        input[type="text"], input[type="number"], input[type="file"], textarea, select { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 4px; 
            border: 1px solid var(--border); 
            font-size: 0.95rem; 
        }
        input[type="file"] { padding: 8px; }
        textarea { min-height: 80px; resize: vertical; font-family: inherit; }
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
        
        .file-upload {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            margin-bottom: 16px;
        }
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

    <h1>Upload Media</h1>

    <?php if ($message): ?>
        <div class="message success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?php if ($siteKey !== ''): ?>
            <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
        <?php endif; ?>

        <div class="file-upload">
            <label for="media_file">Select File *</label>
            <input type="file" id="media_file" name="media_file" required>
        </div>

        <div class="row">
            <div>
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div>
                <label for="alt_text">Alt Text</label>
                <input type="text" id="alt_text" name="alt_text" value="<?= htmlspecialchars($_POST['alt_text'] ?? '') ?>">
            </div>
        </div>

        <label for="caption">Caption</label>
        <textarea id="caption" name="caption"><?= htmlspecialchars($_POST['caption'] ?? '') ?></textarea>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

        <div class="row">
            <div>
                <label for="attach_post_id">Attach to Post ID</label>
                <input type="number" id="attach_post_id" name="attach_post_id" min="0" value="<?= htmlspecialchars($_POST['attach_post_id'] ?? '0') ?>">
                <p class="hint">0 = no attachment</p>
            </div>
            <div>
                <label for="featured_for_post_id">Set as Featured Image for Post ID</label>
                <input type="number" id="featured_for_post_id" name="featured_for_post_id" min="0" value="<?= htmlspecialchars($_POST['featured_for_post_id'] ?? '0') ?>">
                <p class="hint">0 = don't set as featured</p>
            </div>
        </div>

        <button type="submit">Upload Media</button>
    </form>
</div>
</body>
</html>
