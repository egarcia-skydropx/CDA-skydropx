<?php die(); // archivo de diagnóstico desactivado
define('ABSPATH', __DIR__ . '/');
require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) wp_die('Sin acceso.');

echo '<style>body{font:13px monospace;padding:20px} .ok{color:green} .fail{color:red}</style>';
echo '<h2>Diagnóstico de archivos del plugin</h2>';

$files = array(
    'class-sxhc-post-type.php',
    'class-sxhc-taxonomy.php',
    'class-sxhc-importer.php',
    'class-sxhc-article-importer.php',
    'class-sxhc-admin-columns.php',
    'class-sxhc-search.php',
    'class-sxhc-bulk-actions.php',
    'class-sxhc-appearance.php',
    'class-sxhc-category-order.php',
);

$plugin_dir = __DIR__ . '/wp-content/plugins/skydropx-help-center/includes/';

foreach ($files as $f) {
    $path = $plugin_dir . $f;
    if (!file_exists($path)) {
        echo "<div class='fail'>❌ NO EXISTE: $f</div>";
        continue;
    }
    // Buscar sintaxis problemática
    $content = file_get_contents($path);
    $issues = array();
    if (preg_match('/\bfn\s*\(/', $content)) $issues[] = 'fn() arrow function (PHP 7.4+)';
    if (preg_match('/\bmatch\s*\(/', $content)) $issues[] = 'match() expression (PHP 8.0+)';
    if (preg_match('/\?\?=/', $content)) $issues[] = '??= operator (PHP 7.4+)';

    if ($issues) {
        echo "<div class='fail'>⚠️ $f — " . implode(', ', $issues) . "</div>";
    } else {
        echo "<div class='ok'>✅ $f</div>";
    }
}

echo '<h2>PHP Version</h2>';
echo '<div>' . phpversion() . '</div>';

echo '<h2>Clases registradas</h2>';
$classes = array('SXHC_Post_Type','SXHC_Taxonomy','SXHC_Importer','SXHC_Article_Importer',
                 'SXHC_Admin_Columns','SXHC_Search','SXHC_Bulk_Actions','SXHC_Appearance','SXHC_Category_Order');
foreach ($classes as $c) {
    $ok = class_exists($c);
    echo "<div class='" . ($ok?'ok':'fail') . "'>" . ($ok?'✅':'❌') . " $c</div>";
}

echo '<h2>Menús registrados (Help Center)</h2>';
global $submenu;
$key = 'edit.php?post_type=help_article';
if (!empty($submenu[$key])) {
    foreach ($submenu[$key] as $item) {
        echo "<div class='ok'>✅ {$item[3]} → page={$item[2]}</div>";
    }
} else {
    echo "<div class='fail'>❌ No hay submenús registrados bajo help_article</div>";
}

echo '<h2>Último error PHP</h2><pre>';
$err = error_get_last();
print_r($err);
echo '</pre>';
