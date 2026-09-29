<?php
/**
 * Title: Latest Articles
 * Slug: wp-theme/latest-articles
 * Categories: wp-theme-current
 * Description: Responsive latest-post cards for the bottom of landing pages.
 */ ?>
<!-- wp:group {"className":"wp-theme-section-shell wp-theme-insights-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-insights-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} --><!-- wp:heading --><h2 class="wp-block-heading">Latest articles and useful updates</h2><!-- /wp:heading --><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:group {"className":"container wp-theme-blog-preview-container","layout":{"type":"default"}} --><div class="wp-block-group container wp-theme-blog-preview-container"><!-- wp:query {"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"displayLayout":{"type":"grid","columns":3},"className":"wp-theme-blog-preview"} --><div class="wp-block-query wp-theme-blog-preview"><!-- wp:post-template --><!-- wp:group {"className":"wp-theme-blog-card","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-blog-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /--><!-- wp:post-date /--><!-- wp:post-title {"isLink":true,"level":3} /--><!-- wp:post-excerpt {"moreText":"Read article"} /--></div><!-- /wp:group --><!-- /wp:post-template --></div><!-- /wp:query --></div><!-- /wp:group --></div><!-- /wp:group -->
