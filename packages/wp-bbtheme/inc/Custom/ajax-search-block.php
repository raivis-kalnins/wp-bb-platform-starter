<?php
/**
 * Theme AJAX Search Block
 */

if (!defined('ABSPATH')) {
    exit;
}

function tfa_ajax_search_allowed_post_types(): array {
    $post_types = [
        'post' => __('Blogs', 'wp-theme'),
        'page' => __('Pages', 'wp-theme'),
    ];

    if (post_type_exists('product')) {
        $post_types['product'] = __('Products', 'wp-theme');
    }

    return $post_types;
}

function tfa_ajax_search_product_categories(): array {
    if (!taxonomy_exists('product_cat')) {
        return [];
    }

    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $options = [];
    foreach ($terms as $term) {
        $options[] = [
            'slug' => $term->slug,
            'name' => $term->name,
        ];
    }

    return $options;
}

function tfa_ajax_search_block_attributes(): array {
    return [
        'placeholder' => [
            'type'    => 'string',
            'default' => 'Search products, posts and pages',
        ],
        'buttonText' => [
            'type'    => 'string',
            'default' => 'Search',
        ],
        'minChars' => [
            'type'    => 'number',
            'default' => 2,
        ],
        'resultsLimit' => [
            'type'    => 'number',
            'default' => 8,
        ],
        'postTypes' => [
            'type'    => 'array',
            'default' => array_keys(tfa_ajax_search_allowed_post_types()),
            'items'   => [
                'type' => 'string',
            ],
        ],
        'showImage' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'showExcerpt' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'showPrice' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'showTypeLabel' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'searchButton' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'searchInSku' => [
            'type'    => 'boolean',
            'default' => false,
        ],
        'highlightTerms' => [
            'type'    => 'boolean',
            'default' => true,
        ],
        'productCategory' => [
            'type'    => 'string',
            'default' => '',
        ],
        'searchScopeControl' => [
            'type'    => 'string',
            'default' => 'none',
        ],
        'className' => [
            'type' => 'string',
        ],
    ];
}

function tfa_ajax_search_register_block(): void {
    if (!function_exists('register_block_type')) {
        return;
    }

    register_block_type('tfa/ajax-search', [
        'api_version'     => 2,
        'render_callback' => 'tfa_ajax_search_render_block',
        'attributes'      => tfa_ajax_search_block_attributes(),
        'supports'        => [
            'html'      => false,
            'className' => true,
            'anchor'    => true,
        ],
    ]);
}
add_action('init', 'tfa_ajax_search_register_block');

function tfa_ajax_search_enqueue_editor_assets(): void {
    if (!is_admin()) {
        return;
    }

    wp_enqueue_script('wp-blocks');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-block-editor');
    wp_enqueue_script('wp-server-side-render');
    wp_enqueue_script('wp-i18n');

    $handle = 'tfa-ajax-search-editor';
    wp_register_script($handle, '', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n'], '1.1.0', true);

    wp_localize_script($handle, 'tfaAjaxSearchEditor', [
        'postTypes'         => tfa_ajax_search_allowed_post_types(),
        'productCategories' => tfa_ajax_search_product_categories(),
    ]);

    $script = <<<'JS'
(function(wp, config) {
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const {
        PanelBody,
        TextControl,
        RangeControl,
        ToggleControl,
        CheckboxControl,
        SelectControl,
        Notice
    } = wp.components;
    const ServerSideRender = wp.serverSideRender;
    const el = wp.element.createElement;
    const Fragment = wp.element.Fragment;

    const buildPostTypeControls = (attrs, props) => {
        return Object.entries(config.postTypes || {}).map(([value, label]) => {
            return el(CheckboxControl, {
                key: value,
                label,
                checked: (attrs.postTypes || []).includes(value),
                onChange: function(checked) {
                    const next = new Set(attrs.postTypes || []);
                    if (checked) {
                        next.add(value);
                    } else {
                        next.delete(value);
                    }
                    const nextArr = Array.from(next);
                    props.setAttributes({ postTypes: nextArr.length ? nextArr : ['post'] });
                }
            });
        });
    };

    registerBlockType('tfa/ajax-search', {
        title: __('TFA AJAX Search', 'wp-theme'),
        icon: 'search',
        category: 'widgets',
        description: __('AJAX search for products, posts and pages.', 'wp-theme'),
        attributes: {
            placeholder: { type: 'string', default: 'Search products, posts and pages' },
            buttonText: { type: 'string', default: 'Search' },
            minChars: { type: 'number', default: 2 },
            resultsLimit: { type: 'number', default: 8 },
            postTypes: { type: 'array', default: Object.keys(config.postTypes || {}) },
            showImage: { type: 'boolean', default: true },
            showExcerpt: { type: 'boolean', default: true },
            showPrice: { type: 'boolean', default: true },
            showTypeLabel: { type: 'boolean', default: true },
            searchButton: { type: 'boolean', default: true },
            searchInSku: { type: 'boolean', default: false },
            highlightTerms: { type: 'boolean', default: true },
            productCategory: { type: 'string', default: '' },
            searchScopeControl: { type: 'string', default: 'none' },
        },
        edit: function(props) {
            const attrs = props.attributes;
            const blockProps = useBlockProps({ className: 'tfa-ajax-search-editor-preview' });
            const selectedPostTypes = attrs.postTypes || [];
            const productsEnabled = selectedPostTypes.includes('product');
            const categoryOptions = [{ label: __('All product categories', 'wp-theme'), value: '' }]
                .concat((config.productCategories || []).map((item) => ({ label: item.name, value: item.slug })));

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Search content', 'wp-theme'), initialOpen: true },
                        buildPostTypeControls(attrs, props),
                        !productsEnabled
                            ? el(Notice, { status: 'info', isDismissible: false }, __('Enable Products to use SKU search, price output, and category filtering.', 'wp-theme'))
                            : null,
                        productsEnabled
                            ? el(SelectControl, {
                                label: __('Product category filter', 'wp-theme'),
                                value: attrs.productCategory || '',
                                options: categoryOptions,
                                onChange: (value) => props.setAttributes({ productCategory: value })
                            })
                            : null,
                        productsEnabled
                            ? el(ToggleControl, {
                                label: __('Search WooCommerce SKU', 'wp-theme'),
                                checked: !!attrs.searchInSku,
                                onChange: (value) => props.setAttributes({ searchInSku: value })
                            })
                            : null
                    ),
                    el(
                        PanelBody,
                        { title: __('Search behaviour', 'wp-theme'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Front-end scope control', 'wp-theme'),
                            value: attrs.searchScopeControl || 'none',
                            options: [
                                { label: __('None', 'wp-theme'), value: 'none' },
                                { label: __('Radio buttons', 'wp-theme'), value: 'radio' },
                                { label: __('Compact dropdown', 'wp-theme'), value: 'select' }
                            ],
                            onChange: (value) => props.setAttributes({ searchScopeControl: value })
                        }),
                        el(TextControl, {
                            label: __('Placeholder', 'wp-theme'),
                            value: attrs.placeholder,
                            onChange: (value) => props.setAttributes({ placeholder: value })
                        }),
                        el(TextControl, {
                            label: __('Button text', 'wp-theme'),
                            value: attrs.buttonText,
                            onChange: (value) => props.setAttributes({ buttonText: value })
                        }),
                        el(RangeControl, {
                            label: __('Minimum characters', 'wp-theme'),
                            min: 1,
                            max: 5,
                            value: attrs.minChars,
                            onChange: (value) => props.setAttributes({ minChars: value || 2 })
                        }),
                        el(RangeControl, {
                            label: __('Results limit', 'wp-theme'),
                            min: 4,
                            max: 20,
                            value: attrs.resultsLimit,
                            onChange: (value) => props.setAttributes({ resultsLimit: value || 8 })
                        }),
                        el(ToggleControl, {
                            label: __('Show submit button', 'wp-theme'),
                            checked: !!attrs.searchButton,
                            onChange: (value) => props.setAttributes({ searchButton: value })
                        }),
                        el(ToggleControl, {
                            label: __('Highlight matched terms', 'wp-theme'),
                            checked: !!attrs.highlightTerms,
                            onChange: (value) => props.setAttributes({ highlightTerms: value })
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Result cards', 'wp-theme'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show type labels', 'wp-theme'),
                            checked: !!attrs.showTypeLabel,
                            onChange: (value) => props.setAttributes({ showTypeLabel: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show images', 'wp-theme'),
                            checked: !!attrs.showImage,
                            onChange: (value) => props.setAttributes({ showImage: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show excerpts', 'wp-theme'),
                            checked: !!attrs.showExcerpt,
                            onChange: (value) => props.setAttributes({ showExcerpt: value })
                        }),
                        productsEnabled
                            ? el(ToggleControl, {
                                label: __('Show WooCommerce prices', 'wp-theme'),
                                checked: !!attrs.showPrice,
                                onChange: (value) => props.setAttributes({ showPrice: value })
                            })
                            : null
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(ServerSideRender, {
                        block: 'tfa/ajax-search',
                        attributes: attrs
                    })
                )
            );
        },
        save: function() {
            return null;
        }
    });
})(window.wp, window.tfaAjaxSearchEditor || {});
JS;

    wp_add_inline_script($handle, $script);
    wp_enqueue_script($handle);
}
add_action('enqueue_block_editor_assets', 'tfa_ajax_search_enqueue_editor_assets');

function tfa_ajax_search_frontend_config(): void {
    if (is_admin()) {
        return;
    }

    $handle = 'wp-theme-ajax-search';
    $asset_path = get_stylesheet_directory() . '/assets/js/ajax-search.js';
    if (file_exists($asset_path)) {
        wp_enqueue_script(
            $handle,
            get_stylesheet_directory_uri() . '/assets/js/ajax-search.js',
            [],
            filemtime($asset_path),
            true
        );
    } else {
        wp_register_script($handle, '', [], '1.0.0', true);
        wp_enqueue_script($handle);
    }

    wp_localize_script($handle, 'tfaAjaxSearch', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('tfa_ajax_search_nonce'),
        'labels'  => [
            'loading'    => __('Searching…', 'wp-theme'),
            'viewAll'    => __('View all results', 'wp-theme'),
            'noResults'  => __('No results found.', 'wp-theme'),
            'typeMore'   => __('Type to search…', 'wp-theme'),
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'tfa_ajax_search_frontend_config', 20);

function tfa_ajax_search_render_block(array $attributes = []): string {
    $post_types = !empty($attributes['postTypes']) && is_array($attributes['postTypes'])
        ? array_values(array_intersect($attributes['postTypes'], array_keys(tfa_ajax_search_allowed_post_types())))
        : array_keys(tfa_ajax_search_allowed_post_types());

    if (empty($post_types)) {
        $post_types = ['post'];
    }

    $wrapper_classes = ['wp-block-tfa-ajax-search', 'tfa-ajax-search'];
    if (!empty($attributes['className'])) {
        foreach (preg_split('/\s+/', (string) $attributes['className']) as $class_name) {
            $class_name = sanitize_html_class($class_name);
            if ($class_name) {
                $wrapper_classes[] = $class_name;
            }
        }
    }

    $placeholder      = sanitize_text_field($attributes['placeholder'] ?? 'Search products, posts and pages');
    $button_text      = sanitize_text_field($attributes['buttonText'] ?? 'Search');
    $min_chars        = max(1, (int) ($attributes['minChars'] ?? 2));
    $results_limit    = max(1, min(20, (int) ($attributes['resultsLimit'] ?? 8)));
    $show_image       = !empty($attributes['showImage']) ? '1' : '0';
    $show_excerpt     = !empty($attributes['showExcerpt']) ? '1' : '0';
    $show_price       = !empty($attributes['showPrice']) ? '1' : '0';
    $show_type_label  = !empty($attributes['showTypeLabel']) ? '1' : '0';
    $search_button    = !empty($attributes['searchButton']);
    $search_in_sku    = !empty($attributes['searchInSku']) ? '1' : '0';
    $highlight_terms  = !empty($attributes['highlightTerms']) ? '1' : '0';
    $product_category = sanitize_title($attributes['productCategory'] ?? '');
    $scope_control_raw = sanitize_key($attributes['searchScopeControl'] ?? 'none');
    $scope_control = in_array($scope_control_raw, ['radio', 'select'], true) ? $scope_control_raw : 'none';

    // Backwards-compatible defaults for existing template classes.
    $class_string = implode(' ', $wrapper_classes);
    $is_header_search = (bool) preg_match('/\b(iws-header-search|header-product-search)\b/', $class_string);
    $header_products_only = $is_header_search && function_exists('wp_theme_acf_get') && (bool) wp_theme_acf_get('only_prod_search', 'option', 0);
    if ($header_products_only) {
        $wrapper_classes[] = 'iws-header-search-products-only';
    }
    if ($scope_control === 'none') {
        if ($is_header_search) {
            $scope_control = 'select';
        } elseif (preg_match('/\b(search-page-search|shop-search)\b/', $class_string)) {
            $scope_control = 'radio';
        }
    }
    if ($header_products_only && post_type_exists('product')) {
        $scope_control = 'none';
        $post_types = ['product'];
        $button_text = __('Only Prod Search', 'wp-theme');
    }

    $current_scope = 'products';
    $requested_scope = isset($_GET['iws_search_scope']) ? sanitize_key(wp_unslash($_GET['iws_search_scope'])) : '';
    $requested_post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
    if (in_array($requested_scope, ['products', 'posts', 'all'], true)) {
        $current_scope = $requested_scope;
    } elseif ($requested_post_type === 'post') {
        $current_scope = 'posts';
    } elseif ($requested_post_type === 'product') {
        $current_scope = 'products';
    }

    if ($scope_control !== 'none') {
        $post_types = array_values(array_intersect(['product', 'post', 'page'], array_keys(tfa_ajax_search_allowed_post_types())));
        if (!in_array('product', $post_types, true) && post_type_exists('product')) {
            array_unshift($post_types, 'product');
        }
    }

    // Always submit to the normal WordPress search page.
    // The iws_search_scope value is applied in pre_get_posts below, which avoids
    // WooCommerce redirecting product searches to the shop/product archive.
    $search_url = home_url('/');

    $input_id = wp_unique_id('tfa-search-');

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', array_filter($wrapper_classes))); ?>"
        data-min-chars="<?php echo esc_attr((string) $min_chars); ?>"
        data-results-limit="<?php echo esc_attr((string) $results_limit); ?>"
        data-post-types="<?php echo esc_attr(wp_json_encode($post_types)); ?>"
        data-show-image="<?php echo esc_attr($show_image); ?>"
        data-show-excerpt="<?php echo esc_attr($show_excerpt); ?>"
        data-show-price="<?php echo esc_attr($show_price); ?>"
        data-show-type-label="<?php echo esc_attr($show_type_label); ?>"
        data-search-in-sku="<?php echo esc_attr($search_in_sku); ?>"
        data-highlight-terms="<?php echo esc_attr($highlight_terms); ?>"
        data-product-category="<?php echo esc_attr($product_category); ?>"
        data-scope-control="<?php echo esc_attr($scope_control); ?>"
        data-current-scope="<?php echo esc_attr($current_scope); ?>"
        data-site-search-url="<?php echo esc_url(home_url('/')); ?>"
        data-product-search-url="<?php echo esc_url(home_url('/')); ?>">
        <form class="tfa-ajax-search__form" role="search" method="get" action="<?php echo esc_url($search_url); ?>">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php esc_html_e('Search', 'wp-theme'); ?></label>
            <div class="tfa-ajax-search__field-wrap">
                <?php if ($scope_control === 'select') : ?>
                    <label class="screen-reader-text" for="<?php echo esc_attr($input_id . '-scope'); ?>"><?php esc_html_e('Search in', 'wp-theme'); ?></label>
                    <select id="<?php echo esc_attr($input_id . '-scope'); ?>" class="tfa-ajax-search__scope-select" name="iws_search_scope" aria-label="<?php esc_attr_e('Search in', 'wp-theme'); ?>" data-no-select2="1">
                        <option value="products" <?php selected($current_scope, 'products'); ?>><?php esc_html_e('Products', 'wp-theme'); ?></option>
                        <option value="posts" <?php selected($current_scope, 'posts'); ?>><?php esc_html_e('Blogs', 'wp-theme'); ?></option>
                        <option value="all" <?php selected($current_scope, 'all'); ?>><?php esc_html_e('All', 'wp-theme'); ?></option>
                    </select>
                <?php endif; ?>
                <input
                    id="<?php echo esc_attr($input_id); ?>"
                    class="tfa-ajax-search__input"
                    type="search"
                    name="s"
                    value="<?php echo isset($_GET['s']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['s']))) : ''; ?>"
                    autocomplete="off"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    aria-label="<?php esc_attr_e('Search site', 'wp-theme'); ?>"
                />
                <?php if ($search_button) : ?>
                    <button class="tfa-ajax-search__button" type="submit" aria-label="<?php esc_attr_e('Search', 'wp-theme'); ?>"><?php echo $button_text !== '' ? esc_html($button_text) : '<span aria-hidden="true">⌕</span>'; ?></button>
                <?php endif; ?>
            </div>
            <?php if ($scope_control === 'radio') : ?>
                <fieldset class="tfa-ajax-search__scope tfa-ajax-search__scope--radio" aria-label="<?php esc_attr_e('Search in', 'wp-theme'); ?>">
                    <label><input type="radio" name="iws_search_scope" value="products" <?php checked($current_scope, 'products'); ?> /> <span><?php esc_html_e('Products', 'wp-theme'); ?></span></label>
                    <label><input type="radio" name="iws_search_scope" value="posts" <?php checked($current_scope, 'posts'); ?> /> <span><?php esc_html_e('Blogs', 'wp-theme'); ?></span></label>
                    <label><input type="radio" name="iws_search_scope" value="all" <?php checked($current_scope, 'all'); ?> /> <span><?php esc_html_e('All', 'wp-theme'); ?></span></label>
                </fieldset>
            <?php endif; ?>
            <input class="tfa-ajax-search__post-type" type="hidden" name="post_type" value="" disabled />
            <?php if ($product_category) : ?>
                <input type="hidden" name="product_cat" value="<?php echo esc_attr($product_category); ?>" />
            <?php endif; ?>
        </form>
        <div class="tfa-ajax-search__results" aria-live="polite" hidden></div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function tfa_ajax_search_result_type_label(string $post_type): string {
    $obj = get_post_type_object($post_type);
    return $obj && !empty($obj->labels->name) ? $obj->labels->name : ucfirst($post_type);
}

function tfa_ajax_search_build_query_args(string $post_type, string $term, int $remaining, string $product_category = ''): array {
    $args = [
        'post_type'              => $post_type,
        'post_status'            => 'publish',
        'posts_per_page'         => max(1, $remaining),
        's'                      => $term,
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'orderby'                => 'relevance',
        'order'                  => 'DESC',
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ];

    if ($post_type === 'product' && $product_category && taxonomy_exists('product_cat')) {
        $args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $product_category,
        ]];
    }

    return $args;
}

function tfa_ajax_search_find_sku_product_ids(string $term, int $limit, string $product_category = ''): array {
    global $wpdb;

    $like = '%' . $wpdb->esc_like($term) . '%';
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT CASE WHEN p.post_type = 'product_variation' AND p.post_parent > 0 THEN p.post_parent ELSE p.ID END AS product_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('product','product_variation')
              AND p.post_status IN ('publish','private')
              AND pm.meta_key = '_sku'
              AND pm.meta_value LIKE %s
            LIMIT %d",
            $like,
            max(1, $limit * 3)
        )
    );

    $ids = array_values(array_unique(array_map('intval', $ids ?: [])));
    if (!$ids || !$product_category || !taxonomy_exists('product_cat')) {
        return array_slice($ids, 0, max(1, $limit));
    }

    $ids = array_values(array_filter($ids, static function ($post_id) use ($product_category) {
        return has_term($product_category, 'product_cat', $post_id);
    }));

    return array_slice($ids, 0, max(1, $limit));
}

function tfa_ajax_search_highlight(string $text, string $term, bool $enabled): string {
    $safe_text = esc_html($text);
    if (!$enabled || $term === '') {
        return $safe_text;
    }

    $pattern = '/' . preg_quote($term, '/') . '/iu';
    return (string) preg_replace($pattern, '<mark>$0</mark>', $safe_text);
}

function tfa_ajax_search_render_results_html(array $grouped, string $term, bool $show_image, bool $show_excerpt, bool $show_price, bool $show_type_label, bool $highlight_terms, array $post_types, string $product_category): string {
    ob_start();

    if (!empty($grouped)) {
        echo '<div class="tfa-ajax-search__panel">';
        foreach ($grouped as $post_type => $posts) {
            echo '<div class="tfa-ajax-search__group">';
            if ($show_type_label) {
                echo '<div class="tfa-ajax-search__group-title">' . esc_html(tfa_ajax_search_result_type_label($post_type)) . '</div>';
            }
            echo '<ul class="tfa-ajax-search__list">';
            foreach ($posts as $post) {
                $permalink = get_permalink($post);
                $title = get_the_title($post) ?: __('Untitled', 'wp-theme');
                $excerpt = $show_excerpt ? wp_trim_words(wp_strip_all_tags(get_the_excerpt($post) ?: $post->post_content), 16) : '';
                $thumb = $show_image ? get_the_post_thumbnail_url($post, 'thumbnail') : '';

                echo '<li class="tfa-ajax-search__item">';
                echo '<a class="tfa-ajax-search__result" href="' . esc_url($permalink) . '">';
                if ($thumb) {
                    echo '<span class="tfa-ajax-search__media"><img src="' . esc_url($thumb) . '" alt="" loading="lazy" /></span>';
                }
                echo '<span class="tfa-ajax-search__content">';
                echo '<span class="tfa-ajax-search__title">' . wp_kses(tfa_ajax_search_highlight($title, $term, $highlight_terms), ['mark' => []]) . '</span>';
                if ($show_price && $post_type === 'product' && function_exists('wc_get_product')) {
                    $product = wc_get_product($post->ID);
                    if ($product) {
                        echo '<span class="tfa-ajax-search__meta tfa-ajax-search__price">' . wp_kses_post($product->get_price_html()) . '</span>';
                    }
                }
                if ($post_type === 'product' && function_exists('wc_get_product')) {
                    $product = isset($product) && $product ? $product : wc_get_product($post->ID);
                    if ($product && $product->get_sku()) {
                        echo '<span class="tfa-ajax-search__meta tfa-ajax-search__sku">' . esc_html__('SKU:', 'wp-theme') . ' ' . esc_html($product->get_sku()) . '</span>';
                    }
                }
                if ($excerpt) {
                    echo '<span class="tfa-ajax-search__excerpt">' . wp_kses(tfa_ajax_search_highlight($excerpt, $term, $highlight_terms), ['mark' => []]) . '</span>';
                }
                echo '</span>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        $view_all_url = add_query_arg(['s' => $term, 'iws_search_scope' => 'all'], home_url('/'));
        if (count($post_types) === 1 && in_array('product', $post_types, true)) {
            $args = [
                's'                => $term,
                'iws_search_scope' => 'products',
            ];
            if ($product_category) {
                $args['product_cat'] = $product_category;
            }
            $view_all_url = add_query_arg($args, home_url('/'));
        } elseif (count($post_types) === 1 && in_array('post', $post_types, true)) {
            $view_all_url = add_query_arg(['s' => $term, 'post_type' => 'post', 'iws_search_scope' => 'posts'], home_url('/'));
        }

        echo '<div class="tfa-ajax-search__footer"><a class="tfa-ajax-search__view-all" href="' . esc_url($view_all_url) . '">' . esc_html__('View all results', 'wp-theme') . '</a></div>';
        echo '</div>';
    } else {
        echo '<div class="tfa-ajax-search__empty">' . esc_html__('No results found.', 'wp-theme') . '</div>';
    }

    return (string) ob_get_clean();
}

function tfa_ajax_search_ajax_handler(): void {
    check_ajax_referer('tfa_ajax_search_nonce', 'nonce');

    $raw_term = isset($_POST['term']) ? wp_unslash($_POST['term']) : '';
    $term = trim(sanitize_text_field($raw_term));

    $allowed = array_keys(tfa_ajax_search_allowed_post_types());
    $post_types = isset($_POST['postTypes']) ? (array) $_POST['postTypes'] : $allowed;
    $post_types = array_values(array_intersect(array_map('sanitize_key', $post_types), $allowed));
    if (empty($post_types)) {
        $post_types = ['post'];
    }

    $limit            = isset($_POST['resultsLimit']) ? (int) $_POST['resultsLimit'] : 8;
    $limit            = max(1, min(20, $limit));
    $show_image       = !empty($_POST['showImage']);
    $show_excerpt     = !empty($_POST['showExcerpt']);
    $show_price       = !empty($_POST['showPrice']);
    $show_type_label  = !empty($_POST['showTypeLabel']);
    $search_in_sku    = !empty($_POST['searchInSku']) || in_array('product', $post_types, true);
    $highlight_terms  = !empty($_POST['highlightTerms']);
    $product_category = isset($_POST['productCategory']) ? sanitize_title(wp_unslash($_POST['productCategory'])) : '';

    if (mb_strlen($term) < 1) {
        wp_send_json_success([
            'html' => '<div class="tfa-ajax-search__empty">' . esc_html__('Type to search…', 'wp-theme') . '</div>',
        ]);
    }

    $grouped = [];
    $used_ids = [];
    $remaining = $limit;

    foreach ($post_types as $post_type) {
        if ($remaining < 1) {
            break;
        }

        $query = new WP_Query(tfa_ajax_search_build_query_args($post_type, $term, $remaining, $product_category));
        $posts = $query->posts;

        if ($post_type === 'product' && $search_in_sku) {
            $sku_ids = tfa_ajax_search_find_sku_product_ids($term, $remaining, $product_category);
            $existing_ids = wp_list_pluck($posts, 'ID');
            $sku_ids = array_values(array_diff($sku_ids, $used_ids));
            if (!empty($sku_ids)) {
                $sku_query = new WP_Query([
                    'post_type'              => 'product',
                    'post_status'            => 'publish',
                    'post__in'               => array_slice($sku_ids, 0, $remaining),
                    'orderby'                => 'post__in',
                    'posts_per_page'         => $remaining,
                    'ignore_sticky_posts'    => true,
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                ]);
                $sku_posts = $sku_query->posts;
                $posts = array_merge($sku_posts, array_values(array_filter($posts, static function ($post) use ($sku_ids) {
                    return !in_array((int) $post->ID, $sku_ids, true);
                })));
            }
        }

        if (!empty($used_ids)) {
            $posts = array_values(array_filter($posts, static function ($post) use ($used_ids) {
                return !in_array((int) $post->ID, $used_ids, true);
            }));
        }

        $posts = array_slice($posts, 0, $remaining);
        if (!empty($posts)) {
            $grouped[$post_type] = $posts;
            $used_ids = array_merge($used_ids, wp_list_pluck($posts, 'ID'));
            $remaining -= count($posts);
        }
    }

    wp_send_json_success([
        'html' => tfa_ajax_search_render_results_html($grouped, $term, $show_image, $show_excerpt, $show_price, $show_type_label, $highlight_terms, $post_types, $product_category),
    ]);
}
add_action('wp_ajax_tfa_ajax_search', 'tfa_ajax_search_ajax_handler');
add_action('wp_ajax_nopriv_tfa_ajax_search', 'tfa_ajax_search_ajax_handler');

/**
 * Keep submitted product searches aligned with the AJAX search UX.
 * - /?s=ABC&iws_search_scope=products searches product title/content AND SKU on the normal search page.
 * - ?iws_search_scope=posts searches posts only.
 * - ?iws_search_scope=all searches products, posts and pages.
 */
function tfa_ajax_search_request_scope(): string {
    $scope = isset($_GET['iws_search_scope']) ? sanitize_key(wp_unslash($_GET['iws_search_scope'])) : '';
    if (in_array($scope, ['products', 'posts', 'all'], true)) {
        return $scope;
    }

    $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
    if ($post_type === 'product') {
        return 'products';
    }
    if ($post_type === 'post') {
        return 'posts';
    }

    return '';
}

function tfa_ajax_search_apply_scope_to_main_query(WP_Query $query): void {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return;
    }

    $scope = tfa_ajax_search_request_scope();
    if ($scope === 'products') {
        $query->set('post_type', ['product']);
    } elseif ($scope === 'posts') {
        $query->set('post_type', ['post']);
    } elseif ($scope === 'all') {
        $query->set('post_type', array_values(array_intersect(['product', 'post', 'page'], array_keys(tfa_ajax_search_allowed_post_types()))));
    }
}
add_action('pre_get_posts', 'tfa_ajax_search_apply_scope_to_main_query', 20);

function tfa_ajax_search_query_includes_product_sku(string $search, WP_Query $query): string {
    if (is_admin() || $search === '') {
        return $search;
    }

    $term = $query->get('s');
    if (!is_string($term) || trim($term) === '') {
        return $search;
    }

    $post_type = (array) $query->get('post_type');
    $scope = tfa_ajax_search_request_scope();
    $is_product_search = in_array('product', $post_type, true) || $scope === 'products' || $scope === 'all';
    if (!$is_product_search || !post_type_exists('product')) {
        return $search;
    }

    $sku_ids = tfa_ajax_search_find_sku_product_ids($term, 500);
    if (empty($sku_ids)) {
        return $search;
    }

    global $wpdb;
    $id_list = implode(',', array_map('intval', $sku_ids));
    $search_without_and = preg_replace('/^\s*AND\s*/i', '', trim($search));
    if ($search_without_and === '') {
        return " AND {$wpdb->posts}.ID IN ({$id_list}) ";
    }

    return " AND (({$search_without_and}) OR {$wpdb->posts}.ID IN ({$id_list})) ";
}
add_filter('posts_search', 'tfa_ajax_search_query_includes_product_sku', 20, 2);

/**
 * Add product SKU to normal WordPress search results.
 * This covers submitted searches, not only the AJAX dropdown.
 */
function tfa_ajax_search_append_sku_to_search_result_title(string $block_content, array $block): string {
    if (is_admin() || !is_search() || ($block['blockName'] ?? '') !== 'core/post-title' || get_post_type() !== 'product' || !function_exists('wc_get_product')) {
        return $block_content;
    }

    $product = wc_get_product(get_the_ID());
    if (!$product || !$product->get_sku()) {
        return $block_content;
    }

    return $block_content . '<div class="iws-search-result-sku">' . esc_html__('SKU:', 'wp-theme') . ' <span>' . esc_html($product->get_sku()) . '</span></div>';
}
add_filter('render_block', 'tfa_ajax_search_append_sku_to_search_result_title', 20, 2);



/* AJAX search presentation is owned by child themes. */




