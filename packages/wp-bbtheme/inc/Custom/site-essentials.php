<?php
/**
 * Shared site essentials: sitemap, legal pages, consent banner, form privacy
 * links and installable PWA helpers used by every WP BBTheme child theme.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Keep parent-owned site essentials isolated from bespoke/legacy children.
 *
 * The maintained WP BBTheme suite opts into the parent cookie/PWA/form helpers.
 * Older projects such as Garilla keep their own newsletter, privacy and PWA UI
 * exactly as authored by the child theme. This mirrors the pre-3.8 parent
 * behaviour and prevents a parent Git pull from changing legacy presentation.
 */
function wp_theme_site_essentials_enabled() {
    $enabled = function_exists( 'wp_theme_is_current_suite_theme' )
        ? wp_theme_is_current_suite_theme()
        : ( 'wp-bbtheme' === (string) wp_get_theme()->get_stylesheet() );

    return (bool) apply_filters( 'wp_theme_site_essentials_enabled', $enabled );
}

function wp_theme_essential_page_url( $slug ) {
    $page = get_page_by_path( sanitize_title( $slug ) );
    return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/' . trim( $slug, '/' ) . '/' );
}

function wp_theme_sitemap_list_pages() {
    $pages = get_pages( array( 'sort_column' => 'menu_order,post_title', 'post_status' => 'publish' ) );
    if ( ! $pages ) return '';
    $by_parent = array();
    foreach ( $pages as $page ) $by_parent[ (int) $page->post_parent ][] = $page;
    $render = function( $parent = 0 ) use ( &$render, $by_parent ) {
        if ( empty( $by_parent[ $parent ] ) ) return '';
        $html = '<ul class="wp-theme-sitemap-list">';
        foreach ( $by_parent[ $parent ] as $page ) {
            $html .= '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>';
            $html .= $render( (int) $page->ID );
            $html .= '</li>';
        }
        return $html . '</ul>';
    };
    return $render( 0 );
}

function wp_theme_sitemap_shortcode() {
    $sections = array();
    $pages = wp_theme_sitemap_list_pages();
    if ( $pages ) $sections[] = array( __( 'Pages', 'wp-theme' ), $pages );

    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    foreach ( $post_types as $name => $object ) {
        if ( in_array( $name, array( 'attachment', 'page' ), true ) ) continue;
        $query = new WP_Query( array(
            'post_type' => $name, 'post_status' => 'publish', 'posts_per_page' => 60,
            'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true,
        ) );
        if ( ! $query->have_posts() ) continue;
        $items = '<ul class="wp-theme-sitemap-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $items .= '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
        }
        wp_reset_postdata();
        $items .= '</ul>';
        $sections[] = array( $object->labels->name ?: ucfirst( $name ), $items );
    }

    $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
    foreach ( $taxonomies as $taxonomy => $object ) {
        if ( in_array( $taxonomy, array( 'post_format', 'product_shipping_class' ), true ) ) continue;
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 40 ) );
        if ( is_wp_error( $terms ) || ! $terms ) continue;
        $items = '<ul class="wp-theme-sitemap-list">';
        foreach ( $terms as $term ) {
            $url = get_term_link( $term );
            if ( is_wp_error( $url ) ) continue;
            $items .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a></li>';
        }
        $items .= '</ul>';
        $sections[] = array( $object->labels->name ?: ucfirst( $taxonomy ), $items );
    }

    ob_start();
    ?>
    <main id="wp-theme-main" class="wp-theme-essential-page wp-theme-sitemap-page">
        <section class="wp-theme-essential-hero"><div class="container"><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Website directory', 'wp-theme' ); ?></p><h1><?php esc_html_e( 'Site map', 'wp-theme' ); ?></h1><p><?php esc_html_e( 'Find the main pages, articles, services and public content available on this website.', 'wp-theme' ); ?></p></div></section>
        <section class="wp-theme-essential-content"><div class="container"><div class="wp-theme-sitemap-grid">
        <?php foreach ( $sections as $section ) : ?>
            <section class="wp-theme-sitemap-card"><h2><?php echo esc_html( $section[0] ); ?></h2><?php echo wp_kses_post( $section[1] ); ?></section>
        <?php endforeach; ?>
        </div></div></section>
    </main>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_sitemap', 'wp_theme_sitemap_shortcode' );

function wp_theme_legal_page_content( $type ) {
    $name = get_bloginfo( 'name' ) ?: __( 'this website', 'wp-theme' );
    $contact = wp_theme_essential_page_url( 'contact' );
    $privacy = wp_theme_essential_page_url( 'privacy-policy' );
    $terms = wp_theme_essential_page_url( 'terms-and-conditions' );

    if ( 'privacy' === $type ) {
        $title = __( 'Privacy Policy', 'wp-theme' );
        $eyebrow = __( 'Privacy and data', 'wp-theme' );
        $sections = array(
            array( __( '1. About this policy', 'wp-theme' ), sprintf( __( '%s respects your privacy. This policy explains the types of personal information the website may receive, why it is used, and the choices available to you.', 'wp-theme' ), $name ) ),
            array( __( '2. Information you provide', 'wp-theme' ), __( 'When you use an enquiry, booking, reservation, quote, account or checkout form, the website may receive the details you choose to submit, such as your name, email address, telephone number, company details, delivery or billing details and the content of your message.', 'wp-theme' ) ),
            array( __( '3. Website and device information', 'wp-theme' ), __( 'The website may process technical information needed for security and operation, including browser type, device information, approximate location derived from an IP address, pages requested, error logs and cookie preferences.', 'wp-theme' ) ),
            array( __( '4. How information is used', 'wp-theme' ), __( 'Information may be used to respond to enquiries, provide requested services, administer orders or quotations, manage accounts, prevent abuse, maintain website security, improve the service and meet legal or regulatory obligations.', 'wp-theme' ) ),
            array( __( '5. Cookies and optional analytics', 'wp-theme' ), __( 'Essential cookies may be used where they are required for features such as sessions, security, baskets, preferences and account access. Optional analytics or marketing technologies should only be enabled in line with the cookie choices you make through the website consent controls.', 'wp-theme' ) ),
            array( __( '6. Sharing and processors', 'wp-theme' ), __( 'Information may be shared with trusted service providers only where needed to operate the website or deliver the requested service, for example hosting, email, payment, analytics, delivery or professional service providers. Those providers should process information under appropriate contractual and security safeguards.', 'wp-theme' ) ),
            array( __( '7. Retention and security', 'wp-theme' ), __( 'Personal information should be kept only for as long as reasonably necessary for the purpose for which it was collected, including accounting, warranty, dispute, fraud-prevention and legal requirements. Reasonable technical and organisational controls should be used to protect it.', 'wp-theme' ) ),
            array( __( '8. Your choices and rights', 'wp-theme' ), __( 'Depending on applicable law, you may have rights to request access, correction, deletion, restriction, portability or objection to certain uses of your personal information. You may also withdraw optional cookie consent at any time using the Manage cookies control.', 'wp-theme' ) ),
            array( __( '9. Contact', 'wp-theme' ), sprintf( __( 'For privacy questions or requests, please use the contact details on the %s page.', 'wp-theme' ), '<a href="' . esc_url( $contact ) . '">' . esc_html__( 'Contact', 'wp-theme' ) . '</a>' ) ),
        );
    } else {
        $title = __( 'Terms and Conditions', 'wp-theme' );
        $eyebrow = __( 'Website terms', 'wp-theme' );
        $sections = array(
            array( __( '1. Using this website', 'wp-theme' ), sprintf( __( 'These terms apply when you use the %s website. By continuing to use the website, you agree to use it lawfully and not to interfere with its security, availability or other users.', 'wp-theme' ), $name ) ),
            array( __( '2. Information and availability', 'wp-theme' ), __( 'We aim to keep website information useful and current, but content, availability, lead times, prices, specifications and service descriptions may change. Important commercial, technical or professional decisions should be confirmed directly with the relevant team before you rely on website information.', 'wp-theme' ) ),
            array( __( '3. Enquiries, bookings and quotations', 'wp-theme' ), __( 'Submitting an enquiry, booking request, reservation or quote request does not by itself create a binding contract unless the website clearly states otherwise. A contract is formed only when the relevant request or order is accepted in accordance with the applicable sales or service process.', 'wp-theme' ) ),
            array( __( '4. Ecommerce orders', 'wp-theme' ), __( 'Where WooCommerce purchasing is enabled, product availability, taxes, delivery charges, payment authorisation, cancellation and returns are handled through the checkout and the applicable order terms. Quote requests are separate from the normal shopping cart unless expressly converted into an accepted order.', 'wp-theme' ) ),
            array( __( '5. Product and service suitability', 'wp-theme' ), __( 'You are responsible for checking that a product, service, course, booking, property, vehicle, insurance package or other offering is suitable for your circumstances. Where specialist, regulated or safety-critical advice is required, obtain advice from a suitably qualified professional.', 'wp-theme' ) ),
            array( __( '6. Intellectual property', 'wp-theme' ), __( 'Unless stated otherwise, website text, design, graphics, photographs, video, code and other original material are protected by applicable intellectual-property rights. You may view and use the website for normal personal or business evaluation, but may not republish substantial material without permission.', 'wp-theme' ) ),
            array( __( '7. Third-party services and links', 'wp-theme' ), __( 'The website may link to or integrate third-party services such as payment providers, maps, video platforms, social networks or external booking systems. Those services may have their own terms and privacy practices.', 'wp-theme' ) ),
            array( __( '8. Liability', 'wp-theme' ), __( 'Nothing in these terms excludes liability that cannot lawfully be excluded. Subject to that, the website operator is not responsible for losses caused by misuse of the website, events outside reasonable control, or reliance on information that should reasonably have been verified before a material decision.', 'wp-theme' ) ),
            array( __( '9. Privacy and cookies', 'wp-theme' ), sprintf( __( 'Personal information is handled as described in the %s. Cookie choices can be reviewed using the Manage cookies control in the footer.', 'wp-theme' ), '<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy Policy', 'wp-theme' ) . '</a>' ) ),
            array( __( '10. Contact and updates', 'wp-theme' ), sprintf( __( 'Questions about these terms can be sent through the %s page. These terms may be updated when the website, services or legal requirements change.', 'wp-theme' ), '<a href="' . esc_url( $contact ) . '">' . esc_html__( 'Contact', 'wp-theme' ) . '</a>' ) ),
        );
    }

    $content = '<!-- wp:group {"className":"wp-theme-essential-page wp-theme-legal-page","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-essential-page wp-theme-legal-page">';
    $content .= '<!-- wp:group {"className":"wp-theme-essential-hero","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-essential-hero"><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} -->';
    $content .= '<!-- wp:paragraph {"className":"wp-theme-sector-eyebrow"} --><p class="wp-theme-sector-eyebrow">' . esc_html( $eyebrow ) . '</p><!-- /wp:paragraph -->';
    $content .= '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html( $title ) . '</h1><!-- /wp:heading -->';
    $content .= '<!-- wp:paragraph {"className":"wp-theme-sector-lead"} --><p class="wp-theme-sector-lead">' . esc_html__( 'A clear baseline policy for this website. Update the business-specific details before launch and obtain professional advice where required.', 'wp-theme' ) . '</p><!-- /wp:paragraph -->';
    $content .= '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
    $content .= '<!-- wp:group {"className":"wp-theme-essential-content","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-essential-content"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"justify-content-center"} --><!-- wp:wpbb/column {"xs":12,"lg":9} -->';
    foreach ( $sections as $section ) {
        $content .= '<!-- wp:group {"className":"wp-theme-legal-section","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-legal-section">';
        $content .= '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html( $section[0] ) . '</h2><!-- /wp:heading -->';
        $content .= '<!-- wp:paragraph --><p>' . wp_kses_post( $section[1] ) . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
    }
    $content .= '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group --></div><!-- /wp:group -->';
    return $content;
}

function wp_theme_seed_site_essential_pages() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    $pages = array(
        'site-map' => array( __( 'Site Map', 'wp-theme' ), '[wp_theme_sitemap]' ),
        'terms-and-conditions' => array( __( 'Terms and Conditions', 'wp-theme' ), wp_theme_legal_page_content( 'terms' ) ),
        'privacy-policy' => array( __( 'Privacy Policy', 'wp-theme' ), wp_theme_legal_page_content( 'privacy' ) ),
    );
    foreach ( $pages as $slug => $data ) {
        $existing = get_page_by_path( $slug );
        if ( $existing instanceof WP_Post ) {
            if ( get_post_meta( $existing->ID, '_wp_theme_essential_generated', true ) ) {
                wp_update_post( array( 'ID' => $existing->ID, 'post_title' => $data[0], 'post_content' => $data[1] ) );
            }
            continue;
        }
        $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $data[0], 'post_name' => $slug, 'post_content' => $data[1] ) );
        if ( $id && ! is_wp_error( $id ) ) update_post_meta( $id, '_wp_theme_essential_generated', 1 );
    }
    $privacy = get_page_by_path( 'privacy-policy' );
    if ( $privacy instanceof WP_Post && ! get_option( 'wp_page_for_privacy_policy' ) ) update_option( 'wp_page_for_privacy_policy', (int) $privacy->ID );
}

add_action( 'after_switch_theme', 'wp_theme_seed_site_essential_pages', 25 );

/**
 * Ensure upgrade installs receive essential pages even when the active child theme
 * is updated in-place and after_switch_theme therefore does not fire.
 */
function wp_theme_maybe_seed_site_essentials() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'edit_pages' ) ) return;
    $version = '3.8.1';
    if ( get_option( 'wp_theme_site_essentials_version' ) === $version ) return;
    wp_theme_seed_site_essential_pages();
    update_option( 'wp_theme_site_essentials_version', $version, false );
}
add_action( 'admin_init', 'wp_theme_maybe_seed_site_essentials', 20 );

function wp_theme_pwa_endpoint_url( $endpoint ) {
    return add_query_arg( 'wpbb-pwa', sanitize_key( $endpoint ), home_url( '/' ) );
}
function wp_theme_pwa_scope() {
    $path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    return trailingslashit( $path ?: '/' );
}
function wp_theme_project_theme_color() {
    $colors = array(
        'wp-bbtheme-child-business'          => '#5B5CF0',
        'wp-bbtheme-child-medicine'          => '#176B87',
        'wp-bbtheme-child-realestate'        => '#214E3B',
        'wp-bbtheme-child-woo-clouthes'      => '#772F3A',
        'wp-bbtheme-child-woo-tech-shop'     => '#1D63ED',
        'wp-bbtheme-child-travel'            => '#185B57',
        'wp-bbtheme-child-hotel'             => '#253E5B',
        'wp-bbtheme-child-elearning'         => '#4C45C6',
        'wp-bbtheme-child-automotive'        => '#C63D2F',
        'wp-bbtheme-child-insurance'         => '#1D4ED8',
        'wp-bbtheme-child-logistics'         => '#123C69',
        'wp-bbtheme-child-restaurant'        => '#6D2E2E',
        'wp-bbtheme-child-building-services' => '#1F4F62',
    );
    $stylesheet = get_stylesheet();
    $color = isset( $colors[ $stylesheet ] ) ? $colors[ $stylesheet ] : '#3155D9';
    $custom = sanitize_hex_color( get_theme_mod( 'wp_theme_sector_brand_color', '' ) );
    if ( $custom ) $color = $custom;
    return apply_filters( 'wp_theme_project_theme_color', $color, $stylesheet );
}

function wp_theme_project_icon_asset( $filename ) {
    $filename = ltrim( (string) $filename, '/' );
    $child_path = trailingslashit( get_stylesheet_directory() ) . 'assets/icons/' . $filename;
    if ( file_exists( $child_path ) ) {
        return trailingslashit( get_stylesheet_directory_uri() ) . 'assets/icons/' . rawurlencode( $filename );
    }
    $parent_path = trailingslashit( get_template_directory() ) . 'assets/icons/' . $filename;
    if ( file_exists( $parent_path ) ) {
        return trailingslashit( get_template_directory_uri() ) . 'assets/icons/' . rawurlencode( $filename );
    }
    return '';
}

function wp_theme_pwa_icon_url( $size = 192 ) {
    $size = absint( $size ) ?: 192;
    $site_icon = get_site_icon_url( $size );
    if ( $site_icon ) return $site_icon;
    $available = array( 32, 180, 192, 512 );
    $wanted = in_array( $size, $available, true ) ? $size : ( $size <= 180 ? 180 : ( $size <= 192 ? 192 : 512 ) );
    $icon = wp_theme_project_icon_asset( 'icon-' . $wanted . '.png' );
    if ( $icon ) return $icon;
    return get_template_directory_uri() . '/assets/img/logo.png';
}
function wp_theme_pwa_serve_endpoint() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    $endpoint = isset( $_GET['wpbb-pwa'] ) ? sanitize_key( wp_unslash( $_GET['wpbb-pwa'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! $endpoint ) return;
    nocache_headers(); status_header( 200 ); header( 'X-Content-Type-Options: nosniff' );
    if ( 'manifest' === $endpoint ) {
        header( 'Content-Type: application/manifest+json; charset=utf-8' );
        $profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();
        $manifest = array(
            'id' => home_url( '/' ), 'name' => get_bloginfo( 'name' ), 'short_name' => wp_html_excerpt( get_bloginfo( 'name' ), 24, '' ),
            'description' => $profile['footer_text'] ?? get_bloginfo( 'description' ), 'lang' => str_replace( '_', '-', get_locale() ),
            'start_url' => home_url( '/' ), 'scope' => wp_theme_pwa_scope(), 'display' => 'standalone', 'orientation' => 'any',
            'background_color' => '#ffffff', 'theme_color' => wp_theme_project_theme_color(),
            'icons' => array(
                array( 'src' => wp_theme_pwa_icon_url( 192 ), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
                array( 'src' => wp_theme_pwa_icon_url( 512 ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ),
            ),
        );
        echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); exit;
    }
    if ( 'service-worker' === $endpoint ) {
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: ' . wp_theme_pwa_scope() );
        $cache = 'wpbb-' . substr( md5( wp_get_theme()->get( 'Version' ) . home_url( '/' ) ), 0, 12 );
        $manifest = wp_theme_pwa_endpoint_url( 'manifest' );
        echo '"use strict";const C=' . wp_json_encode( $cache ) . ';const A=' . wp_json_encode( array( $manifest, wp_theme_pwa_icon_url( 192 ) ), JSON_UNESCAPED_SLASHES ) . ';self.addEventListener("install",e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(A)).catch(()=>undefined));self.skipWaiting()});self.addEventListener("activate",e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x.startsWith("wpbb-")&&x!==C).map(x=>caches.delete(x)))).then(()=>self.clients.claim()))});self.addEventListener("fetch",e=>{const r=e.request;if(r.method!=="GET"||r.mode==="navigate")return;const u=new URL(r.url);if(u.origin!==self.location.origin||u.pathname.includes("/wp-admin/")||u.pathname.includes("/wp-login.php"))return;if(!["style","script","image","font"].includes(r.destination))return;e.respondWith(caches.open(C).then(c=>c.match(r).then(hit=>hit||fetch(r).then(res=>{if(res&&res.ok&&res.type==="basic")c.put(r,res.clone());return res}))))});';
        exit;
    }
    status_header( 404 ); header( 'Content-Type: text/plain; charset=utf-8' ); echo 'Not found'; exit;
}
add_action( 'template_redirect', 'wp_theme_pwa_serve_endpoint', 0 );
add_filter( 'redirect_canonical', function( $url ) { return wp_theme_site_essentials_enabled() && isset( $_GET['wpbb-pwa'] ) ? false : $url; } ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

function wp_theme_cookie_consent_head() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    if ( is_admin() || is_feed() ) return;
    ?>
    <script id="wp-theme-consent-default">window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments)};(function(){var c=null;try{c=JSON.parse(localStorage.getItem('wpThemeConsent')||'null')}catch(e){}var a=!!(c&&c.analytics),m=!!(c&&c.marketing);window.gtag('consent','default',{analytics_storage:a?'granted':'denied',ad_storage:m?'granted':'denied',ad_user_data:m?'granted':'denied',ad_personalization:m?'granted':'denied',functionality_storage:'granted',security_storage:'granted',wait_for_update:500})})();</script>
    <?php
}
add_action( 'wp_head', 'wp_theme_cookie_consent_head', 1 );

function wp_theme_pwa_head() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    if ( is_admin() || is_feed() ) return;
    $theme_color = wp_theme_project_theme_color();
    echo '<link rel="manifest" href="' . esc_url( wp_theme_pwa_endpoint_url( 'manifest' ) ) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( wp_theme_pwa_icon_url( 180 ) ) . '">' . "\n";
    if ( ! has_site_icon() ) {
        echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( wp_theme_pwa_icon_url( 32 ) ) . '">' . "\n";
        $svg = wp_theme_project_icon_asset( 'favicon.svg' );
        if ( $svg ) echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( $svg ) . '">' . "\n";
    }
    echo '<meta name="theme-color" content="' . esc_attr( $theme_color ) . '"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default"><meta name="apple-mobile-web-app-title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'wp_theme_pwa_head', 4 );

function wp_theme_site_essentials_footer_markup() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    $mode = isset( $_GET['install'] ) ? sanitize_key( wp_unslash( $_GET['install'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! in_array( $mode, array( 'apple', 'android' ), true ) ) $mode = '';
    $privacy = wp_theme_essential_page_url( 'privacy-policy' );
    $terms = wp_theme_essential_page_url( 'terms-and-conditions' );
    ?>
    <div class="wp-theme-cookie-banner" data-cookie-banner hidden>
        <div class="wp-theme-cookie-banner__copy"><strong><?php esc_html_e( 'Your privacy choices', 'wp-theme' ); ?></strong><p><?php echo wp_kses_post( sprintf( __( 'We use essential cookies to run the website. Optional analytics and marketing cookies are used only with your permission. Read our %s.', 'wp-theme' ), '<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy Policy', 'wp-theme' ) . '</a>' ) ); ?></p></div>
        <div class="wp-theme-cookie-banner__actions"><button type="button" data-cookie-reject><?php esc_html_e( 'Reject optional', 'wp-theme' ); ?></button><button type="button" data-cookie-settings><?php esc_html_e( 'Cookie settings', 'wp-theme' ); ?></button><button type="button" class="is-primary" data-cookie-accept><?php esc_html_e( 'Accept all', 'wp-theme' ); ?></button></div>
    </div>
    <div class="wp-theme-cookie-settings" data-cookie-settings-dialog hidden role="dialog" aria-modal="true" aria-labelledby="wp-theme-cookie-title">
        <div class="wp-theme-cookie-settings__dialog"><button type="button" class="wp-theme-dialog-close" data-cookie-close aria-label="<?php esc_attr_e( 'Close cookie settings', 'wp-theme' ); ?>">&times;</button><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Privacy controls', 'wp-theme' ); ?></p><h2 id="wp-theme-cookie-title"><?php esc_html_e( 'Cookie settings', 'wp-theme' ); ?></h2>
        <div class="wp-theme-cookie-option"><div><strong><?php esc_html_e( 'Essential cookies', 'wp-theme' ); ?></strong><p><?php esc_html_e( 'Required for core website functions, security, sessions and saved privacy choices.', 'wp-theme' ); ?></p></div><span class="wp-theme-cookie-required"><?php esc_html_e( 'Always on', 'wp-theme' ); ?></span></div>
        <label class="wp-theme-cookie-option"><span><strong><?php esc_html_e( 'Analytics', 'wp-theme' ); ?></strong><small><?php esc_html_e( 'Helps understand how visitors use the website.', 'wp-theme' ); ?></small></span><input type="checkbox" data-cookie-analytics></label>
        <label class="wp-theme-cookie-option"><span><strong><?php esc_html_e( 'Marketing', 'wp-theme' ); ?></strong><small><?php esc_html_e( 'Allows optional advertising and campaign measurement technologies.', 'wp-theme' ); ?></small></span><input type="checkbox" data-cookie-marketing></label>
        <div class="wp-theme-cookie-settings__actions"><a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy Policy', 'wp-theme' ); ?></a><a href="<?php echo esc_url( $terms ); ?>"><?php esc_html_e( 'Terms & Conditions', 'wp-theme' ); ?></a><button type="button" class="is-primary" data-cookie-save><?php esc_html_e( 'Save choices', 'wp-theme' ); ?></button></div></div>
    </div>
    <div class="wp-theme-install-dialog" data-install-dialog data-install-mode="<?php echo esc_attr( $mode ); ?>"<?php echo $mode ? '' : ' hidden'; ?> role="dialog" aria-modal="true" aria-labelledby="wp-theme-install-title">
        <div class="wp-theme-install-dialog__panel"><button type="button" class="wp-theme-dialog-close" data-install-close aria-label="<?php esc_attr_e( 'Close installation instructions', 'wp-theme' ); ?>">&times;</button><img src="<?php echo esc_url( wp_theme_pwa_icon_url( 192 ) ); ?>" alt="" class="wp-theme-install-dialog__icon"><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Install this website', 'wp-theme' ); ?></p><h2 id="wp-theme-install-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2><p><?php esc_html_e( 'Add the website to your home screen for fast access and an app-like window. No app-store account is required.', 'wp-theme' ); ?></p>
        <ol data-install-apple<?php echo 'apple' === $mode ? '' : ' hidden'; ?>><li><?php esc_html_e( 'Open this page in Safari on your iPhone or iPad.', 'wp-theme' ); ?></li><li><?php esc_html_e( 'Tap the Share button in Safari.', 'wp-theme' ); ?></li><li><?php esc_html_e( 'Choose Add to Home Screen, then confirm Add.', 'wp-theme' ); ?></li></ol>
        <ol data-install-android<?php echo 'android' === $mode ? '' : ' hidden'; ?>><li><?php esc_html_e( 'Open this page in Chrome or another supported Android browser.', 'wp-theme' ); ?></li><li><?php esc_html_e( 'Use the Install button below when available, or open the browser menu.', 'wp-theme' ); ?></li><li><?php esc_html_e( 'Choose Install app or Add to Home screen and confirm.', 'wp-theme' ); ?></li></ol>
        <button type="button" class="wp-theme-install-primary" data-install-native hidden><?php esc_html_e( 'Install now', 'wp-theme' ); ?></button></div>
    </div>
    <?php
}
add_action( 'wp_footer', 'wp_theme_site_essentials_footer_markup', 80 );

function wp_theme_enqueue_site_essentials_assets() {
    if ( ! wp_theme_site_essentials_enabled() ) return;
    if ( is_admin() ) return;
    $js = get_template_directory() . '/assets/js/site-essentials.js';
    wp_enqueue_script( 'wp-theme-site-essentials', get_template_directory_uri() . '/assets/js/site-essentials.js', array(), file_exists( $js ) ? (string) filemtime( $js ) : wp_get_theme()->get( 'Version' ), true );
    wp_localize_script( 'wp-theme-site-essentials', 'WPThemeEssentials', array(
        'serviceWorker' => wp_theme_pwa_endpoint_url( 'service-worker' ), 'scope' => wp_theme_pwa_scope(),
        'privacyUrl' => wp_theme_essential_page_url( 'privacy-policy' ), 'termsUrl' => wp_theme_essential_page_url( 'terms-and-conditions' ),
        'labels' => array( 'privacy' => __( 'Privacy Policy', 'wp-theme' ), 'terms' => __( 'Terms & Conditions', 'wp-theme' ), 'formNotice' => __( 'By submitting this form, you agree that we may use the information provided to respond to your request.', 'wp-theme' ) ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'wp_theme_enqueue_site_essentials_assets', 60 );
