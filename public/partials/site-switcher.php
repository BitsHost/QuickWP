<?php
/**
 * QuickWP v2 - Site Switcher Partial
 * 
 * Include this in controllers to add a site switcher dropdown.
 * 
 * Required variables before including:
 *   $loader   - ConfigLoader instance
 *   $siteKey  - Current site key
 * 
 * Usage:
 *   include __DIR__ . '/partials/site-switcher.php';
 */

if (!isset($loader) || !isset($siteKey)) {
    return;
}

$switcherSites = $loader->getSites();

if (empty($switcherSites)) {
    return;
}

// Determine current page and existing query params
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$existingParams = $_GET;
unset($existingParams['site']); // Remove site from params, will be re-added by form
$existingQuery = !empty($existingParams) ? '&' . http_build_query($existingParams) : '';
?>
<div class="site-switcher">
    <form method="get" action="<?= htmlspecialchars($currentPage) ?>">
        <?php foreach ($existingParams as $key => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
        
        <label for="site-select">Site:</label>
        <select id="site-select" name="site" onchange="this.form.submit()">
            <?php foreach ($switcherSites as $key => $site): ?>
                <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= ($key === $siteKey) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($site['label'] ?? $key, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn-small">Switch</button></noscript>
    </form>
</div>
<style>
.site-switcher {
    background: #f0f7fc;
    border: 1px solid #c3ddf0;
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.site-switcher form {
    display: flex;
    align-items: center;
    gap: 8px;
}
.site-switcher label {
    margin: 0;
    font-weight: 600;
    font-size: 0.9rem;
    color: #0073aa;
}
.site-switcher select {
    padding: 6px 10px;
    border: 1px solid #c3ddf0;
    border-radius: 4px;
    font-size: 0.9rem;
    background: #fff;
    min-width: 150px;
}
.site-switcher .btn-small {
    padding: 6px 12px;
    font-size: 0.85rem;
    margin: 0;
}
</style>
