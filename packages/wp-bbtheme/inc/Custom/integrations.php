<?php
/**
 * Optional integrations shared by every BBTheme child.
 * The parent exposes markup/functions only; visual presentation remains in child SCSS.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_polylang_active() {
    return function_exists( 'pll_the_languages' );
}

/**
 * The language switcher is a site-level navigation feature. Existing projects
 * may have an old menu-level `language_bar` value saved as false, so Polylang
 * itself is the authoritative signal and Theme Settings owns the explicit opt-out.
 */
function wp_theme_language_switcher_enabled() {
    if ( ! wp_theme_polylang_active() ) {
        return false;
    }
    // This is a project-level header capability, not translatable content.
    // Existing projects have no saved value, so default to enabled.
    $saved = get_option( 'wp_theme_language_switcher_enabled', null );
    if ( null === $saved ) {
        return true;
    }
    return in_array( $saved, array( true, 1, '1', 'true', 'yes', 'on' ), true );
}

function wp_theme_mirror_language_switcher_enabled( $value, $post_id, $field ) {
    update_option(
        'wp_theme_language_switcher_enabled',
        in_array( $value, array( true, 1, '1', 'true', 'yes', 'on' ), true ) ? '1' : '0',
        false
    );
    return $value;
}
add_filter( 'acf/update_value/name=theme_language_switcher_enabled', 'wp_theme_mirror_language_switcher_enabled', 20, 3 );

/**
 * Languages supported by the shared header UI.
 * Polylang still owns which languages are actually enabled on a project.
 */
function wp_theme_supported_languages() {
    return array(
        'en' => array( 'name' => 'English', 'group' => 'default' ),
        'de' => array( 'name' => 'Deutsch', 'group' => 'europe' ),
        'es' => array( 'name' => 'Español', 'group' => 'europe' ),
        'fr' => array( 'name' => 'Français', 'group' => 'europe' ),
        'pl' => array( 'name' => 'Polski', 'group' => 'europe' ),
        'ru' => array( 'name' => 'Русский', 'group' => 'europe' ),
        'lv' => array( 'name' => 'Latviešu', 'group' => 'baltic' ),
        'lt' => array( 'name' => 'Lietuvių', 'group' => 'baltic' ),
        'et' => array( 'name' => 'Eesti', 'group' => 'baltic' ),
        'da' => array( 'name' => 'Dansk', 'group' => 'nordic' ),
        'sv' => array( 'name' => 'Svenska', 'group' => 'nordic' ),
        'nb' => array( 'name' => 'Norsk (Bokmål)', 'group' => 'nordic' ),
        'no' => array( 'name' => 'Norsk', 'group' => 'nordic' ),
        'fi' => array( 'name' => 'Suomi', 'group' => 'nordic' ),
        'is' => array( 'name' => 'Íslenska', 'group' => 'nordic' ),
    );
}

function wp_theme_supported_language_codes() {
    return array_keys( wp_theme_supported_languages() );
}

/**
 * Canonical multilingual Starter Setup configuration.
 * These values mirror Polylang's language fields: display name, slug, WordPress
 * locale, flag country and ordering. English is always the project default.
 */
function wp_theme_demo_polylang_languages() {
    return array(
        'en' => array( 'name' => 'English', 'locale' => 'en_GB', 'flag' => 'gb' ),
        'de' => array( 'name' => 'Deutsch', 'locale' => 'de_DE', 'flag' => 'de' ),
        'es' => array( 'name' => 'Español', 'locale' => 'es_ES', 'flag' => 'es' ),
        'fr' => array( 'name' => 'Français', 'locale' => 'fr_FR', 'flag' => 'fr' ),
        'pl' => array( 'name' => 'Polski', 'locale' => 'pl_PL', 'flag' => 'pl' ),
        'ru' => array( 'name' => 'Русский', 'locale' => 'ru_RU', 'flag' => 'ru' ),
        'lv' => array( 'name' => 'Latviešu', 'locale' => 'lv', 'flag' => 'lv' ),
        'lt' => array( 'name' => 'Lietuvių', 'locale' => 'lt_LT', 'flag' => 'lt' ),
        'et' => array( 'name' => 'Eesti', 'locale' => 'et', 'flag' => 'ee' ),
        'da' => array( 'name' => 'Dansk', 'locale' => 'da_DK', 'flag' => 'dk' ),
        'sv' => array( 'name' => 'Svenska', 'locale' => 'sv_SE', 'flag' => 'se' ),
        'nb' => array( 'name' => 'Norsk (Bokmål)', 'locale' => 'nb_NO', 'flag' => 'no' ),
        'fi' => array( 'name' => 'Suomi', 'locale' => 'fi', 'flag' => 'fi' ),
        'is' => array( 'name' => 'Íslenska', 'locale' => 'is_IS', 'flag' => 'is' ),
    );
}

function wp_theme_language_display_code( $slug ) {
    $slug = sanitize_key( (string) $slug );
    return 'nb' === $slug || 'no' === $slug ? 'NO' : strtoupper( $slug ?: 'en' );
}

function wp_theme_language_flag_emoji( $slug ) {
    $flags = array(
        'en'=>'🇬🇧','de'=>'🇩🇪','es'=>'🇪🇸','fr'=>'🇫🇷','pl'=>'🇵🇱','ru'=>'🇷🇺',
        'lv'=>'🇱🇻','lt'=>'🇱🇹','et'=>'🇪🇪','da'=>'🇩🇰','sv'=>'🇸🇪','nb'=>'🇳🇴','no'=>'🇳🇴','fi'=>'🇫🇮','is'=>'🇮🇸',
    );
    $slug = sanitize_key( (string) $slug );
    return $flags[ $slug ] ?? '🌐';
}

function wp_theme_language_flag_markup( $language, $slug ) {
    $flag = '';
    if ( is_array( $language ) ) {
        $flag = (string) ( $language['flag_url'] ?? '' );
        if ( ! $flag && ! empty( $language['flag'] ) ) {
            $raw_flag = (string) $language['flag'];
            if ( preg_match( '#^https?://#i', $raw_flag ) ) {
                $flag = $raw_flag;
            } elseif ( preg_match( '#src=["\']([^"\']+)["\']#i', $raw_flag, $match ) ) {
                $flag = $match[1];
            }
        }
    }
    if ( $flag ) {
        return '<img class="wp-theme-language-switcher__flag-image" src="' . esc_url( $flag ) . '" alt="" width="22" height="16" loading="lazy">';
    }
    return '<span class="wp-theme-language-switcher__flag-emoji" aria-hidden="true">' . esc_html( wp_theme_language_flag_emoji( $slug ) ) . '</span>';
}

/**
 * Polylang creates language-specific menu locations dynamically. Preserve the
 * Starter Setup English menus after languages are created by copying the base
 * location IDs into the registered English locations (for example location___en).
 */
function wp_theme_bind_demo_menu_locations_to_english() {
    $locations = (array) get_theme_mod( 'nav_menu_locations', array() );
    if ( empty( $locations ) ) {
        return 0;
    }

    $options = (array) get_option( 'polylang', array() );
    $theme = sanitize_key( (string) get_option( 'stylesheet', get_stylesheet() ) );
    if ( ! $theme ) {
        $theme = sanitize_key( get_stylesheet() );
    }
    if ( empty( $options['nav_menus'] ) || ! is_array( $options['nav_menus'] ) ) {
        $options['nav_menus'] = array();
    }
    if ( empty( $options['nav_menus'][ $theme ] ) || ! is_array( $options['nav_menus'][ $theme ] ) ) {
        $options['nav_menus'][ $theme ] = array();
    }

    $language_slugs = array_keys( wp_theme_demo_polylang_languages() );
    $changed = 0;
    foreach ( $locations as $location => $menu_id ) {
        $location = sanitize_key( (string) $location );
        $menu_id = absint( $menu_id );
        if ( ! $location || ! $menu_id || false !== strpos( $location, '___' ) ) {
            continue;
        }
        if ( empty( $options['nav_menus'][ $theme ][ $location ] ) || ! is_array( $options['nav_menus'][ $theme ][ $location ] ) ) {
            $options['nav_menus'][ $theme ][ $location ] = array();
        }
        // The base Starter Setup menu is the English source menu only.
        // Non-English languages receive their own menus after translated posts
        // have been created; mapping one English menu to every language is what
        // caused untranslated navigation on otherwise translated demo pages.
        if ( empty( $options['nav_menus'][ $theme ][ $location ]['en'] ) ) {
            $options['nav_menus'][ $theme ][ $location ]['en'] = $menu_id;
            $changed++;
        }
    }

    if ( $changed ) {
        update_option( 'polylang', $options );
    }
    return $changed;
}

/**
 * Starter content that should receive real Polylang translation relationships.
 * We keep the automatic scope deliberately narrow: managed pages, posts and
 * mega-menu layouts only. Sector CPT records remain editor-controlled.
 */
function wp_theme_demo_polylang_source_ids( $profile_id = '', $page_id = 0 ) {
    $ids = array();
    foreach ( array( $page_id, get_option( 'page_on_front' ), get_option( 'page_for_posts' ), get_option( 'wp_page_for_privacy_policy' ) ) as $candidate ) {
        $candidate = absint( $candidate );
        if ( $candidate ) $ids[] = $candidate;
    }

    $meta_query = array( 'relation' => 'OR' );
    if ( $profile_id ) $meta_query[] = array( 'key' => '_wp_theme_demo_profile', 'value' => $profile_id );
    $meta_query[] = array( 'key' => '_wp_theme_demo_generated', 'value' => '1' );
    $meta_query[] = array( 'key' => '_wp_theme_demo_blog', 'value' => '1' );
    $meta_query[] = array( 'key' => '_wp_theme_demo_blog_page', 'value' => '1' );
    $meta_query[] = array( 'key' => '_wp_theme_essential_generated', 'value' => '1' );
    $meta_query[] = array( 'key' => '_wp_theme_forms_generated', 'value' => '1' );

    $managed = get_posts( array(
        'post_type'      => array( 'page', 'post', 'megamenu' ),
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'no_found_rows'    => true,
        'meta_query'       => $meta_query,
    ) );
    $ids = array_merge( $ids, array_map( 'absint', (array) $managed ) );

    // Some older starter pages predate the generated markers. Include only
    // canonical starter slugs and never arbitrary client pages.
    foreach ( array( 'about','contact','services','work','insights','blog','site-map','terms-and-conditions','privacy-policy','forms' ) as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page instanceof WP_Post ) $ids[] = (int) $page->ID;
    }

    $ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
    return array_values( array_filter( $ids, static function( $post_id ) {
        if ( get_post_meta( $post_id, '_wp_theme_demo_translation_source', true ) ) return false;
        $post = get_post( $post_id );
        return $post instanceof WP_Post && in_array( $post->post_type, array( 'page','post','megamenu' ), true );
    } ) );
}

/** Localised admin titles/slugs for the most common starter pages. */
function wp_theme_demo_polylang_page_labels() {
    return array(
        'about' => array('de'=>'Über uns','es'=>'Nosotros','fr'=>'À propos','pl'=>'O nas','ru'=>'О нас','lv'=>'Par mums','lt'=>'Apie mus','et'=>'Meist','da'=>'Om os','sv'=>'Om oss','nb'=>'Om oss','fi'=>'Meistä','is'=>'Um okkur'),
        'contact' => array('de'=>'Kontakt','es'=>'Contacto','fr'=>'Contact','pl'=>'Kontakt','ru'=>'Контакты','lv'=>'Kontakti','lt'=>'Kontaktai','et'=>'Kontakt','da'=>'Kontakt','sv'=>'Kontakt','nb'=>'Kontakt','fi'=>'Yhteystiedot','is'=>'Hafa samband'),
        'services' => array('de'=>'Leistungen','es'=>'Servicios','fr'=>'Services','pl'=>'Usługi','ru'=>'Услуги','lv'=>'Pakalpojumi','lt'=>'Paslaugos','et'=>'Teenused','da'=>'Tjenester','sv'=>'Tjänster','nb'=>'Tjenester','fi'=>'Palvelut','is'=>'Þjónusta'),
        'work' => array('de'=>'Projekte','es'=>'Proyectos','fr'=>'Réalisations','pl'=>'Realizacje','ru'=>'Проекты','lv'=>'Darbi','lt'=>'Darbai','et'=>'Tööd','da'=>'Projekter','sv'=>'Projekt','nb'=>'Prosjekter','fi'=>'Työt','is'=>'Verkefni'),
        'insights' => array('de'=>'Einblicke','es'=>'Ideas','fr'=>'Perspectives','pl'=>'Wiedza','ru'=>'Материалы','lv'=>'Raksti','lt'=>'Įžvalgos','et'=>'Artiklid','da'=>'Indsigter','sv'=>'Insikter','nb'=>'Innsikt','fi'=>'Näkemyksiä','is'=>'Greinar'),
        'blog' => array('de'=>'Blog','es'=>'Blog','fr'=>'Blog','pl'=>'Blog','ru'=>'Блог','lv'=>'Blogs','lt'=>'Tinklaraštis','et'=>'Blogi','da'=>'Blog','sv'=>'Blogg','nb'=>'Blogg','fi'=>'Blogi','is'=>'Blogg'),
        'site-map' => array('de'=>'Sitemap','es'=>'Mapa del sitio','fr'=>'Plan du site','pl'=>'Mapa witryny','ru'=>'Карта сайта','lv'=>'Vietnes karte','lt'=>'Svetainės žemėlapis','et'=>'Saidikaart','da'=>'Sitemap','sv'=>'Webbplatskarta','nb'=>'Nettstedskart','fi'=>'Sivukartta','is'=>'Veftré'),
        'terms-and-conditions' => array('de'=>'Allgemeine Geschäftsbedingungen','es'=>'Términos y condiciones','fr'=>'Conditions générales','pl'=>'Regulamin','ru'=>'Условия использования','lv'=>'Noteikumi un nosacījumi','lt'=>'Taisyklės ir sąlygos','et'=>'Tingimused','da'=>'Vilkår og betingelser','sv'=>'Villkor','nb'=>'Vilkår og betingelser','fi'=>'Käyttöehdot','is'=>'Skilmálar'),
        'privacy-policy' => array('de'=>'Datenschutzerklärung','es'=>'Política de privacidad','fr'=>'Politique de confidentialité','pl'=>'Polityka prywatności','ru'=>'Политика конфиденциальности','lv'=>'Privātuma politika','lt'=>'Privatumo politika','et'=>'Privaatsuspoliitika','da'=>'Privatlivspolitik','sv'=>'Integritetspolicy','nb'=>'Personvernerklæring','fi'=>'Tietosuojakäytäntö','is'=>'Persónuverndarstefna'),
        'forms' => array('de'=>'Formulare','es'=>'Formularios','fr'=>'Formulaires','pl'=>'Formularze','ru'=>'Формы','lv'=>'Veidlapas','lt'=>'Formos','et'=>'Vormid','da'=>'Formularer','sv'=>'Formulär','nb'=>'Skjemaer','fi'=>'Lomakkeet','is'=>'Eyðublöð'),
    );
}

function wp_theme_demo_polylang_translation_title( WP_Post $source, $lang ) {
    $slug = sanitize_title( $source->post_name );
    $labels = wp_theme_demo_polylang_page_labels();
    if ( isset( $labels[ $slug ][ $lang ] ) ) return $labels[ $slug ][ $lang ];
    return $source->post_title;
}

/**
 * Lightweight translations for the managed starter content. These are not
 * applied to arbitrary client content: only auto-generated Polylang copies use
 * this dictionary. Child-theme copy that is not in the dictionary remains
 * editable and untouched.
 */
function wp_theme_demo_translation_dictionary( $lang ) {
    static $all = null;
    if ( null === $all ) {
        $path = trailingslashit( get_template_directory() ) . 'inc/demo-translations.json';
        $json = is_readable( $path ) ? file_get_contents( $path ) : '';
        $all = $json ? json_decode( $json, true ) : array();
        if ( ! is_array( $all ) ) $all = array();
    }
    $lang = sanitize_key( (string) $lang );
    $dictionary = isset( $all[ $lang ] ) && is_array( $all[ $lang ] ) ? $all[ $lang ] : array();
    // Child themes may ship sector-specific starter translations without
    // replacing the shared parent dictionary.
    $child_path = trailingslashit( get_stylesheet_directory() ) . 'inc/demo-translations.json';
    if ( get_stylesheet_directory() !== get_template_directory() && is_readable( $child_path ) ) {
        static $child_all = null;
        if ( null === $child_all ) {
            $child_json = file_get_contents( $child_path );
            $child_all = $child_json ? json_decode( $child_json, true ) : array();
            if ( ! is_array( $child_all ) ) $child_all = array();
        }
        if ( ! empty( $child_all[ $lang ] ) && is_array( $child_all[ $lang ] ) ) $dictionary = array_replace( $dictionary, $child_all[ $lang ] );
    }
    return $dictionary;
}

/** Category labels need noun-specific translations (general starter string replacements
 * can be verbs, and WordPress' default category must never leak into translated UI). */
function wp_theme_demo_taxonomy_label( $name, $lang ) {
    $lang = sanitize_key( (string) $lang );
    $key  = strtolower( trim( wp_strip_all_tags( (string) $name ) ) );
    $labels = array(
        'strategy' => array('en'=>'Strategy','de'=>'Strategie','es'=>'Estrategia','fr'=>'Stratégie','pl'=>'Strategia','ru'=>'Стратегия','lv'=>'Stratēģija','lt'=>'Strategija','et'=>'Strateegia','da'=>'Strategi','sv'=>'Strategi','nb'=>'Strategi','no'=>'Strategi','fi'=>'Strategia','is'=>'Stefnumótun'),
        'design' => array('en'=>'Design','de'=>'Design','es'=>'Diseño','fr'=>'Design','pl'=>'Projektowanie','ru'=>'Дизайн','lv'=>'Dizains','lt'=>'Dizainas','et'=>'Disain','da'=>'Design','sv'=>'Design','nb'=>'Design','no'=>'Design','fi'=>'Suunnittelu','is'=>'Hönnun'),
        'delivery' => array('en'=>'Delivery','de'=>'Umsetzung','es'=>'Entrega','fr'=>'Réalisation','pl'=>'Realizacja','ru'=>'Реализация','lv'=>'Piegāde','lt'=>'Įgyvendinimas','et'=>'Teostus','da'=>'Levering','sv'=>'Leverans','nb'=>'Leveranse','no'=>'Leveranse','fi'=>'Toteutus','is'=>'Framkvæmd'),
        'content' => array('en'=>'Content','de'=>'Inhalte','es'=>'Contenido','fr'=>'Contenu','pl'=>'Treści','ru'=>'Контент','lv'=>'Saturs','lt'=>'Turinys','et'=>'Sisu','da'=>'Indhold','sv'=>'Innehåll','nb'=>'Innhold','no'=>'Innhold','fi'=>'Sisältö','is'=>'Efni'),
        'guides' => array('en'=>'Guides','de'=>'Ratgeber','es'=>'Guías','fr'=>'Guides','pl'=>'Poradniki','ru'=>'Руководства','lv'=>'Ceļveži','lt'=>'Gidai','et'=>'Juhendid','da'=>'Guides','sv'=>'Guider','nb'=>'Guider','no'=>'Guider','fi'=>'Oppaat','is'=>'Leiðbeiningar'),
        'uncategorized' => array('en'=>'Uncategorized','de'=>'Nicht kategorisiert','es'=>'Sin categoría','fr'=>'Non classé','pl'=>'Bez kategorii','ru'=>'Без рубрики','lv'=>'Bez kategorijas','lt'=>'Be kategorijos','et'=>'Kategooriata','da'=>'Ikke kategoriseret','sv'=>'Okategoriserad','nb'=>'Ukategorisert','no'=>'Ukategorisert','fi'=>'Yleinen','is'=>'Óflokkað'),
        'uncategorised' => array('en'=>'Uncategorised','de'=>'Nicht kategorisiert','es'=>'Sin categoría','fr'=>'Non classé','pl'=>'Bez kategorii','ru'=>'Без рубрики','lv'=>'Bez kategorijas','lt'=>'Be kategorijos','et'=>'Kategooriata','da'=>'Ikke kategoriseret','sv'=>'Okategoriserad','nb'=>'Ukategorisert','no'=>'Ukategorisert','fi'=>'Yleinen','is'=>'Óflokkað'),
    );
    if ( isset( $labels[ $key ][ $lang ] ) ) return $labels[ $key ][ $lang ];
    if ( isset( $labels[ $key ]['en'] ) ) return $labels[ $key ]['en'];
    return wp_theme_demo_translate_starter_string( $name, $lang );
}

function wp_theme_demo_translate_starter_string( $value, $lang ) {
    $value = (string) $value;
    if ( '' === $value || 'en' === $lang ) return $value;
    $dictionary = wp_theme_demo_translation_dictionary( $lang );
    return $dictionary ? strtr( $value, $dictionary ) : $value;
}

function wp_theme_demo_copy_post_meta( $source_id, $target_id ) {
    $skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_theme_demo_translation_source', '_wp_theme_demo_translation_lang' );
    foreach ( (array) get_post_meta( $source_id ) as $key => $values ) {
        if ( in_array( $key, $skip, true ) || 0 === strpos( $key, '_pll_' ) ) continue;
        delete_post_meta( $target_id, $key );
        foreach ( (array) $values as $value ) add_post_meta( $target_id, $key, maybe_unserialize( $value ) );
    }
}

/**
 * Create linked starter translations. Content is copied as an editable starter
 * structure while the page title and shell controls are localised. Existing
 * editor-created translations are always preserved.
 */
/**
 * Return all auto-managed translations for one English source/language without
 * allowing Polylang's current-language query filter to hide orphan copies.
 */
function wp_theme_demo_managed_translation_ids( $source_id, $lang, $post_type = 'post' ) {
    $ids = get_posts( array(
        'post_type'        => $post_type,
        'post_status'      => array( 'publish', 'draft', 'private', 'pending', 'future' ),
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'orderby'          => 'ID',
        'order'            => 'ASC',
        'suppress_filters' => true,
        'no_found_rows'    => true,
        'meta_query'       => array(
            array( 'key' => '_wp_theme_demo_translation_source', 'value' => (int) $source_id, 'compare' => '=' ),
            array( 'key' => '_wp_theme_demo_translation_lang', 'value' => sanitize_key( $lang ), 'compare' => '=' ),
        ),
    ) );
    return array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
}

/** Remove duplicate starter translations created by older language-filtered repairs. */
function wp_theme_demo_dedupe_managed_translation( $source_id, $lang, $post_type = 'post' ) {
    $managed = wp_theme_demo_managed_translation_ids( $source_id, $lang, $post_type );
    $linked = function_exists( 'pll_get_post' ) ? absint( pll_get_post( (int) $source_id, $lang ) ) : 0;
    $keep = 0;

    if ( $linked && (int) get_post_meta( $linked, '_wp_theme_demo_translation_source', true ) === (int) $source_id ) {
        $keep = $linked;
    } elseif ( $linked ) {
        // A real editor-created translation wins; only remove auto-generated duplicates.
        foreach ( $managed as $duplicate_id ) wp_delete_post( $duplicate_id, true );
        return $linked;
    } elseif ( $managed ) {
        $keep = (int) reset( $managed );
    }

    foreach ( $managed as $duplicate_id ) {
        if ( $keep && (int) $duplicate_id === $keep ) continue;
        wp_delete_post( (int) $duplicate_id, true );
        $GLOBALS['wp_theme_demo_translation_deduped'] = 1 + (int) ( $GLOBALS['wp_theme_demo_translation_deduped'] ?? 0 );
    }
    return $keep;
}

function wp_theme_demo_create_polylang_translations( $source_ids, $language_slugs ) {
    if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) return 0;
    $created = 0;
    $repaired = 0;

    foreach ( (array) $source_ids as $source_id ) {
        $source = get_post( $source_id );
        if ( ! $source instanceof WP_Post ) continue;
        if ( function_exists( 'pll_get_post_language' ) ) {
            $source_lang = sanitize_key( (string) pll_get_post_language( $source_id, 'slug' ) );
            if ( $source_lang && 'en' !== $source_lang ) continue;
        }

        pll_set_post_language( $source_id, 'en' );
        $translations = array( 'en' => (int) $source_id );

        foreach ( (array) $language_slugs as $lang ) {
            $lang = sanitize_key( $lang );
            if ( ! $lang || 'en' === $lang ) continue;

            $existing_id = wp_theme_demo_dedupe_managed_translation( $source_id, $lang, $source->post_type );

            $page_title = wp_theme_demo_polylang_translation_title( $source, $lang );
            $title = $page_title !== $source->post_title ? $page_title : wp_theme_demo_translate_starter_string( $source->post_title, $lang );
            $translated_content = wp_theme_demo_translate_starter_string( $source->post_content, $lang );
            $translated_excerpt = wp_theme_demo_translate_starter_string( $source->post_excerpt, $lang );

            if ( $existing_id ) {
                $is_managed = (int) get_post_meta( $existing_id, '_wp_theme_demo_translation_source', true ) === (int) $source_id;
                if ( $is_managed ) {
                    wp_update_post( array(
                        'ID'           => $existing_id,
                        'post_title'   => $title,
                        'post_content' => $translated_content,
                        'post_excerpt' => $translated_excerpt,
                        'menu_order'   => $source->menu_order,
                    ) );
                    wp_theme_demo_copy_post_meta( $source_id, $existing_id );
                    update_post_meta( $existing_id, '_wp_theme_demo_translation_source', (int) $source_id );
                    update_post_meta( $existing_id, '_wp_theme_demo_translation_lang', $lang );
                    $repaired++;
                }
                pll_set_post_language( $existing_id, $lang );
                $translations[ $lang ] = $existing_id;
                continue;
            }

            $post_name = sanitize_title( $source->post_name . '-' . $lang );
            $target_id = wp_insert_post( array(
                'post_type'      => $source->post_type,
                'post_status'    => $source->post_status,
                'post_title'     => $title,
                'post_name'      => $post_name,
                'post_content'   => $translated_content,
                'post_excerpt'   => $translated_excerpt,
                'post_author'    => $source->post_author,
                'menu_order'     => $source->menu_order,
                'comment_status' => $source->comment_status,
                'ping_status'    => $source->ping_status,
            ), true );
            if ( is_wp_error( $target_id ) || ! $target_id ) continue;

            wp_theme_demo_copy_post_meta( $source_id, $target_id );
            update_post_meta( $target_id, '_wp_theme_demo_translation_source', (int) $source_id );
            update_post_meta( $target_id, '_wp_theme_demo_translation_lang', $lang );
            pll_set_post_language( $target_id, $lang );
            $translations[ $lang ] = (int) $target_id;
            $created++;
        }

        if ( count( $translations ) > 1 ) {
            pll_save_post_translations( $translations );
            clean_post_cache( $source_id );
            foreach ( $translations as $translation_id ) clean_post_cache( $translation_id );
        }
    }

    // Repair page parents after all translations exist.
    foreach ( (array) $source_ids as $source_id ) {
        $source = get_post( $source_id );
        if ( ! $source instanceof WP_Post || ! $source->post_parent ) continue;
        foreach ( (array) $language_slugs as $lang ) {
            if ( 'en' === $lang || ! function_exists( 'pll_get_post' ) ) continue;
            $translated = absint( pll_get_post( $source_id, $lang ) );
            $translated_parent = absint( pll_get_post( $source->post_parent, $lang ) );
            if ( $translated && $translated_parent ) wp_update_post( array( 'ID' => $translated, 'post_parent' => $translated_parent ) );
        }
    }

    update_option( 'wp_theme_demo_polylang_translation_repaired', $repaired, false );
    update_option( 'wp_theme_demo_polylang_translation_deduped', (int) ( $GLOBALS['wp_theme_demo_translation_deduped'] ?? 0 ), false );
    return $created + $repaired;
}

/** Assign managed starter categories/tags to English so Polylang has no orphan notice. */
function wp_theme_demo_assign_polylang_terms_to_english( $post_ids ) {
    if ( ! function_exists( 'pll_set_term_language' ) ) return 0;
    $term_ids = array();
    foreach ( (array) $post_ids as $post_id ) {
        foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
            $terms = wp_get_object_terms( (int) $post_id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $terms ) ) $term_ids = array_merge( $term_ids, array_map( 'absint', $terms ) );
        }
    }
    $changed = 0;
    foreach ( array_unique( array_filter( $term_ids ) ) as $term_id ) {
        $current = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term_id, 'slug' ) : '';
        if ( ! $current ) { pll_set_term_language( $term_id, 'en' ); $changed++; }
    }
    return $changed;
}


/**
 * Translate and link starter categories/tags, then assign the language-specific
 * terms to the translated posts. Polylang expects translations to be separate
 * term records linked with pll_save_term_translations().
 */
function wp_theme_demo_create_polylang_term_translations( $source_ids, $language_slugs ) {
    if ( ! function_exists( 'pll_set_term_language' ) || ! function_exists( 'pll_save_term_translations' ) || ! function_exists( 'pll_get_post' ) ) return 0;
    $created = 0;
    foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
        $source_terms = array();
        foreach ( (array) $source_ids as $source_id ) {
            $terms = wp_get_object_terms( (int) $source_id, $taxonomy );
            if ( is_wp_error( $terms ) ) continue;
            foreach ( $terms as $term ) if ( $term instanceof WP_Term ) $source_terms[ $term->term_id ] = $term;
        }
        foreach ( $source_terms as $term ) {
            $current = function_exists( 'pll_get_term_language' ) ? sanitize_key( (string) pll_get_term_language( $term->term_id, 'slug' ) ) : '';
            if ( ! $current ) pll_set_term_language( $term->term_id, 'en' );
            $translations = array( 'en' => (int) $term->term_id );
            foreach ( (array) $language_slugs as $lang ) {
                $lang = sanitize_key( (string) $lang );
                if ( ! $lang || 'en' === $lang ) continue;
                $target_id = function_exists( 'pll_get_term' ) ? absint( pll_get_term( $term->term_id, $lang ) ) : 0;
                $translated_name = wp_theme_demo_taxonomy_label( $term->name, $lang );
                if ( ! $translated_name ) $translated_name = $term->name;
                if ( ! $target_id ) {
                    $slug = sanitize_title( $term->slug . '-' . $lang );
                    $inserted = wp_insert_term( $translated_name, $taxonomy, array( 'slug' => $slug ) );
                    if ( ! is_wp_error( $inserted ) ) { $target_id = (int) $inserted['term_id']; $created++; }
                } else {
                    wp_update_term( $target_id, $taxonomy, array( 'name' => $translated_name ) );
                }
                if ( ! $target_id ) continue;
                pll_set_term_language( $target_id, $lang );
                $translations[ $lang ] = $target_id;
            }
            if ( count( $translations ) > 1 ) pll_save_term_translations( $translations );

            foreach ( (array) $source_ids as $source_id ) {
                $source_term_ids = wp_get_object_terms( (int) $source_id, $taxonomy, array( 'fields' => 'ids' ) );
                if ( is_wp_error( $source_term_ids ) || ! in_array( (int) $term->term_id, array_map( 'intval', $source_term_ids ), true ) ) continue;
                foreach ( $translations as $lang => $translated_term_id ) {
                    if ( 'en' === $lang ) continue;
                    $translated_post_id = absint( pll_get_post( (int) $source_id, $lang ) );
                    if ( ! $translated_post_id ) continue;
                    wp_set_object_terms( $translated_post_id, array( (int) $translated_term_id ), $taxonomy, true );
                }
            }
        }
    }
    // Exact taxonomy synchronisation: translated posts must not retain the
    // WordPress default English category alongside their translated terms.
    foreach ( (array) $source_ids as $source_id ) {
        foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
            $source_terms = wp_get_object_terms( (int) $source_id, $taxonomy );
            if ( is_wp_error( $source_terms ) ) continue;
            foreach ( (array) $language_slugs as $lang ) {
                $lang = sanitize_key( (string) $lang );
                if ( ! $lang || 'en' === $lang ) continue;
                $translated_post_id = absint( pll_get_post( (int) $source_id, $lang ) );
                if ( ! $translated_post_id ) continue;
                $translated_term_ids = array();
                foreach ( $source_terms as $source_term ) {
                    if ( ! $source_term instanceof WP_Term ) continue;
                    // Do not propagate WordPress' placeholder category into demo content.
                    if ( 'category' === $taxonomy && 'uncategorized' === sanitize_title( $source_term->slug ) ) continue;
                    $translated_term_id = function_exists( 'pll_get_term' ) ? absint( pll_get_term( $source_term->term_id, $lang ) ) : 0;
                    if ( $translated_term_id ) $translated_term_ids[] = $translated_term_id;
                }
                wp_set_object_terms( $translated_post_id, array_values( array_unique( $translated_term_ids ) ), $taxonomy, false );
            }
        }
    }
    return $created;
}

/** Build real language-specific classic menus for Polylang Starter Setup. */
function wp_theme_create_demo_polylang_menus( $profile = array() ) {
    if ( ! wp_theme_polylang_active() || ! function_exists( 'pll_get_post' ) ) return 0;
    $profile = is_array( $profile ) ? $profile : array();
    $profile_id = sanitize_key( $profile['id'] ?? get_option( 'wp_theme_active_demo_profile', 'business' ) );
    $profile_name = sanitize_text_field( $profile['name'] ?? ucfirst( $profile_id ) );
    $options = (array) get_option( 'polylang', array() );
    $theme = sanitize_key( (string) get_option( 'stylesheet', get_stylesheet() ) );
    if ( empty( $options['nav_menus'] ) || ! is_array( $options['nav_menus'] ) ) $options['nav_menus'] = array();
    if ( empty( $options['nav_menus'][ $theme ] ) || ! is_array( $options['nav_menus'][ $theme ] ) ) $options['nav_menus'][ $theme ] = array();

    $base = array(
        'wp-header-menu'     => $profile_name . ' — Header',
        'wp-header-top-menu' => $profile_name . ' — Utility',
        'wp-footer-menu'     => $profile_name . ' — Footer',
    );
    $count = 0;
    foreach ( $base as $location => $base_name ) {
        $source_menu = wp_get_nav_menu_object( $base_name );
        if ( ! $source_menu ) continue;
        if ( empty( $options['nav_menus'][ $theme ][ $location ] ) || ! is_array( $options['nav_menus'][ $theme ][ $location ] ) ) $options['nav_menus'][ $theme ][ $location ] = array();
        $options['nav_menus'][ $theme ][ $location ]['en'] = (int) $source_menu->term_id;
        $source_items = wp_get_nav_menu_items( $source_menu->term_id ) ?: array();
        foreach ( array_keys( wp_theme_demo_polylang_languages() ) as $lang ) {
            if ( 'en' === $lang ) continue;
            $menu_name = $base_name . ' [' . strtoupper( $lang ) . ']';
            $menu_obj = wp_get_nav_menu_object( $menu_name );
            $menu_id = $menu_obj ? (int) $menu_obj->term_id : wp_create_nav_menu( $menu_name );
            if ( is_wp_error( $menu_id ) || ! $menu_id ) continue;
            update_term_meta( $menu_id, '_wp_theme_demo_profile', $profile_id );
            update_term_meta( $menu_id, '_wp_theme_demo_managed', 1 );
            update_term_meta( $menu_id, '_wp_theme_demo_language', $lang );
            foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $old ) wp_delete_post( $old->ID, true );
            $item_map = array();
            foreach ( $source_items as $item ) {
                $args = array(
                    'menu-item-title'  => wp_theme_demo_translate_starter_string( $item->title, $lang ),
                    'menu-item-status' => 'publish',
                    'menu-item-type'   => $item->type,
                    'menu-item-object' => $item->object,
                    'menu-item-url'    => $item->url,
                    'menu-item-target' => $item->target,
                    'menu-item-classes'=> implode( ' ', (array) $item->classes ),
                );
                if ( 'post_type' === $item->type && $item->object_id ) {
                    $translated_object = absint( pll_get_post( (int) $item->object_id, $lang ) );
                    if ( $translated_object ) $args['menu-item-object-id'] = $translated_object;
                    else $args['menu-item-object-id'] = (int) $item->object_id;
                } else {
                    $args['menu-item-object-id'] = (int) $item->object_id;
                }
                if ( $item->menu_item_parent && isset( $item_map[ (int) $item->menu_item_parent ] ) ) $args['menu-item-parent-id'] = $item_map[ (int) $item->menu_item_parent ];
                $new_item = wp_update_nav_menu_item( $menu_id, 0, $args );
                if ( is_wp_error( $new_item ) ) continue;
                $item_map[ (int) $item->ID ] = (int) $new_item;
                $mega_id = absint( get_post_meta( $item->ID, '_wp_theme_mega_post_id', true ) ?: get_post_meta( $item->ID, 'mega_post_id', true ) );
                if ( $mega_id ) {
                    $translated_mega = absint( pll_get_post( $mega_id, $lang ) );
                    if ( $translated_mega ) {
                        update_post_meta( $new_item, '_wp_theme_mega_post_id', $translated_mega );
                        update_post_meta( $new_item, 'mega_post_id', $translated_mega );
                        if ( function_exists( 'update_field' ) ) update_field( 'mega_post_id', $translated_mega, $new_item );
                    }
                }
            }
            // Copy menu-level ACF/settings used by the header UX.
            foreach ( array( 'search_bar','customer_account','mini_cart','wishlist','mega_menu','last_button','light_dark','language_bar','sticky_header' ) as $setting ) {
                if ( function_exists( 'wp_theme_acf_ready' ) && wp_theme_acf_ready() && function_exists( 'update_field' ) ) {
                    $value = get_field( $setting, 'nav_menu_' . (int) $source_menu->term_id );
                    if ( null !== $value ) update_field( $setting, $value, 'nav_menu_' . (int) $menu_id );
                }
            }
            $options['nav_menus'][ $theme ][ $location ][ $lang ] = (int) $menu_id;
            $count++;
        }
    }
    update_option( 'polylang', $options );
    return $count;
}

/**
 * Configure the Starter Setup language set in Polylang without requiring the
 * administrator to manually repeat the Languages screen after every demo import.
 * Existing language records are preserved; only missing preset languages are added.
 */
function wp_theme_setup_demo_polylang_languages( $page_id = 0, $profile = array() ) {
    if ( ! wp_theme_polylang_active() || ! function_exists( 'PLL' ) ) {
        return array( 'configured' => false, 'reason' => 'polylang_inactive' );
    }

    $pll = PLL();
    if ( ! is_object( $pll ) || ! isset( $pll->model ) || ! is_object( $pll->model ) ) {
        return array( 'configured' => false, 'reason' => 'polylang_model_unavailable' );
    }

    // Polylang 3.7+ moved language CRUD to $model->languages while keeping
    // older methods behind __call(). Prefer the modern API and retain fallback
    // compatibility for older supported Polylang versions.
    $language_model = isset( $pll->model->languages ) && is_object( $pll->model->languages ) ? $pll->model->languages : $pll->model;
    $modern_model = method_exists( $language_model, 'get_list' ) && method_exists( $language_model, 'add' );
    $legacy_model = is_callable( array( $pll->model, 'get_languages_list' ) ) && is_callable( array( $pll->model, 'add_language' ) );
    if ( ! $modern_model && ! $legacy_model ) {
        return array( 'configured' => false, 'reason' => 'polylang_language_api_unavailable' );
    }

    try {
        $language_list = $modern_model ? $language_model->get_list() : $pll->model->get_languages_list();
    } catch ( Throwable $error ) {
        return array( 'configured' => false, 'reason' => 'polylang_languages_unavailable' );
    }

    $existing = array();
    foreach ( (array) $language_list as $language ) {
        $slug = sanitize_key( is_object( $language ) ? ( $language->slug ?? '' ) : ( $language['slug'] ?? '' ) );
        if ( $slug ) {
            $existing[ $slug ] = true;
        }
    }

    $existing_before_setup = $existing;
    $created = array();
    $order = 0;
    foreach ( wp_theme_demo_polylang_languages() as $slug => $definition ) {
        if ( ! isset( $existing[ $slug ] ) ) {
            $data = array(
                'name'           => $definition['name'],
                'slug'           => $slug,
                'locale'         => $definition['locale'],
                'rtl'            => false,
                'flag'           => $definition['flag'],
                'no_default_cat' => false,
                'term_group'     => $order,
            );
            try {
                $language = $modern_model ? $language_model->add( $data ) : $pll->model->add_language( $data );
            } catch ( Throwable $error ) {
                $language = false;
            }
            if ( $language && ! is_wp_error( $language ) ) {
                $created[] = $slug;
                $existing[ $slug ] = true;
            }
        }
        $order++;
    }

    if ( isset( $existing['en'] ) ) {
        try {
            if ( $modern_model && method_exists( $language_model, 'update_default' ) ) {
                $language_model->update_default( 'en' );
            } elseif ( is_callable( array( $pll->model, 'update_default_lang' ) ) ) {
                $pll->model->update_default_lang( 'en' );
            }
        } catch ( Throwable $error ) {
            // Keep demo import non-fatal if a future Polylang version changes internals.
        }
    }

    // A freshly imported starter reuses media across languages by default. Do
    // not override an existing multilingual project's media preference.
    if ( empty( $existing_before_setup ) ) {
        $options = (array) get_option( 'polylang', array() );
        if ( ! array_key_exists( 'media_support', $options ) ) {
            $options['media_support'] = false;
            update_option( 'polylang', $options );
        }
    }

    $menu_locations_bound = wp_theme_bind_demo_menu_locations_to_english();
    delete_transient( 'pll_activation_redirect' );

    update_option( 'wp_theme_language_switcher_enabled', '1', false );
    update_option( 'wp_theme_demo_language_bar_enabled', '1', false );

    $profile_id = sanitize_key( is_array( $profile ) ? ( $profile['id'] ?? '' ) : '' );
    if ( ! $profile_id ) {
        $profile_id = sanitize_key( (string) get_option( 'wp_theme_active_demo_profile', 'business' ) );
    }

    $post_ids = wp_theme_demo_polylang_source_ids( $profile_id, $page_id );
    if ( function_exists( 'pll_set_post_language' ) && isset( $existing['en'] ) ) {
        foreach ( $post_ids as $post_id ) {
            $current_lang = function_exists( 'pll_get_post_language' ) ? sanitize_key( (string) pll_get_post_language( $post_id, 'slug' ) ) : '';
            if ( ! $current_lang ) pll_set_post_language( $post_id, 'en' );
        }
    }
    $terms_assigned = wp_theme_demo_assign_polylang_terms_to_english( $post_ids );
    $translation_count = wp_theme_demo_create_polylang_translations( $post_ids, array_keys( wp_theme_demo_polylang_languages() ) );
    $term_translation_count = wp_theme_demo_create_polylang_term_translations( $post_ids, array_keys( wp_theme_demo_polylang_languages() ) );
    $language_menu_count = wp_theme_create_demo_polylang_menus( is_array( $profile ) ? $profile : array() );

    update_option( 'wp_theme_demo_polylang_setup_version', '3.8.10.6', false );
    update_option( 'wp_theme_demo_polylang_setup', array(
        'default'          => 'en',
        'created'          => $created,
        'profile'          => $profile_id,
        'assigned'         => count( $post_ids ),
        'translations'     => $translation_count,
        'terms_assigned'   => $terms_assigned,
        'menu_locations'   => $menu_locations_bound,
        'language_menus'   => $language_menu_count,
        'term_translations'=> $term_translation_count,
        'time'             => time(),
    ), false );

    if ( function_exists( 'flush_rewrite_rules' ) ) {
        flush_rewrite_rules( false );
    }

    return array( 'configured' => true, 'created' => $created, 'assigned' => count( $post_ids ), 'translations' => $translation_count, 'terms_assigned' => $terms_assigned, 'term_translations' => $term_translation_count, 'language_menus' => $language_menu_count, 'menu_locations' => $menu_locations_bound );
}
add_action( 'wp_theme_after_demo_import', 'wp_theme_setup_demo_polylang_languages', 5, 2 );

/**
 * Upgrade path for sites that imported Starter Setup before Polylang was enabled.
 * It runs once on an authorised admin request and never continually recreates
 * languages an editor later chooses to remove.
 */
function wp_theme_maybe_setup_demo_polylang_languages() {
    if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) || ! wp_theme_polylang_active() ) {
        return;
    }
    if ( '3.8.10.6' === (string) get_option( 'wp_theme_demo_polylang_setup_version', '' ) ) {
        return;
    }
    $profile = (string) get_option( 'wp_theme_active_demo_profile', '' );
    if ( ! $profile ) {
        return;
    }
    $front = absint( get_option( 'page_on_front' ) );
    wp_theme_setup_demo_polylang_languages( $front, array( 'id' => $profile ) );
}
add_action( 'admin_init', 'wp_theme_maybe_setup_demo_polylang_languages', 20 );

/**
 * Keep Starter Setup menu locations usable after Polylang starts filtering
 * theme locations. This is intentionally idempotent and never overwrites a
 * menu that an editor has already assigned to a specific language.
 */
function wp_theme_maybe_bind_demo_polylang_menu_locations() {
    if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) || ! wp_theme_polylang_active() ) {
        return;
    }
    if ( ! get_option( 'wp_theme_active_demo_profile', false ) ) {
        return;
    }
    wp_theme_bind_demo_menu_locations_to_english();
}
add_action( 'admin_init', 'wp_theme_maybe_bind_demo_polylang_menu_locations', 25 );


/**
 * Starter language bar preset.
 *
 * The compact utility bar is intentionally available before Polylang has been
 * fully configured so Starter Setup visibly documents the intended language
 * architecture. Configured Polylang languages become real links; preset-only
 * languages remain non-interactive labels until an administrator adds them.
 */
function wp_theme_language_bar_enabled() {
    // Respect the same explicit Theme Settings opt-out as the Polylang dropdown.
    $master = get_option( 'wp_theme_language_switcher_enabled', null );
    if ( null !== $master && ! in_array( $master, array( true, 1, '1', 'true', 'yes', 'on' ), true ) ) {
        return false;
    }
    $saved = get_option( 'wp_theme_demo_language_bar_enabled', null );
    if ( null !== $saved ) {
        return in_array( $saved, array( true, 1, '1', 'true', 'yes', 'on' ), true );
    }
    if ( wp_theme_polylang_active() ) {
        return wp_theme_language_switcher_enabled();
    }
    return (bool) get_option( 'wp_theme_active_demo_profile', false ) || (bool) get_page_by_path( 'demo-homepage' );
}

function wp_theme_language_bar_codes() {
    return array( 'en', 'de', 'es', 'fr', 'pl', 'ru', 'lv', 'lt', 'et', 'da', 'sv', 'no', 'fi', 'is' );
}

function wp_theme_polylang_languages_raw() {
    if ( ! wp_theme_polylang_active() ) {
        return array();
    }
    $languages = pll_the_languages( array(
        'raw' => 1,
        'hide_if_empty' => 0,
        'hide_if_no_translation' => 0,
    ) );
    return is_array( $languages ) ? $languages : array();
}

function wp_theme_render_language_bar() {
    // v3.8.10 keeps the compact flag dropdown with the same compact flag dropdown
    // used in the main header. Keep this function as a backwards-compatible alias.
    if ( ! wp_theme_language_bar_enabled() || ! wp_theme_polylang_active() ) {
        return '';
    }
    return wp_theme_render_language_switcher( array( 'variant' => 'utility' ) );
}

/**
 * Current frontend language slug with English as the safe project default.
 */
function wp_theme_current_language_slug() {
    if ( function_exists( 'pll_current_language' ) ) {
        $slug = sanitize_key( (string) pll_current_language( 'slug' ) );
        if ( $slug ) {
            return $slug;
        }
    }

    $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    $locale = strtolower( str_replace( '-', '_', (string) $locale ) );
    $prefix = substr( $locale, 0, 2 );
    if ( in_array( $prefix, wp_theme_supported_language_codes(), true ) ) {
        return $prefix;
    }
    return 'en';
}

/**
 * Small deterministic UI dictionary for shell controls.
 * This deliberately follows Polylang's active language rather than the global
 * WordPress locale, preventing an English page from inheriting a Latvian shell.
 */
function wp_theme_runtime_translate( $text ) {
    $text = (string) $text;
    if ( '' === $text ) return $text;
    $lang = function_exists( 'wp_theme_current_language_slug' ) ? wp_theme_current_language_slug() : 'en';
    if ( 'en' === $lang || '' === $lang ) return $text;
    return function_exists( 'wp_theme_demo_translate_starter_string' ) ? wp_theme_demo_translate_starter_string( $text, $lang ) : $text;
}

function wp_theme_ui_string( $key, $fallback = '' ) {
    $strings = array(
        'search' => array(
            'en'=>'Search','de'=>'Suche','es'=>'Buscar','fr'=>'Rechercher','pl'=>'Szukaj','ru'=>'Поиск','lv'=>'Meklēt','lt'=>'Paieška','et'=>'Otsi','da'=>'Søg','sv'=>'Sök','nb'=>'Søk','no'=>'Søk','fi'=>'Haku','is'=>'Leita',
        ),
        'search_site' => array(
            'en'=>'Search the site…','de'=>'Website durchsuchen…','es'=>'Buscar en el sitio…','fr'=>'Rechercher sur le site…','pl'=>'Przeszukaj witrynę…','ru'=>'Поиск по сайту…','lv'=>'Meklēt vietnē…','lt'=>'Ieškoti svetainėje…','et'=>'Otsi saidilt…','da'=>'Søg på siden…','sv'=>'Sök på webbplatsen…','nb'=>'Søk på nettstedet…','no'=>'Søk på nettstedet…','fi'=>'Hae sivustolta…','is'=>'Leita á vefnum…',
        ),
        'close_search' => array(
            'en'=>'Close search','de'=>'Suche schließen','es'=>'Cerrar búsqueda','fr'=>'Fermer la recherche','pl'=>'Zamknij wyszukiwanie','ru'=>'Закрыть поиск','lv'=>'Aizvērt meklēšanu','lt'=>'Uždaryti paiešką','et'=>'Sulge otsing','da'=>'Luk søgning','sv'=>'Stäng sökning','nb'=>'Lukk søk','no'=>'Lukk søk','fi'=>'Sulje haku','is'=>'Loka leit',
        ),
        'open_menu' => array(
            'en'=>'Open menu','de'=>'Menü öffnen','es'=>'Abrir menú','fr'=>'Ouvrir le menu','pl'=>'Otwórz menu','ru'=>'Открыть меню','lv'=>'Atvērt izvēlni','lt'=>'Atidaryti meniu','et'=>'Ava menüü','da'=>'Åbn menu','sv'=>'Öppna meny','nb'=>'Åpne meny','no'=>'Åpne meny','fi'=>'Avaa valikko','is'=>'Opna valmynd',
        ),
        'close_menu' => array(
            'en'=>'Close menu','de'=>'Menü schließen','es'=>'Cerrar menú','fr'=>'Fermer le menu','pl'=>'Zamknij menu','ru'=>'Закрыть меню','lv'=>'Aizvērt izvēlni','lt'=>'Uždaryti meniu','et'=>'Sulge menüü','da'=>'Luk menu','sv'=>'Stäng meny','nb'=>'Lukk meny','no'=>'Lukk meny','fi'=>'Sulje valikko','is'=>'Loka valmynd',
        ),
        'switch_dark' => array(
            'en'=>'Switch to dark mode','de'=>'Zum dunklen Modus wechseln','es'=>'Cambiar al modo oscuro','fr'=>'Passer en mode sombre','pl'=>'Przełącz na tryb ciemny','ru'=>'Переключить на тёмную тему','lv'=>'Pārslēgt uz tumšo režīmu','lt'=>'Perjungti į tamsų režimą','et'=>'Lülita tumedale režiimile','da'=>'Skift til mørk tilstand','sv'=>'Byt till mörkt läge','nb'=>'Bytt til mørk modus','no'=>'Bytt til mørk modus','fi'=>'Vaihda tummaan tilaan','is'=>'Skipta yfir í dökka stillingu',
        ),
        'switch_light' => array(
            'en'=>'Switch to light mode','de'=>'Zum hellen Modus wechseln','es'=>'Cambiar al modo claro','fr'=>'Passer en mode clair','pl'=>'Przełącz na tryb jasny','ru'=>'Переключить на светлую тему','lv'=>'Pārslēgt uz gaišo režīmu','lt'=>'Perjungti į šviesų režimą','et'=>'Lülita heledale režiimile','da'=>'Skift til lys tilstand','sv'=>'Byt till ljust läge','nb'=>'Bytt til lys modus','no'=>'Bytt til lys modus','fi'=>'Vaihda vaaleaan tilaan','is'=>'Skipta yfir í ljósa stillingu',
        ),
        'search_articles' => array(
            'en'=>'Search articles','de'=>'Artikel durchsuchen','es'=>'Buscar artículos','fr'=>'Rechercher des articles','pl'=>'Szukaj artykułów','ru'=>'Поиск статей','lv'=>'Meklēt rakstos','lt'=>'Ieškoti straipsnių','et'=>'Otsi artikleid','da'=>'Søg i artikler','sv'=>'Sök artiklar','nb'=>'Søk i artikler','no'=>'Søk i artikler','fi'=>'Hae artikkeleita','is'=>'Leita í greinum',
        ),
        'all' => array(
            'en'=>'All','de'=>'Alle','es'=>'Todos','fr'=>'Tous','pl'=>'Wszystkie','ru'=>'Все','lv'=>'Visi','lt'=>'Visi','et'=>'Kõik','da'=>'Alle','sv'=>'Alla','nb'=>'Alle','no'=>'Alle','fi'=>'Kaikki','is'=>'Allt',
        ),
        'reset' => array(
            'en'=>'Reset','de'=>'Zurücksetzen','es'=>'Restablecer','fr'=>'Réinitialiser','pl'=>'Resetuj','ru'=>'Сбросить','lv'=>'Atiestatīt','lt'=>'Atstatyti','et'=>'Lähtesta','da'=>'Nulstil','sv'=>'Återställ','nb'=>'Tilbakestill','no'=>'Tilbakestill','fi'=>'Nollaa','is'=>'Endurstilla',
        ),
        'filter_articles' => array(
            'en'=>'Filter articles by category','de'=>'Artikel nach Kategorie filtern','es'=>'Filtrar artículos por categoría','fr'=>'Filtrer les articles par catégorie','pl'=>'Filtruj artykuły według kategorii','ru'=>'Фильтровать статьи по категории','lv'=>'Filtrēt rakstus pēc kategorijas','lt'=>'Filtruoti straipsnius pagal kategoriją','et'=>'Filtreeri artikleid kategooria järgi','da'=>'Filtrer artikler efter kategori','sv'=>'Filtrera artiklar efter kategori','nb'=>'Filtrer artikler etter kategori','no'=>'Filtrer artikler etter kategori','fi'=>'Suodata artikkelit kategorian mukaan','is'=>'Sía greinar eftir flokki',
        ),
        'article' => array(
            'en'=>'article','de'=>'Artikel','es'=>'artículo','fr'=>'article','pl'=>'artykuł','ru'=>'статья','lv'=>'raksts','lt'=>'straipsnis','et'=>'artikkel','da'=>'artikel','sv'=>'artikel','nb'=>'artikkel','no'=>'artikkel','fi'=>'artikkeli','is'=>'grein',
        ),
        'articles' => array(
            'en'=>'articles','de'=>'Artikel','es'=>'artículos','fr'=>'articles','pl'=>'artykuły','ru'=>'статей','lv'=>'raksti','lt'=>'straipsniai','et'=>'artiklit','da'=>'artikler','sv'=>'artiklar','nb'=>'artikler','no'=>'artikler','fi'=>'artikkelia','is'=>'greinar',
        ),
        'languages' => array(
            'en'=>'Languages','de'=>'Sprachen','es'=>'Idiomas','fr'=>'Langues','pl'=>'Języki','ru'=>'Языки','lv'=>'Valodas','lt'=>'Kalbos','et'=>'Keeled','da'=>'Sprog','sv'=>'Språk','nb'=>'Språk','no'=>'Språk','fi'=>'Kielet','is'=>'Tungumál',
        ),
        'read_article' => array( 'en'=>'Read article','de'=>'Artikel lesen','es'=>'Leer artículo','fr'=>'Lire l’article','pl'=>'Czytaj artykuł','ru'=>'Читать статью','lv'=>'Lasīt rakstu','lt'=>'Skaityti straipsnį','et'=>'Loe artiklit','da'=>'Læs artikel','sv'=>'Läs artikel','nb'=>'Les artikkel','fi'=>'Lue artikkeli','is'=>'Lesa grein' ),
        'load_more' => array( 'en'=>'Load more','de'=>'Mehr laden','es'=>'Cargar más','fr'=>'Charger plus','pl'=>'Wczytaj więcej','ru'=>'Загрузить ещё','lv'=>'Ielādēt vairāk','lt'=>'Įkelti daugiau','et'=>'Laadi veel','da'=>'Indlæs flere','sv'=>'Ladda fler','nb'=>'Last inn flere','fi'=>'Lataa lisää','is'=>'Hlaða fleiri' ),
        'back_to_journal' => array( 'en'=>'Back to journal','de'=>'Zurück zum Journal','es'=>'Volver al diario','fr'=>'Retour au journal','pl'=>'Wróć do dziennika','ru'=>'Назад к журналу','lv'=>'Atpakaļ uz žurnālu','lt'=>'Grįžti į žurnalą','et'=>'Tagasi ajakirja','da'=>'Tilbage til journalen','sv'=>'Tillbaka till journalen','nb'=>'Tilbake til journalen','fi'=>'Takaisin julkaisuun','is'=>'Til baka í greinasafn' ),
        'no_articles' => array( 'en'=>'No articles found.','de'=>'Keine Artikel gefunden.','es'=>'No se encontraron artículos.','fr'=>'Aucun article trouvé.','pl'=>'Nie znaleziono artykułów.','ru'=>'Статьи не найдены.','lv'=>'Raksti nav atrasti.','lt'=>'Straipsnių nerasta.','et'=>'Artikleid ei leitud.','da'=>'Ingen artikler fundet.','sv'=>'Inga artiklar hittades.','nb'=>'Ingen artikler funnet.','fi'=>'Artikkeleita ei löytynyt.','is'=>'Engar greinar fundust.' ),
        'try_another_search' => array( 'en'=>'Try another search term or clear the category filter.','de'=>'Versuchen Sie einen anderen Suchbegriff oder löschen Sie den Kategoriefilter.','es'=>'Prueba otro término de búsqueda o borra el filtro de categoría.','fr'=>'Essayez un autre terme de recherche ou effacez le filtre de catégorie.','pl'=>'Spróbuj innego hasła lub wyczyść filtr kategorii.','ru'=>'Попробуйте другой запрос или сбросьте фильтр категории.','lv'=>'Izmēģiniet citu meklēšanas frāzi vai notīriet kategorijas filtru.','lt'=>'Išbandykite kitą paieškos frazę arba išvalykite kategorijos filtrą.','et'=>'Proovi teist otsingusõna või tühjenda kategooriafilter.','da'=>'Prøv et andet søgeord eller ryd kategorifilteret.','sv'=>'Prova ett annat sökord eller rensa kategorifiltret.','nb'=>'Prøv et annet søkeord eller fjern kategorifilteret.','fi'=>'Kokeile toista hakusanaa tai tyhjennä kategoriasuodatin.','is'=>'Prófaðu annað leitarorð eða hreinsaðu flokkasíuna.' ),
        'built_with_stack' => array( 'en'=>'Built with WordPress, Gutenberg and Bootstrap.','de'=>'Erstellt mit WordPress, Gutenberg und Bootstrap.','es'=>'Creado con WordPress, Gutenberg y Bootstrap.','fr'=>'Conçu avec WordPress, Gutenberg et Bootstrap.','pl'=>'Zbudowane z WordPress, Gutenberga i Bootstrapa.','ru'=>'Создано на WordPress, Gutenberg и Bootstrap.','lv'=>'Izveidots ar WordPress, Gutenberg un Bootstrap.','lt'=>'Sukurta su WordPress, Gutenberg ir Bootstrap.','et'=>'Loodud WordPressi, Gutenbergi ja Bootstrapiga.','da'=>'Bygget med WordPress, Gutenberg og Bootstrap.','sv'=>'Byggd med WordPress, Gutenberg och Bootstrap.','nb'=>'Bygget med WordPress, Gutenberg og Bootstrap.','no'=>'Bygget med WordPress, Gutenberg og Bootstrap.','fi'=>'Rakennettu WordPressillä, Gutenbergillä ja Bootstrapilla.','is'=>'Byggt með WordPress, Gutenberg og Bootstrap.' ),
        'view_all_articles' => array( 'en'=>'View all articles','de'=>'Alle Artikel ansehen','es'=>'Ver todos los artículos','fr'=>'Voir tous les articles','pl'=>'Zobacz wszystkie artykuły','ru'=>'Все статьи','lv'=>'Skatīt visus rakstus','lt'=>'Peržiūrėti visus straipsnius','et'=>'Vaata kõiki artikleid','da'=>'Se alle artikler','sv'=>'Visa alla artiklar','nb'=>'Se alle artikler','fi'=>'Näytä kaikki artikkelit','is'=>'Skoða allar greinar' ),
    );
    $lang = wp_theme_current_language_slug();
    if ( isset( $strings[ $key ][ $lang ] ) ) {
        return $strings[ $key ][ $lang ];
    }
    if ( isset( $strings[ $key ]['en'] ) ) {
        return $strings[ $key ]['en'];
    }
    return $fallback;
}

function wp_theme_register_polylang_strings() {
    if ( ! function_exists( 'pll_register_string' ) ) {
        return;
    }
    $strings = array(
        'Search' => 'Search',
        'Search the site' => 'Search the site…',
        'Menu' => 'Menu',
        'Account' => 'Account',
        'Cart' => 'Cart',
        'Subscribe' => 'Subscribe',
        'Newsletter' => 'Newsletter',
        'Book an appointment' => 'Book an appointment',
        'View all' => 'View all',
    );
    foreach ( $strings as $name => $value ) {
        pll_register_string( 'bbtheme_' . sanitize_key( $name ), $value, 'WP BBTheme', false );
    }
}
add_action( 'init', 'wp_theme_register_polylang_strings', 30 );

/**
 * Header/footer language menu. Polylang controls the enabled languages; the
 * theme only normalises ordering and presentation. English is always first.
 */
function wp_theme_render_language_switcher( $args = array() ) {
    $args = wp_parse_args( $args, array( 'expanded' => false, 'variant' => 'header' ) );
    if ( ! wp_theme_polylang_active() ) {
        return '';
    }
    $languages = wp_theme_polylang_languages_raw();
    if ( empty( $languages ) ) {
        return '';
    }

    $supported = wp_theme_supported_languages();
    $mobile_enabled = function_exists( 'wp_theme_acf_get' ) ? (bool) wp_theme_acf_get( 'theme_language_switcher_mobile', 'option', true ) : true;
    $order = array_flip( array_keys( $supported ) );
    usort( $languages, static function( $a, $b ) use ( $order ) {
        $a_slug = sanitize_key( $a['slug'] ?? '' );
        $b_slug = sanitize_key( $b['slug'] ?? '' );
        $a_rank = $order[ $a_slug ] ?? 999;
        $b_rank = $order[ $b_slug ] ?? 999;
        return $a_rank === $b_rank ? strcasecmp( (string) ( $a['name'] ?? $a_slug ), (string) ( $b['name'] ?? $b_slug ) ) : $a_rank <=> $b_rank;
    } );

    $current_slug = wp_theme_current_language_slug();
    $current_language = array();
    $items = '';
    foreach ( $languages as $language ) {
        $slug = sanitize_key( $language['slug'] ?? '' );
        if ( ! $slug ) {
            continue;
        }
        $name = sanitize_text_field( $language['name'] ?? ( $supported[ $slug ]['name'] ?? strtoupper( $slug ) ) );
        $url = esc_url( $language['url'] ?? home_url( '/' ) );
        $active = ! empty( $language['current_lang'] ) || $slug === $current_slug;
        if ( $active ) {
            $current_slug = $slug;
            $current_language = $language;
        }
        $items .= '<li' . ( $active ? ' class="is-current"' : '' ) . '><a href="' . $url . '" hreflang="' . esc_attr( $slug ) . '" lang="' . esc_attr( $slug ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '>'
            . wp_theme_language_flag_markup( $language, $slug )
            . '<span class="wp-theme-language-switcher__copy"><strong>' . esc_html( $name ) . '</strong><small>' . esc_html( wp_theme_language_display_code( $slug ) ) . '</small></span></a></li>';
    }
    if ( '' === $items ) {
        return '';
    }

    if ( empty( $current_language ) ) {
        foreach ( $languages as $language ) {
            if ( sanitize_key( $language['slug'] ?? '' ) === $current_slug ) {
                $current_language = $language;
                break;
            }
        }
    }
    $current_flag = wp_theme_language_flag_markup( $current_language, $current_slug );
    $current_code = wp_theme_language_display_code( $current_slug );
    $expanded = ! empty( $args['expanded'] );
    $label = wp_theme_ui_string( 'languages', 'Languages' );
    $variant = sanitize_html_class( (string) $args['variant'] );
    $classes = array( 'wp-theme-language-switcher', 'wp-theme-language-switcher--' . ( $variant ?: 'header' ) );
    if ( $expanded ) $classes[] = 'is-expanded';
    if ( ! $mobile_enabled ) $classes[] = 'is-mobile-hidden';

    return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-language-switcher>'
        . '<button class="wp-theme-language-switcher__toggle" type="button" aria-expanded="' . ( $expanded ? 'true' : 'false' ) . '" aria-haspopup="true" aria-label="' . esc_attr( $label ) . '">'
        . $current_flag . '<span class="wp-theme-language-code">' . esc_html( $current_code ) . '</span>'
        . '<svg class="wp-theme-language-switcher__chevron" viewBox="0 0 20 20" width="14" height="14" aria-hidden="true"><path d="m6 8 4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
        . '<ul class="wp-theme-language-switcher__menu"' . ( $expanded ? '' : ' hidden' ) . '>' . $items . '</ul></div>';
}

function wp_theme_ajax_search_markup( $args = array() ) {
    $defaults = array(
        'placeholder' => 'Search the site…',
        'post_types' => array( 'post', 'page' ),
        'limit' => 8,
    );
    $args = wp_parse_args( $args, $defaults );
    $registry = class_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance() : null;
    $block_name = '';
    if ( $registry && $registry->is_registered( 'tfa/ajax-search' ) ) {
        $block_name = 'tfa/ajax-search';
    } elseif ( $registry && $registry->is_registered( 'wpbb/ajax-search' ) ) {
        $block_name = 'wpbb/ajax-search';
    }
    if ( $block_name ) {
        $search_label = wp_theme_ui_string( 'search', 'Search' );
        $attrs = array(
            'title' => $search_label,
            'searchTitle' => $search_label,
            'label' => $search_label,
            'placeholder' => sanitize_text_field( $args['placeholder'] ),
            'buttonText' => $search_label,
            'resultsLimit' => max( 1, min( 20, absint( $args['limit'] ) ) ),
            'postTypes' => array_values( array_map( 'sanitize_key', (array) $args['post_types'] ) ),
            'showImage' => true,
            'showExcerpt' => true,
            'className' => 'wp-theme-global-ajax-search',
        );
        if ( 'tfa/ajax-search' === $block_name ) {
            $attrs['searchButton'] = false;
            $attrs['searchScopeControl'] = 'none';
        } else {
            $attrs['showButton'] = false;
        }
        return do_blocks( '<!-- wp:' . esc_attr( $block_name ) . ' ' . wp_json_encode( $attrs ) . ' /-->' );
    }
    $label = wp_theme_ui_string( 'search', 'Search' );
    $placeholder = sanitize_text_field( $args['placeholder'] ?: wp_theme_ui_string( 'search_site', 'Search the site…' ) );
    return '<div class="wp-theme-global-search-fallback"><form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '"><label><span class="screen-reader-text">' . esc_html( $label ) . '</span><input type="search" class="search-field" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( get_search_query() ) . '" name="s" autocomplete="off"></label><button type="submit" class="search-submit" aria-label="' . esc_attr( $label ) . '"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="screen-reader-text">' . esc_html( $label ) . '</span></button></form></div>';
}

function wp_theme_newsletter_markup( $args = array() ) {
    $title = $args['title'] ?? __( 'Useful updates, no noise.', 'wp-theme' );
    $text = $args['text'] ?? __( 'Occasional news, guides and ideas from our team.', 'wp-theme' );
    $form = '';
    foreach ( array( 'newsletter_form', 'newsletter', 'wp_newslatter_campaigns' ) as $tag ) {
        if ( shortcode_exists( $tag ) ) {
            $form = do_shortcode( '[' . $tag . ']' );
            break;
        }
    }
    if ( '' === trim( wp_strip_all_tags( $form ) ) && '' === trim( $form ) ) {
        $form = '<form class="wp-theme-newsletter-fallback" action="#" method="post"><label class="screen-reader-text" for="wp-theme-newsletter-email">' . esc_html__( 'Email address', 'wp-theme' ) . '</label><input id="wp-theme-newsletter-email" type="email" placeholder="' . esc_attr__( 'Email address', 'wp-theme' ) . '" disabled><button type="button" disabled>' . esc_html__( 'Subscribe', 'wp-theme' ) . '</button></form><small>' . esc_html__( 'Activate WP Newslatter Campaigns to enable subscriptions.', 'wp-theme' ) . '</small>';
    }
    return '<div class="wp-theme-footer-newsletter"><div class="wp-theme-footer-newsletter__copy"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $text ) . '</p></div><div class="wp-theme-footer-newsletter__form">' . $form . '</div></div>';
}

function wp_theme_header_search_post_types() {
    $types = array( 'post', 'page' );
    if ( post_type_exists( 'doctor' ) ) $types[] = 'doctor';
    if ( post_type_exists( 'property' ) ) $types[] = 'property';
    if ( post_type_exists( 'product' ) ) $types[] = 'product';
    return array_values( array_unique( apply_filters( 'wp_theme_header_search_post_types', $types ) ) );
}
