<?php
/**
 * QuickWP v2 - List/Browse Posts & Pages
 * 
 * Browse recent posts and pages with pagination and quick actions.
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
$siteParam = $siteKey !== '' ? '&site=' . urlencode($siteKey) : '';

// Initialize QuickWP
$qwp = new QuickWP($config);

// Parameters
$itemType = $_GET['type'] ?? 'post';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$status = $_GET['status'] ?? 'any';
$search = trim($_GET['search'] ?? '');

$items = [];
$totalItems = 0;
$totalPages = 0;
$error = null;

// Fetch items
$service = $itemType === 'page' ? $qwp->pages() : $qwp->posts();

$params = [
    'per_page' => $perPage,
    'page' => $page,
    'orderby' => 'date',
    'order' => 'desc',
    '_fields' => 'id,title,status,date,modified,link,slug',
];

if ($status !== 'any') {
    $params['status'] = $status;
} else {
    $params['status'] = 'any'; // Get all statuses
}

if ($search !== '') {
    $params['search'] = $search;
}

$result = $service->list($params);

if (QuickWP::isSuccess($result)) {
    $items = $result['json'] ?? [];
    // Get total from headers if available
    $totalItems = (int)($result['headers']['x-wp-total'] ?? count($items));
    $totalPages = (int)($result['headers']['x-wp-totalpages'] ?? ceil($totalItems / $perPage));
} else {
    $error = 'Could not fetch items: ' . QuickWP::getError($result);
}

// Handle bulk actions
$bulkMessage = null;
$bulkError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action']) && !empty($_POST['item_ids'])) {
    $bulkAction = $_POST['bulk_action'];
    $itemIds = array_map('intval', (array)$_POST['item_ids']);
    $bulkService = $itemType === 'page' ? $qwp->pages() : $qwp->posts();
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($itemIds as $id) {
        if ($id <= 0) continue;
        
        $actionResult = null;
        switch ($bulkAction) {
            case 'trash':
                $actionResult = $bulkService->delete($id, false); // Move to trash
                break;
            case 'delete':
                $actionResult = $bulkService->delete($id, true); // Permanent delete
                break;
            case 'publish':
                $actionResult = $bulkService->update($id, ['status' => 'publish']);
                break;
            case 'draft':
                $actionResult = $bulkService->update($id, ['status' => 'draft']);
                break;
        }
        
        if ($actionResult && QuickWP::isSuccess($actionResult)) {
            $successCount++;
        } else {
            $failCount++;
        }
    }
    
    if ($successCount > 0) {
        $actionLabel = ['trash' => 'trashed', 'delete' => 'deleted', 'publish' => 'published', 'draft' => 'drafted'][$bulkAction] ?? $bulkAction;
        $bulkMessage = "$successCount item(s) $actionLabel successfully.";
    }
    if ($failCount > 0) {
        $bulkError = "$failCount item(s) failed.";
    }
    
    // Refresh the list
    $result = $service->list($params);
    if (QuickWP::isSuccess($result)) {
        $items = $result['json'] ?? [];
        $totalItems = (int)($result['headers']['x-wp-total'] ?? count($items));
        $totalPages = (int)($result['headers']['x-wp-totalpages'] ?? ceil($totalItems / $perPage));
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
    ['url' => 'quick-list.php', 'label' => 'Browse', 'active' => true],
];

// Build query string for pagination
function buildQueryString(array $params, string $siteKey): string {
    if ($siteKey !== '') {
        $params['site'] = $siteKey;
    }
    return '?' . http_build_query($params);
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse <?= ucfirst($itemType) ?>s - QuickWP</title>
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
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: #fafafa;
            border-radius: var(--radius);
        }
        .filters label { font-weight: 600; font-size: 0.9rem; }
        .filters select, .filters input[type="text"] {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .filters button {
            padding: 8px 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .filters button:hover { background: var(--primary-hover); }
        
        .message { 
            padding: 12px 16px; 
            border-radius: 4px; 
            margin-bottom: 16px; 
        }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .items-table th, .items-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .items-table tr:hover {
            background: #f8f9fa;
        }
        .item-title {
            font-weight: 500;
        }
        .item-title a {
            color: var(--primary);
            text-decoration: none;
        }
        .item-title a:hover {
            text-decoration: underline;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-publish { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        .status-private { background: #e2e3e5; color: #383d41; }
        .status-trash { background: #f8d7da; color: #721c24; }
        
        .actions a {
            color: var(--primary);
            text-decoration: none;
            margin-right: 10px;
            font-size: 0.85rem;
        }
        .actions a:hover { text-decoration: underline; }
        .actions a.view { color: var(--success); }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .pagination a {
            background: #e5f2fa;
            color: var(--primary);
        }
        .pagination a:hover { background: #d0e7f6; }
        .pagination .current {
            background: var(--primary);
            color: #fff;
        }
        .pagination .disabled {
            background: #eee;
            color: #999;
        }
        
        .summary {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        /* Bulk Actions */
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            background: #f9f9f9;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 12px;
        }
        .bulk-actions select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .bulk-actions button {
            margin: 0;
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        .bulk-actions .selected-count {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .checkbox-cell {
            width: 30px;
            text-align: center;
        }
        .checkbox-cell input {
            cursor: pointer;
        }
        
        @media (max-width: 600px) {
            .items-table { font-size: 0.8rem; }
            .items-table th, .items-table td { padding: 8px 6px; }
            .hide-mobile { display: none; }
            .bulk-actions { flex-wrap: wrap; }
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

    <h1>Browse <?= ucfirst($itemType) ?>s</h1>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($bulkMessage): ?>
        <div class="message success"><?= htmlspecialchars($bulkMessage) ?></div>
    <?php endif; ?>

    <?php if ($bulkError): ?>
        <div class="message error"><?= htmlspecialchars($bulkError) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="get" class="filters">
        <?php if ($siteKey !== ''): ?>
            <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
        <?php endif; ?>
        
        <div>
            <label for="type">Type</label><br>
            <select name="type" id="type">
                <option value="post" <?= $itemType === 'post' ? 'selected' : '' ?>>Posts</option>
                <option value="page" <?= $itemType === 'page' ? 'selected' : '' ?>>Pages</option>
            </select>
        </div>
        
        <div>
            <label for="status">Status</label><br>
            <select name="status" id="status">
                <option value="any" <?= $status === 'any' ? 'selected' : '' ?>>All</option>
                <option value="publish" <?= $status === 'publish' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="private" <?= $status === 'private' ? 'selected' : '' ?>>Private</option>
                <option value="trash" <?= $status === 'trash' ? 'selected' : '' ?>>Trash</option>
            </select>
        </div>
        
        <div>
            <label for="search">Search</label><br>
            <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
        </div>
        
        <div style="display: flex; align-items: flex-end;">
            <button type="submit">Filter</button>
        </div>
    </form>

    <?php if (!empty($items)): ?>
        <form method="post" id="bulk-form">
            <?php if ($siteKey !== ''): ?>
                <input type="hidden" name="site" value="<?= htmlspecialchars($siteKey) ?>">
            <?php endif; ?>
            
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions">
                <select name="bulk_action" id="bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Set to Draft</option>
                    <option value="trash">Move to Trash</option>
                    <option value="delete">Delete Permanently</option>
                </select>
                <button type="submit" onclick="return confirmBulkAction()">Apply</button>
                <span class="selected-count"><span id="selected-count">0</span> selected</span>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th class="hide-mobile">Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="item_ids[]" value="<?= (int)$item['id'] ?>" class="item-checkbox" onchange="updateCount()">
                            </td>
                            <td><?= (int)$item['id'] ?></td>
                            <td class="item-title">
                                <?php 
                                $title = $item['title']['rendered'] ?? $item['title'] ?? 'Untitled';
                                $title = html_entity_decode(strip_tags($title));
                                if (strlen($title) > 50) $title = substr($title, 0, 50) . '...';
                                ?>
                                <a href="quick-edit.php?action=load&item_type=<?= $itemType ?>&item_id=<?= (int)$item['id'] ?><?= $siteParam ?>">
                                    <?= htmlspecialchars($title) ?>
                                </a>
                            </td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($item['status'] ?? 'draft') ?>">
                                    <?= htmlspecialchars($item['status'] ?? 'draft') ?>
                                </span>
                            </td>
                            <td class="hide-mobile">
                                <?php 
                                $date = $item['date'] ?? '';
                                if ($date) {
                                    $dt = new DateTime($date);
                                    echo $dt->format('M j, Y');
                                }
                                ?>
                            </td>
                            <td class="actions">
                                <a href="quick-edit.php?action=load&item_type=<?= $itemType ?>&item_id=<?= (int)$item['id'] ?><?= $siteParam ?>">Edit</a>
                                <?php if (!empty($item['link'])): ?>
                                    <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" class="view">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </form><!-- End bulk form -->

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= buildQueryString(['type' => $itemType, 'status' => $status, 'search' => $search, 'page' => $page - 1], $siteKey) ?>">&laquo; Prev</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Prev</span>
                <?php endif; ?>

                <?php
                // Show page numbers
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($start > 1) {
                    echo '<a href="' . buildQueryString(['type' => $itemType, 'status' => $status, 'search' => $search, 'page' => 1], $siteKey) . '">1</a>';
                    if ($start > 2) echo '<span class="disabled">...</span>';
                }
                
                for ($i = $start; $i <= $end; $i++):
                    if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= buildQueryString(['type' => $itemType, 'status' => $status, 'search' => $search, 'page' => $i], $siteKey) ?>"><?= $i ?></a>
                    <?php endif;
                endfor;
                
                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<span class="disabled">...</span>';
                    echo '<a href="' . buildQueryString(['type' => $itemType, 'status' => $status, 'search' => $search, 'page' => $totalPages], $siteKey) . '">' . $totalPages . '</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= buildQueryString(['type' => $itemType, 'status' => $status, 'search' => $search, 'page' => $page + 1], $siteKey) ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="summary">
            Showing <?= count($items) ?> of <?= $totalItems ?> <?= $itemType ?>s
            <?php if ($totalPages > 1): ?>(Page <?= $page ?> of <?= $totalPages ?>)<?php endif; ?>
        </p>

    <?php elseif (!$error): ?>
        <div class="empty-state">
            <p>No <?= $itemType ?>s found.</p>
            <p><a href="quick-<?= $itemType ?>.php<?= $siteSuffix ?>">Create a new <?= $itemType ?></a></p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleAll(checkbox) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
    
    // Update select-all checkbox state
    const total = document.querySelectorAll('.item-checkbox').length;
    const selectAll = document.getElementById('select-all');
    if (count === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else if (count === total) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    }
}

function confirmBulkAction() {
    const action = document.getElementById('bulk_action').value;
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    
    if (!action) {
        alert('Please select a bulk action.');
        return false;
    }
    
    if (count === 0) {
        alert('Please select at least one item.');
        return false;
    }
    
    const actionLabels = {
        'publish': 'publish',
        'draft': 'set to draft',
        'trash': 'move to trash',
        'delete': 'PERMANENTLY DELETE'
    };
    
    const label = actionLabels[action] || action;
    
    if (action === 'delete') {
        return confirm(`Are you sure you want to ${label} ${count} item(s)? This cannot be undone!`);
    }
    
    return confirm(`Are you sure you want to ${label} ${count} item(s)?`);
}
</script>
</body>
</html>
