<?php
/**
 * v3.0.7 layout hardening.
 * Removes empty block-template-part spacer markup and keeps header/footer flush.
 */
defined('ABSPATH') || exit;

function wpbbshop_v307_clean_template_part_markup($content, $block) {
    if (!is_string($content) || $content === '') { return $content; }
    $slug = isset($block['attrs']['slug']) ? sanitize_key($block['attrs']['slug']) : '';
    if (!in_array($slug, array('header', 'footer'), true)) { return $content; }

    // Remove editor-generated spacer paragraphs and BR tags around our shortcode output.
    $content = preg_replace('#<p(?:\\s[^>]*)?>(?:\\s|&nbsp;|&#160;|<br\\s*/?>)*</p>#i', '', $content);
    $content = preg_replace('#(?:<br\\s*/?>\\s*)+(?=<(?:header|footer)\\b)#i', '', $content);
    $content = preg_replace('#(</(?:header|footer)>)\\s*(?:<br\\s*/?>|<p(?:\\s[^>]*)?>(?:\\s|&nbsp;|&#160;|<br\\s*/?>)*</p>)*#i', '$1', $content);

    // Block themes add an outer header/footer.wp-block-template-part around a shortcode that already
    // returns a semantic header/footer. Unwrap it to remove nested landmarks and block-gap margins.
    $tag = $slug === 'header' ? 'header' : 'footer';
    $pattern = '#^\\s*<' . $tag . '\\b([^>]*)class=("|\\\')[^"\\\']*wp-block-template-part[^"\\\']*\\2([^>]*)>(.*)</' . $tag . '>\\s*$#is';
    if (preg_match($pattern, $content, $m)) {
        $inner = trim($m[4]);
        if (($slug === 'header' && strpos($inner, 'llg-header') !== false) || ($slug === 'footer' && strpos($inner, 'llg-footer') !== false)) {
            return $inner;
        }
    }
    return trim($content);
}
add_filter('render_block_core/template-part', 'wpbbshop_v307_clean_template_part_markup', 999, 2);

// Also normalize any classic content filter that injects blank paragraphs immediately around theme shortcodes.
add_filter('the_content', function($content) {
    if (!is_string($content) || (strpos($content, 'llg-header') === false && strpos($content, 'llg-footer') === false)) { return $content; }
    return preg_replace('#<p(?:\\s[^>]*)?>(?:\\s|&nbsp;|&#160;|<br\\s*/?>)*</p>#i', '', $content);
}, 999);
