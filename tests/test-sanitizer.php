<?php
/**
 * Standalone sanitizer tests (no WordPress bootstrap).
 */

define('VSS_STANDALONE_TEST', true);

require dirname(__DIR__) . '/includes/class-vss-sanitizer.php';

$failed = 0;

function vss_assert($ok, $message) {
    global $failed;
    if ($ok) {
        echo "ok  $message\n";
        return;
    }
    $failed++;
    echo "FAIL  $message\n";
}

vss_assert(VSS_Sanitizer::selector('h1.hero { color }') === 'h1.hero color', 'selector strips braces');
vss_assert(strpos(VSS_Sanitizer::selector('<img onerror=alert(1)>'), '<') === false, 'selector strips angle brackets');

$props = VSS_Sanitizer::properties(array(
    'color' => '#111',
    'position' => 'fixed',
    'background-color' => 'red; } body { background: url(javascript:alert(1))',
));
vss_assert(isset($props['color']) && $props['color'] === '#111', 'allowlisted color kept');
vss_assert(!isset($props['position']), 'unknown property dropped');
vss_assert(!isset($props['background-color']), 'dangerous declaration dropped');

$custom = VSS_Sanitizer::custom_css('@import url(https://evil.test); color: blue; </style><script>alert(1)</script>');
vss_assert(strpos($custom, '@import') === false, 'import stripped');
vss_assert(strpos($custom, '<script') === false, 'tags stripped');

$css = VSS_Sanitizer::compile(array(
    array(
        'enabled' => 1,
        'selector' => 'h1.site-title',
        'properties' => array('color' => '#c00'),
        'custom_css' => 'letter-spacing: .02em',
    ),
    array(
        'enabled' => 0,
        'selector' => 'body',
        'properties' => array('display' => 'none'),
    ),
));
vss_assert(strpos($css, 'h1.site-title{color:#c00 !important;letter-spacing: .02em;}') !== false, 'enabled rule compiled');
vss_assert(strpos($css, 'body') === false, 'disabled rule skipped');

if ($failed) {
    echo "\n$failed test(s) failed\n";
    exit(1);
}

echo "\nAll sanitizer tests passed\n";
