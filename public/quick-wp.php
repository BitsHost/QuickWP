<?php
/**
 * QuickWP v2 - Alternative entry point (redirects to index.php)
 */
header('Location: index.php' . (isset($_GET['site']) ? '?site=' . urlencode($_GET['site']) : ''));
exit;
