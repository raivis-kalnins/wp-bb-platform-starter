<?php
/**
 * WP BB Home & Garden v4.0.22
 * Bilingual product-guide demo content, extra Home & Garden demo products,
 * and an all-variation availability matrix for WooCommerce variable products.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V422_VERSION')) {
    define('WPBBSHOP_V422_VERSION', '4.0.22');
}

function wpbbshop_v422_lang() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if (is_string($lang) && $lang !== '') {
            return strtolower($lang) === 'lv' ? 'lv' : 'en';
        }
    }
    return stripos((string) get_locale(), 'lv') === 0 ? 'lv' : 'en';
}

function wpbbshop_v422_t($en, $lv) {
    return wpbbshop_v422_lang() === 'lv' ? $lv : $en;
}

function wpbbshop_v422_polylang_has($lang) {
    if (!function_exists('pll_languages_list')) { return true; }
    $langs = pll_languages_list(array('fields' => 'slug'));
    return empty($langs) || in_array($lang, (array) $langs, true);
}

function wpbbshop_v422_post_specs() {
    return array(
        'heating-plan' => array(
            'icon' => '♨',
            'en' => array(
                'title' => 'Planning a practical home heating system',
                'excerpt' => 'A practical guide to boilers, radiators, controls and circulation components for a balanced installation.',
                'content' => '<h2>Start with the room-by-room heat requirement</h2><p>A heating system is easier to choose when heat demand, emitter sizes and control zones are considered together. Match radiator or underfloor output to the room and size circulation components for the complete circuit rather than one appliance in isolation.</p><h3>Think in systems, not single products</h3><p>Boiler output, radiator size, pump duty, valves, pipework and thermostatic controls all affect comfort and efficiency. Product pages should make dimensions, connections, output and availability easy to compare.</p>',
            ),
            'lv' => array(
                'title' => 'Kā plānot praktisku mājas apkures sistēmu',
                'excerpt' => 'Praktisks ceļvedis par katliem, radiatoriem, vadību un cirkulācijas elementiem sabalansētai sistēmai.',
                'content' => '<h2>Sāc ar katras telpas siltuma nepieciešamību</h2><p>Apkures sistēmu ir vieglāk izvēlēties, ja vienlaikus tiek vērtēta telpu siltuma slodze, sildķermeņu izmēri un vadības zonas. Radiatoru vai grīdas apkures jaudu pieskaņo telpai un cirkulācijas elementus izvēlies visai sistēmai kopumā.</p><h3>Domā par sistēmu, ne tikai vienu preci</h3><p>Katla jauda, radiatoru izmēri, sūknis, vārsti, cauruļvadi un termostati kopā nosaka komfortu un efektivitāti. Tāpēc preču lapās ir svarīgi skaidri parādīt izmērus, pieslēgumus, jaudu un pieejamību.</p>',
            ),
        ),
        'radiator-size' => array(
            'icon' => '▦',
            'en' => array(
                'title' => 'Choosing radiator sizes and variants',
                'excerpt' => 'Why size, connection, price and stock should be visible for every radiator variation.',
                'content' => '<h2>One product can have many useful variants</h2><p>Panel radiators are a good example of why variable products matter. Width, height, panel type and connection style can change the price, output and availability. Showing every variation in one availability list makes the choice faster and reduces incorrect orders.</p>',
            ),
            'lv' => array(
                'title' => 'Radiatoru izmēru un variantu izvēle',
                'excerpt' => 'Kāpēc katram radiatora variantam jābūt redzamam izmēram, cenai, pieslēgumam un atlikumam.',
                'content' => '<h2>Vienai precei var būt daudzi noderīgi varianti</h2><p>Paneļu radiatori labi parāda, kāpēc variāciju preces ir svarīgas. Platums, augstums, tips un pieslēguma veids var mainīt cenu, jaudu un pieejamību. Ja visi varianti redzami vienā pieejamības sarakstā, izvēle ir ātrāka un samazinās kļūdainu pasūtījumu risks.</p>',
            ),
        ),
        'cultivator-guide' => array(
            'icon' => '⚙',
            'en' => array(
                'title' => 'Cultivator or mini tractor: what suits the job?',
                'excerpt' => 'Compare working width, transmission, attachments and weight before choosing garden machinery.',
                'content' => '<h2>Match the machine to the work</h2><p>For vegetable plots and soil preparation, a cultivator gives a compact solution. Larger properties can benefit from a mini tractor or ride-on machine when mowing, towing and seasonal attachments are used regularly.</p><p>Useful comparison points include working width, engine type, gearbox, reverse gear, attachment support, wheel size, machine weight and parts availability.</p>',
            ),
            'lv' => array(
                'title' => 'Kultivators vai mini traktors: ko izvēlēties?',
                'excerpt' => 'Pirms dārza tehnikas izvēles salīdzini darba platumu, transmisiju, agregātus un svaru.',
                'content' => '<h2>Pieskaņo tehniku veicamajam darbam</h2><p>Sakņu dārza un augsnes sagatavošanas darbiem kultivators ir kompakts risinājums. Lielākai teritorijai noder mini traktors vai braucams pļāvējs, ja regulāri izmanto pļaušanu, piekabi un sezonālos agregātus.</p><p>Salīdzini darba platumu, dzinēja tipu, pārnesumus, atpakaļgaitu, agregātu iespējas, riteņu izmēru, svaru un rezerves daļu pieejamību.</p>',
            ),
        ),
        'variation-stock' => array(
            'icon' => '✓',
            'en' => array(
                'title' => 'Why variation-level stock improves product pages',
                'excerpt' => 'Customers should see which size, package or configuration is actually available before ordering.',
                'content' => '<h2>Availability belongs next to the option</h2><p>A variable product can be in stock overall while one important size or package is sold out. A clear variation matrix makes stock, SKU and price visible for every choice instead of hiding this information until all dropdowns have been selected.</p>',
            ),
            'lv' => array(
                'title' => 'Kāpēc atlikums jāparāda katram preces variantam',
                'excerpt' => 'Pircējam jau pirms pasūtīšanas jāredz, kurš izmērs, komplekts vai konfigurācija tiešām ir pieejama.',
                'content' => '<h2>Pieejamībai jābūt blakus konkrētajam variantam</h2><p>Maināma prece kopumā var būt noliktavā, bet konkrēts izmērs vai komplekts var būt izpārdots. Skaidra variāciju tabula parāda atlikumu, SKU un cenu katrai izvēlei, negaidot, līdz aizpildīti visi izvēles lauki.</p>',
            ),
        ),
        'underfloor-heating' => array(
            'icon' => '⌂',
            'en' => array(
                'title' => 'Sizing electric underfloor heating mats',
                'excerpt' => 'Plan the usable heated area first, then select the nearest mat size and wattage.',
                'content' => '<h2>Measure the usable heated floor area</h2><p>Heating mats are normally selected by usable floor area, not the full room footprint. Fixed cabinets, baths and permanent fixtures should be accounted for before choosing the mat size.</p><p>Variable products are useful because each coverage size can have its own wattage, price and stock quantity.</p>',
            ),
            'lv' => array(
                'title' => 'Elektriskās grīdas apkures paklāja izmēra izvēle',
                'excerpt' => 'Vispirms aprēķini reāli apsildāmo laukumu un pēc tam izvēlies tuvāko paklāja izmēru un jaudu.',
                'content' => '<h2>Aprēķini reāli apsildāmo grīdas laukumu</h2><p>Apkures paklāju parasti izvēlas pēc izmantojamā grīdas laukuma, nevis visas telpas platības. Pirms izmēra izvēles jāņem vērā stacionāras mēbeles, vannas un citi elementi, zem kuriem apkures kabeli neizvieto.</p><p>Variāciju prece ir piemērota, jo katram laukuma izmēram var būt sava jauda, cena un noliktavas atlikums.</p>',
            ),
        ),
        'garden-maintenance' => array(
            'icon' => '✦',
            'en' => array(
                'title' => 'Seasonal checks for garden machinery',
                'excerpt' => 'A simple pre-season checklist for engines, filters, blades, belts and safety controls.',
                'content' => '<h2>Prepare before the busy season</h2><p>Check oil level and condition, air filtration, fuel system, cables, tyres, guards and fasteners before regular use. Cutting equipment should be inspected for damage and sharpened or replaced when required.</p>',
            ),
            'lv' => array(
                'title' => 'Sezonas pārbaudes dārza tehnikai',
                'excerpt' => 'Vienkāršs kontrolsaraksts dzinējam, filtriem, nažiem, siksnām un drošības elementiem.',
                'content' => '<h2>Sagatavo tehniku pirms aktīvās sezonas</h2><p>Pirms regulāras lietošanas pārbaudi eļļas līmeni un stāvokli, gaisa filtru, degvielas sistēmu, troses, riepas, aizsargus un stiprinājumus. Griešanas elementi jāpārbauda, jāuzasina vai vajadzības gadījumā jānomaina.</p>',
            ),
        ),
    );
}

function wpbbshop_v422_post_id($key, $lang) {
    $ids = get_posts(array(
        'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array(
            array('key' => '_wpbbshop_v422_guide_key', 'value' => $key),
            array('key' => '_wpbbshop_v422_lang', 'value' => $lang),
        ),
        'suppress_filters' => true,
    ));
    return $ids ? (int) $ids[0] : 0;
}

function wpbbshop_v422_guide_category($lang) {
    $name = $lang === 'lv' ? 'Preču ceļveži' : 'Product guides';
    $slug = $lang === 'lv' ? 'precu-celvedi' : 'product-guides';
    $term = get_term_by('slug', $slug, 'category');
    if (!$term) {
        $created = wp_insert_term($name, 'category', array('slug' => $slug));
        if (!is_wp_error($created)) { $term = get_term((int) $created['term_id'], 'category'); }
    }
    if ($term instanceof WP_Term && function_exists('pll_set_term_language') && wpbbshop_v422_polylang_has($lang)) {
        pll_set_term_language($term->term_id, $lang);
    }
    return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

function wpbbshop_v422_seed_posts() {
    $created = 0; $updated = 0; $pairs = array(); $order = 0;
    $category_ids = array('en' => wpbbshop_v422_guide_category('en'), 'lv' => wpbbshop_v422_guide_category('lv'));
    if (function_exists('pll_save_term_translations') && $category_ids['en'] && $category_ids['lv']) {
        pll_save_term_translations(array('en' => $category_ids['en'], 'lv' => $category_ids['lv']));
    }
    foreach (wpbbshop_v422_post_specs() as $key => $spec) {
        foreach (array('en','lv') as $lang) {
            $data = $spec[$lang];
            $id = wpbbshop_v422_post_id($key, $lang);
            $args = array(
                'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $data['title'],
                'post_excerpt' => $data['excerpt'], 'post_content' => $data['content'],
                'post_name' => sanitize_title($data['title']), 'comment_status' => 'closed',
                'post_category' => array_filter(array($category_ids[$lang])),
            );
            if ($id) {
                $args['ID'] = $id;
                $result = wp_update_post($args, true);
                if (!is_wp_error($result)) { $updated++; }
            } else {
                $result = wp_insert_post($args, true);
                if (!is_wp_error($result)) { $id = (int) $result; $created++; }
            }
            if (!$id || is_wp_error($result)) { continue; }
            update_post_meta($id, '_wpbbshop_v422_guide', '1');
            update_post_meta($id, '_wpbbshop_v422_guide_key', $key);
            update_post_meta($id, '_wpbbshop_v422_lang', $lang);
            update_post_meta($id, '_wpbbshop_v422_icon', $spec['icon']);
            update_post_meta($id, '_wpbbshop_v422_order', $order);
            if (function_exists('pll_set_post_language') && wpbbshop_v422_polylang_has($lang)) {
                pll_set_post_language($id, $lang);
            }
            $pairs[$key][$lang] = $id;
        }
        $order++;
    }
    if (function_exists('pll_save_post_translations')) {
        foreach ($pairs as $pair) {
            if (!empty($pair['en']) && !empty($pair['lv'])) {
                pll_save_post_translations(array('en' => $pair['en'], 'lv' => $pair['lv']));
            }
        }
    }
    return array('created' => $created, 'updated' => $updated);
}

function wpbbshop_v422_base_category_id($slug, $lang) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term || is_wp_error($term)) { return 0; }
    if ($lang === 'lv' && function_exists('pll_get_term')) {
        $translated = absint(pll_get_term($term->term_id, 'lv'));
        if ($translated) { return $translated; }
    }
    return (int) $term->term_id;
}

function wpbbshop_v422_child_product_category($parent_slug, $lang, $name, $slug) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $parent = wpbbshop_v422_base_category_id($parent_slug, $lang);
        $created = wp_insert_term($name, 'product_cat', array('slug' => $slug, 'parent' => $parent));
        if (!is_wp_error($created)) { $term = get_term((int) $created['term_id'], 'product_cat'); }
    }
    if ($term instanceof WP_Term && function_exists('pll_set_term_language') && wpbbshop_v422_polylang_has($lang)) {
        pll_set_term_language($term->term_id, $lang);
    }
    return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

function wpbbshop_v422_showcase_categories() {
    $cats = array(
        'en' => array(
            'garden' => wpbbshop_v422_base_category_id('garden-machinery', 'en'),
            'heating_parent' => wpbbshop_v422_base_category_id('plumbing-heating', 'en'),
            'heating' => wpbbshop_v422_child_product_category('plumbing-heating', 'en', 'Heating systems', 'heating-systems'),
            'tillers' => wpbbshop_v422_child_product_category('garden-machinery', 'en', 'Cultivators & mini tractors', 'cultivators-mini-tractors'),
        ),
        'lv' => array(
            'garden' => wpbbshop_v422_base_category_id('garden-machinery', 'lv'),
            'heating_parent' => wpbbshop_v422_base_category_id('plumbing-heating', 'lv'),
            'heating' => wpbbshop_v422_child_product_category('plumbing-heating', 'lv', 'Apkures sistēmas', 'apkures-sistemas'),
            'tillers' => wpbbshop_v422_child_product_category('garden-machinery', 'lv', 'Kultivatori un mini traktori', 'kultivatori-mini-traktori'),
        ),
    );
    if (function_exists('pll_save_term_translations')) {
        if ($cats['en']['heating'] && $cats['lv']['heating']) {
            pll_save_term_translations(array('en' => $cats['en']['heating'], 'lv' => $cats['lv']['heating']));
        }
        if ($cats['en']['tillers'] && $cats['lv']['tillers']) {
            pll_save_term_translations(array('en' => $cats['en']['tillers'], 'lv' => $cats['lv']['tillers']));
        }
    }
    return $cats;
}

function wpbbshop_v422_product_id($key, $lang) {
    $ids = get_posts(array(
        'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array(
            array('key' => '_wpbbshop_v422_demo_key', 'value' => $key),
            array('key' => '_wpbbshop_v422_lang', 'value' => $lang),
        ),
        'suppress_filters' => true,
    ));
    return $ids ? (int) $ids[0] : 0;
}

function wpbbshop_v422_image_url($file) {
    $path = get_stylesheet_directory() . '/' . ltrim($file, '/');
    if (!is_readable($path)) { return ''; }
    return get_stylesheet_directory_uri() . '/' . ltrim($file, '/');
}

function wpbbshop_v422_product_specs($lang, $cats) {
    $lv = $lang === 'lv';
    $heating = array_filter(array($cats[$lang]['heating'], $cats[$lang]['heating_parent']));
    $garden = array_filter(array($cats[$lang]['garden'], $cats[$lang]['tillers']));
    return array(
        array(
            'type' => 'variable', 'key' => 'panel-radiator-22', 'sku' => 'HG-SHOWCASE-RAD22-' . strtoupper($lang),
            'name' => $lv ? 'Demo paneļu radiators Type 22' : 'Demo Panel Radiator Type 22',
            'categories' => $heating, 'image' => 'assets/demo-products/oil-pump-v43-v46.webp',
            'short' => $lv ? 'Variāciju radiators ar atsevišķu cenu un atlikumu katram izmēram.' : 'Variable demo radiator with size-specific price and stock availability.',
            'description' => $lv ? '<p>Demo ūdens apkures paneļu radiators variāciju izvēles, atlikumu un cenu demonstrācijai.</p>' : '<p>Demo hydronic panel radiator for testing variation selection, stock display and product comparison.</p>',
            'attribute_name' => $lv ? 'Izmērs' : 'Size',
            'variations' => array(
                array('label'=>'600 × 800 mm','price'=>89,'stock'=>14), array('label'=>'600 × 1000 mm','price'=>119,'stock'=>6),
                array('label'=>'600 × 1200 mm','price'=>139,'stock'=>0), array('label'=>'600 × 1600 mm','price'=>179,'stock'=>3),
            ),
        ),
        array(
            'type' => 'variable', 'key' => 'underfloor-mat', 'sku' => 'HG-SHOWCASE-UFH-' . strtoupper($lang),
            'name' => $lv ? 'Demo elektriskās grīdas apkures paklājs' : 'Demo Electric Underfloor Heating Mat',
            'categories' => $heating, 'image' => 'assets/demo-products/trimmer-line-v45-v46.webp',
            'short' => $lv ? 'Grīdas apkures paklājs ar atsevišķu pieejamību katrai platībai.' : 'Variable floor-heating mat with coverage-specific stock.',
            'description' => $lv ? '<p>Demo elektriskās grīdas apkures paklājs, kur katram laukuma izmēram ir sava jauda, cena un atlikums.</p>' : '<p>Demo electric underfloor heating mat with separate power, price and stock for every coverage option.</p>',
            'attribute_name' => $lv ? 'Platība' : 'Coverage',
            'variations' => array(
                array('label'=>'2 m² / 320 W','price'=>64,'stock'=>18), array('label'=>'4 m² / 640 W','price'=>99,'stock'=>9),
                array('label'=>'6 m² / 960 W','price'=>139,'stock'=>2), array('label'=>'10 m² / 1600 W','price'=>199,'stock'=>0),
            ),
        ),
        array(
            'type' => 'variable', 'key' => 'cultivator-1050', 'sku' => 'HG-SHOWCASE-CULT1050-' . strtoupper($lang),
            'name' => $lv ? 'Demo benzīna kultivators 1050 Pro' : 'Demo Petrol Cultivator 1050 Pro',
            'categories' => $garden, 'image' => 'assets/img/hero-mower.jpg',
            'short' => $lv ? 'Jaudīgs demo kultivators ar dažādiem darba komplektiem.' : 'Heavy-duty demo cultivator with equipment-package variations.',
            'description' => $lv ? '<p>Četrtaktu demo kultivators ar plašu darba zonu, uz priekšu/atpakaļ transmisiju un papildaprīkojuma komplektiem.</p>' : '<p>Demo four-stroke cultivator with a wide working range, forward/reverse transmission and optional garden attachments.</p>',
            'attribute_name' => $lv ? 'Komplekts' : 'Package',
            'variations' => $lv ? array(
                array('label'=>'Pamata mašīna','price'=>679,'stock'=>7), array('label'=>'Frēzēšanas komplekts','price'=>829,'stock'=>5),
                array('label'=>'Arkla komplekts','price'=>929,'stock'=>2), array('label'=>'Pilns dārza komplekts','price'=>1199,'stock'=>0),
            ) : array(
                array('label'=>'Base machine','price'=>679,'stock'=>7), array('label'=>'Tilling kit','price'=>829,'stock'=>5),
                array('label'=>'Ploughing kit','price'=>929,'stock'=>2), array('label'=>'Full garden kit','price'=>1199,'stock'=>0),
            ),
        ),
        array(
            'type' => 'variable', 'key' => 'mini-tractor-15hp', 'sku' => 'HG-SHOWCASE-MINITRACTOR-' . strtoupper($lang),
            'name' => $lv ? 'Demo kompaktais mini traktors 15 HP' : 'Demo Compact Mini Tractor 15 HP',
            'categories' => $garden, 'image' => 'assets/img/hero-mower.jpg',
            'short' => $lv ? 'Kompakts mini traktors ar pļaušanas un sezonālo agregātu variantiem.' : 'Compact mini tractor with mower and seasonal attachment variants.',
            'description' => $lv ? '<p>Demo mini traktors ar elektrisko startu, sakabes punktu un pļaušanas/agregātu komplektiem lielākiem dārziem.</p>' : '<p>Demo compact mini tractor with electric start, tow hitch and mower/attachment packages for larger gardens.</p>',
            'attribute_name' => $lv ? 'Aprīkojums' : 'Equipment',
            'variations' => $lv ? array(
                array('label'=>'Pamata traktors','price'=>3290,'stock'=>3), array('label'=>'105 cm pļaušanas bloks','price'=>3790,'stock'=>2),
                array('label'=>'Pļaušana + piekabe','price'=>4190,'stock'=>1), array('label'=>'Pilns sezonas komplekts','price'=>4890,'stock'=>0),
            ) : array(
                array('label'=>'Base tractor','price'=>3290,'stock'=>3), array('label'=>'105 cm mower deck','price'=>3790,'stock'=>2),
                array('label'=>'Mower + trailer','price'=>4190,'stock'=>1), array('label'=>'Full seasonal kit','price'=>4890,'stock'=>0),
            ),
        ),
        array(
            'type' => 'variable', 'key' => 'towel-radiator', 'sku' => 'HG-SHOWCASE-TOWEL-' . strtoupper($lang),
            'name' => $lv ? 'Demo dvieļu radiators' : 'Demo Towel Radiator',
            'categories' => $heating, 'image' => 'assets/demo-products/tool-set.webp',
            'short' => $lv ? 'Vannas istabas radiators ar izmēru variācijām un atsevišķu atlikumu.' : 'Bathroom radiator with selectable sizes and variation-level stock.',
            'description' => $lv ? '<p>Demo dvieļu radiators vannas istabai ar vairākiem izmēriem un individuāliem noliktavas atlikumiem.</p>' : '<p>Demo bathroom towel radiator with multiple sizes and individual stock quantities.</p>',
            'attribute_name' => $lv ? 'Izmērs' : 'Size',
            'variations' => array(
                array('label'=>'500 × 800 mm','price'=>79,'stock'=>11), array('label'=>'500 × 1200 mm','price'=>109,'stock'=>4), array('label'=>'600 × 1500 mm','price'=>149,'stock'=>0),
            ),
        ),
        array(
            'type' => 'simple', 'key' => 'boiler-24kw', 'sku' => 'HG-SHOWCASE-BOILER24-' . strtoupper($lang),
            'name' => $lv ? 'Demo kondensācijas katls 24 kW' : 'Demo Condensing Boiler 24 kW', 'price' => 799, 'stock' => 4,
            'categories' => $heating, 'image' => 'assets/demo-products/generator.webp',
            'short' => $lv ? 'Kompakts demo katls apkures sistēmu kataloga testiem.' : 'Compact demo boiler for heating-system catalogue layouts.',
            'description' => $lv ? '<p>Demo 24 kW kondensācijas katls ar modulējošu jaudu un digitālo vadību.</p>' : '<p>Demo 24 kW condensing boiler with modulating output and digital controls.</p>',
        ),
        array(
            'type' => 'simple', 'key' => 'pump-25-60', 'sku' => 'HG-SHOWCASE-PUMP2560-' . strtoupper($lang),
            'name' => $lv ? 'Demo cirkulācijas sūknis 25-60' : 'Demo Circulation Pump 25-60', 'price' => 89, 'stock' => 15,
            'categories' => $heating, 'image' => 'assets/demo-products/oil-pump-v43-v46.webp',
            'short' => $lv ? 'Energoefektīvs demo cirkulācijas sūknis.' : 'High-efficiency demo circulation pump.',
            'description' => $lv ? '<p>Demo cirkulācijas sūknis radiatoru un grīdas apkures sistēmām.</p>' : '<p>Demo circulation pump for radiator and underfloor heating system layouts.</p>',
        ),
        array(
            'type' => 'simple', 'key' => 'riding-mower-1050a', 'sku' => 'HG-SHOWCASE-RIDER1050A-' . strtoupper($lang),
            'name' => $lv ? 'Demo braucamais zāles pļāvējs 1050A' : 'Demo Riding Lawn Mower 1050A', 'price' => 1299, 'stock' => 5,
            'categories' => $garden, 'image' => 'assets/img/hero-mower.jpg',
            'short' => $lv ? 'Braucams demo pļāvējs lielākām teritorijām.' : 'Ride-on mower demo product for larger garden categories.',
            'description' => $lv ? '<p>Demo braucamais pļāvējs ar platu pļaušanas bloku, regulējamu augstumu un savākšanas iespēju.</p>' : '<p>Demo ride-on mower with a wide cutting deck, adjustable cutting height and grass-collection support.</p>',
        ),
    );
}

function wpbbshop_v422_save_demo_meta($id, $key, $lang, $image) {
    update_post_meta($id, '_wpbbshop_demo_product', '1');
    update_post_meta($id, '_wpbbshop_v422_demo_key', $key);
    update_post_meta($id, '_wpbbshop_v422_lang', $lang);
    update_post_meta($id, '_wpbbshop_feed_exclude', 'yes');
    $url = wpbbshop_v422_image_url($image);
    if ($url) { update_post_meta($id, '_wpbbshop_demo_image_url', esc_url_raw($url)); }
}

function wpbbshop_v422_seed_simple_product($spec, $lang) {
    $id = wpbbshop_v422_product_id($spec['key'], $lang);
    $product = $id ? wc_get_product($id) : new WC_Product_Simple();
    if (!$product instanceof WC_Product_Simple) {
        if ($id) { wp_delete_post($id, true); }
        $product = new WC_Product_Simple(); $id = 0;
    }
    $product->set_name($spec['name']); $product->set_status('publish'); $product->set_catalog_visibility('visible');
    $product->set_short_description($spec['short']); $product->set_description($spec['description']);
    if (!$id) { try { $product->set_sku($spec['sku']); } catch (Exception $e) {} }
    $product->set_regular_price((string) $spec['price']); $product->set_manage_stock(true); $product->set_stock_quantity((int) $spec['stock']);
    $product->set_stock_status((int) $spec['stock'] > 0 ? 'instock' : 'outofstock');
    $product->set_category_ids(array_values(array_filter(array_map('absint', $spec['categories']))));
    $product->set_image_id(0); $product->set_gallery_image_ids(array());
    $id = $product->save();
    wpbbshop_v422_save_demo_meta($id, $spec['key'], $lang, $spec['image']);
    if (function_exists('pll_set_post_language') && wpbbshop_v422_polylang_has($lang)) { pll_set_post_language($id, $lang); }
    return $id;
}

function wpbbshop_v422_seed_variable_product($spec, $lang) {
    $id = wpbbshop_v422_product_id($spec['key'], $lang);
    $product = $id ? wc_get_product($id) : new WC_Product_Variable();
    if (!$product instanceof WC_Product_Variable) {
        if ($id) { wp_delete_post($id, true); }
        $product = new WC_Product_Variable(); $id = 0;
    }
    $product->set_name($spec['name']); $product->set_status('publish'); $product->set_catalog_visibility('visible');
    $product->set_short_description($spec['short']); $product->set_description($spec['description']);
    if (!$id) { try { $product->set_sku($spec['sku']); } catch (Exception $e) {} }
    $product->set_category_ids(array_values(array_filter(array_map('absint', $spec['categories']))));
    $product->set_image_id(0); $product->set_gallery_image_ids(array());

    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0); $attribute->set_name($spec['attribute_name']);
    $attribute->set_options(array_values(array_map(function($row){ return $row['label']; }, $spec['variations'])));
    $attribute->set_position(0); $attribute->set_visible(true); $attribute->set_variation(true);
    $product->set_attributes(array($attribute));
    $id = $product->save();
    wpbbshop_v422_save_demo_meta($id, $spec['key'], $lang, $spec['image']);
    if (function_exists('pll_set_post_language') && wpbbshop_v422_polylang_has($lang)) { pll_set_post_language($id, $lang); }

    foreach ($product->get_children() as $child_id) {
        if ((string) get_post_meta($child_id, '_wpbbshop_v422_demo_variation', true) === '1') { wp_delete_post($child_id, true); }
    }
    $attr_key = sanitize_title($spec['attribute_name']);
    foreach ($spec['variations'] as $index => $row) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($id); $variation->set_attributes(array($attr_key => $row['label']));
        try { $variation->set_sku($spec['sku'] . '-' . ($index + 1)); } catch (Exception $e) {}
        $variation->set_regular_price((string) $row['price']); $variation->set_manage_stock(true); $variation->set_stock_quantity((int) $row['stock']);
        $variation->set_stock_status((int) $row['stock'] > 0 ? 'instock' : 'outofstock');
        $variation_id = $variation->save();
        update_post_meta($variation_id, '_wpbbshop_v422_demo_variation', '1');
    }
    WC_Product_Variable::sync($id); wc_delete_product_transients($id);
    return $id;
}

function wpbbshop_v422_seed_products() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple') || !class_exists('WC_Product_Variable')) {
        return array('created' => 0, 'updated' => 0);
    }
    $cats = wpbbshop_v422_showcase_categories(); $pairs = array(); $count = 0;
    foreach (array('en','lv') as $lang) {
        foreach (wpbbshop_v422_product_specs($lang, $cats) as $spec) {
            $id = $spec['type'] === 'variable' ? wpbbshop_v422_seed_variable_product($spec, $lang) : wpbbshop_v422_seed_simple_product($spec, $lang);
            if ($id) { $pairs[$spec['key']][$lang] = $id; $count++; }
        }
    }
    if (function_exists('pll_save_post_translations')) {
        foreach ($pairs as $pair) {
            if (!empty($pair['en']) && !empty($pair['lv'])) { pll_save_post_translations(array('en' => $pair['en'], 'lv' => $pair['lv'])); }
        }
    }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    delete_transient('wpbbshop_megastore_departments_312_en'); delete_transient('wpbbshop_megastore_departments_312_lv');
    return array('created' => $count, 'updated' => 0);
}

function wpbbshop_v422_seed_all() {
    $posts = wpbbshop_v422_seed_posts(); $products = wpbbshop_v422_seed_products();
    update_option('wpbbshop_v422_showcase_version', WPBBSHOP_V422_VERSION, false);
    update_option('wpbbshop_v422_showcase_disabled', '0', false);
    return array('posts' => $posts, 'products' => $products);
}

function wpbbshop_v422_is_local_site() {
    $env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    return in_array($env, array('local','development'), true) || strpos($host, 'localhost') !== false || str_ends_with($host, '.test');
}

function wpbbshop_v422_showcase_missing() {
    if ((string) get_option('wpbbshop_v422_showcase_version', '') !== WPBBSHOP_V422_VERSION) { return true; }
    if (!wpbbshop_v422_post_id('heating-plan', 'en') || !wpbbshop_v422_product_id('panel-radiator-22', 'en')) { return true; }
    return false;
}

add_action('admin_init', function() {
    if (!current_user_can('manage_options') || !wpbbshop_v422_is_local_site()) { return; }
    if (function_exists('wpbbshop_v429_demo_tools_enabled') && !wpbbshop_v429_demo_tools_enabled()) { return; }
    if ((string) get_option('wpbbshop_v422_showcase_disabled', '0') === '1') { return; }
    if (wpbbshop_v422_showcase_missing()) { wpbbshop_v422_seed_all(); }
}, 90);

function wpbbshop_v422_remove_showcase() {
    $ids = get_posts(array('post_type' => array('product','post'), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array('relation'=>'OR', array('key'=>'_wpbbshop_v422_demo_key','compare'=>'EXISTS'), array('key'=>'_wpbbshop_v422_guide','value'=>'1')), 'suppress_filters' => true));
    foreach ((array) $ids as $id) { wp_delete_post($id, true); }
    delete_option('wpbbshop_v422_showcase_version'); update_option('wpbbshop_v422_showcase_disabled', '1', false);
}

// v4.0.27: the base 500-item catalogue, demo blog and richer new-product
// showcase are managed independently from the single Home & Garden tabbed UI.
// Do not couple the v4.0.00 catalogue seed/remove actions to showcase content.

// v4.0.27: no separate Appearance submenu. Demo imports live exclusively in
// the tabbed Appearance -> Home & Garden screen.

function wpbbshop_v422_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    // Legacy URL kept only for backwards compatibility. The submenu itself is
    // removed; anyone opening an old bookmark gets a single link to the new UI.
    ?>
    <div class="wrap"><h1>Demo imports moved</h1><p>Catalogue, Blog and New Product demo imports are now together under <a href="<?php echo esc_url(admin_url('themes.php?page=wpbbshop-platform&tab=demo')); ?>">Appearance &rarr; Home &amp; Garden</a>.</p></div>
    <?php return; ?>
    <div class="wrap"><h1>WP BB Home & Garden — Demo Blog & Variations</h1>
      <p>Create or refresh bilingual product-guide posts plus heating, cultivator, ride-on mower and mini-tractor demo products with variation-level stock.</p>
      <?php if ($notice) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="wpbbshop_v422_seed_showcase"><?php wp_nonce_field('wpbbshop_v422_seed_showcase'); ?><?php submit_button('Create / refresh demo blog & products'); ?></form>
      <p class="description">Safe to run again: the theme updates its own demo content instead of creating duplicates.</p>
    </div>
    <?php
}

add_action('admin_post_wpbbshop_v422_seed_showcase', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v422_seed_showcase');
    if (function_exists('wpbbshop_v429_demo_tools_enabled') && !wpbbshop_v429_demo_tools_enabled()) { wp_die(esc_html__('Demo tools are locked. Enable them in Appearance -> WP BB HOME & GARDEN Theme Settings.', 'wpbbshop')); }
    $result = function_exists('wpbbshop_v423_seed_all') ? wpbbshop_v423_seed_all() : wpbbshop_v422_seed_all();
    $message = sprintf('Demo showcase ready: %d posts created, %d updated; %d bilingual demo products refreshed.', (int)$result['posts']['created'], (int)$result['posts']['updated'], (int)$result['products']['created']);
    if (function_exists('wpbbshop_v429_lock_demo_tools')) { wpbbshop_v429_lock_demo_tools(); }
    wp_safe_redirect(add_query_arg('wpbb_notice', rawurlencode($message . ' Demo tools are locked again.'), admin_url('themes.php?page=wpbbshop-platform&tab=demo'))); exit;
});

function wpbbshop_v422_guide_posts() {
    $lang = wpbbshop_v422_lang();
    $args = array(
        'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1,
        'meta_key' => '_wpbbshop_v422_order', 'orderby' => array('meta_value_num'=>'ASC','date'=>'DESC'),
        'meta_query' => array(array('key'=>'_wpbbshop_v422_guide','value'=>'1'), array('key'=>'_wpbbshop_v422_lang','value'=>$lang)),
        'suppress_filters' => true,
    );
    $posts = get_posts($args);
    $unique = array(); $seen = array();
    foreach ((array) $posts as $post) {
        $key = (string) get_post_meta($post->ID, '_wpbbshop_v422_guide_key', true);
        if ($key === '' || isset($seen[$key])) { continue; }
        $seen[$key] = true; $unique[] = $post;
        if (count($unique) >= 12) { break; }
    }
    return $unique;
}

function wpbbshop_v422_guides_slider_html() {
    $posts = wpbbshop_v422_guide_posts(); if (!$posts) { return ''; }
    $lv = wpbbshop_v422_lang() === 'lv';
    ob_start(); ?>
    <section class="wpbb-v400-shell wpbb-v422-guides" aria-labelledby="wpbb-v422-guides-title">
      <header class="wpbb-v422-guides-head">
        <div><span><?php echo esc_html($lv ? 'PREČU CEĻVEŽI' : 'PRODUCT GUIDES'); ?></span><h2 id="wpbb-v422-guides-title"><?php echo esc_html($lv ? 'Padomi gudrākai preču izvēlei' : 'Ideas for choosing the right products'); ?></h2><p><?php echo esc_html($lv ? 'Apkure, dārza tehnika, variācijas, pieejamība un praktiska apkope.' : 'Heating, garden machinery, product variations, availability and practical maintenance.'); ?></p></div>
        <nav class="wpbb-v422-guides-nav" aria-label="<?php echo esc_attr($lv ? 'Rakstu slīdņa vadība' : 'Product guide slider controls'); ?>"><button type="button" class="wpbb-v422-guides-prev" aria-label="<?php echo esc_attr($lv ? 'Iepriekšējais' : 'Previous'); ?>">←</button><button type="button" class="wpbb-v422-guides-next" aria-label="<?php echo esc_attr($lv ? 'Nākamais' : 'Next'); ?>">→</button></nav>
      </header>
      <div class="wpbb-v422-guides-viewport"><div class="wpbb-v422-guides-track">
      <?php foreach ($posts as $post) : $key=(string)get_post_meta($post->ID,'_wpbbshop_v422_guide_key',true); $icon=(string)get_post_meta($post->ID,'_wpbbshop_v422_icon',true); $image=function_exists('wpbbshop_v425_guide_image_url')?wpbbshop_v425_guide_image_url($post->ID):(function_exists('wpbbshop_v423_guide_image_url')?wpbbshop_v423_guide_image_url($post->ID):''); ?>
        <article class="wpbb-v422-guide wpbb-v422-guide--<?php echo esc_attr(sanitize_html_class($key)); ?>"><a href="<?php echo esc_url(get_permalink($post)); ?>"><span class="wpbb-v422-guide-visual" aria-hidden="true"><?php if($image): ?><img src="<?php echo esc_url($image); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php else: ?><b><?php echo esc_html($icon ?: '✦'); ?></b><?php endif; ?><i></i></span><span class="wpbb-v422-guide-meta"><?php echo esc_html($lv ? 'Preču padoms' : 'Product guide'); ?></span><h3><?php echo esc_html(get_the_title($post)); ?></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt($post), 22)); ?></p><span class="wpbb-v422-guide-read"><?php echo esc_html($lv ? 'Lasīt rakstu' : 'Read article'); ?> <b>→</b></span></a></article>
      <?php endforeach; ?>
      </div></div>
    </section>
    <?php return (string) ob_get_clean();
}

add_filter('do_shortcode_tag', function($output, $tag) {
    if ($tag !== 'wpbbshop_home' || strpos($output, 'wpbb-v422-guides') !== false) { return $output; }
    $slider = wpbbshop_v422_guides_slider_html(); if ($slider === '') { return $output; }
    $needle = '<section class="wpbb-v400-shell wpbb-v400-services">';
    $pos = strpos($output, $needle);
    if ($pos !== false) { return substr($output, 0, $pos) . $slider . substr($output, $pos); }
    return $output . $slider;
}, 99, 2);

function wpbbshop_v422_variation_stock_text($variation) {
    if (!$variation instanceof WC_Product_Variation) { return ''; }
    if (!$variation->is_in_stock()) { return wpbbshop_v422_t('Out of stock', 'Nav noliktavā'); }
    $qty = $variation->get_stock_quantity();
    if ($variation->managing_stock() && $qty !== null) {
        $qty = (int) $qty;
        if ($qty <= 3) { return sprintf(wpbbshop_v422_t('Low stock — %d available', 'Mazs atlikums — pieejami %d'), $qty); }
        return sprintf(wpbbshop_v422_t('In stock — %d available', 'Noliktavā — pieejami %d'), $qty);
    }
    if ($variation->backorders_allowed()) { return wpbbshop_v422_t('Available to order', 'Pieejams pēc pasūtījuma'); }
    return wpbbshop_v422_t('In stock', 'Noliktavā');
}

function wpbbshop_v422_variation_attributes_html($variation) {
    $labels = array();
    foreach ($variation->get_variation_attributes() as $key => $value) {
        $name = str_replace('attribute_', '', $key); $label = wc_attribute_label($name, $variation);
        if (taxonomy_exists($name)) { $term = get_term_by('slug', $value, $name); if ($term instanceof WP_Term) { $value = $term->name; } }
        $labels[] = '<span><small>' . esc_html($label) . '</small><strong>' . esc_html($value) . '</strong></span>';
    }
    return implode('', $labels);
}

function wpbbshop_v422_variable_summary_html($product) {
    if (!$product instanceof WC_Product_Variable) { return ''; }
    $children = array_filter($product->get_children()); $total = count($children); $available = 0;
    foreach ($children as $child_id) { $v = wc_get_product($child_id); if ($v instanceof WC_Product_Variation && $v->is_in_stock()) { $available++; } }
    if (!$total) { return ''; }
    return '<div class="wpbb-v422-card-variants"><span>' . esc_html(sprintf(wpbbshop_v422_t('%d variants', '%d varianti'), $total)) . '</span><strong>' . esc_html(sprintf(wpbbshop_v422_t('%d available', '%d pieejami'), $available)) . '</strong></div>';
}

add_action('woocommerce_after_variations_table', function() {
    global $product; if (!$product instanceof WC_Product_Variable) { return; }
    $children = array_values(array_filter($product->get_children())); if (!$children) { return; }
    $shown = array_slice($children, 0, (int) apply_filters('wpbbshop_v422_variation_matrix_max_rows', 60, $product));
    ?>
    <section class="wpbb-v422-variation-matrix" aria-label="<?php echo esc_attr(wpbbshop_v422_t('Options and availability', 'Varianti un pieejamība')); ?>">
      <header><div><span><?php echo esc_html(wpbbshop_v422_t('ALL OPTIONS', 'VISI VARIANTI')); ?></span><h3><?php echo esc_html(wpbbshop_v422_t('Options & availability', 'Varianti un pieejamība')); ?></h3></div><small><?php echo esc_html(sprintf(wpbbshop_v422_t('%d variations', '%d varianti'), count($children))); ?></small></header>
      <div class="wpbb-v422-variation-rows">
      <?php foreach ($shown as $variation_id) : $variation=wc_get_product($variation_id); if (!$variation instanceof WC_Product_Variation) { continue; } $attrs=$variation->get_variation_attributes(); $in=$variation->is_in_stock(); $qty=$variation->get_stock_quantity(); $low=$in && $variation->managing_stock() && $qty!==null && (int)$qty<=3; ?>
        <div class="wpbb-v422-variation-row<?php echo $in?'':' is-out'; ?><?php echo $low?' is-low':''; ?>" data-variation-id="<?php echo esc_attr($variation_id); ?>">
          <div class="wpbb-v422-variation-attrs"><?php echo wp_kses_post(wpbbshop_v422_variation_attributes_html($variation)); ?></div>
          <div class="wpbb-v422-variation-sku"><small>SKU</small><strong><?php echo esc_html($variation->get_sku() ?: '—'); ?></strong></div>
          <div class="wpbb-v422-variation-price"><?php echo wp_kses_post($variation->get_price_html()); ?></div>
          <div class="wpbb-v422-variation-stock"><span></span><?php echo esc_html(wpbbshop_v422_variation_stock_text($variation)); ?></div>
          <button type="button" class="wpbb-v422-variation-choose" data-wpbb-variation="<?php echo esc_attr(wp_json_encode($attrs)); ?>" <?php disabled(!$in); ?>><?php echo esc_html($in ? wpbbshop_v422_t('Choose', 'Izvēlēties') : wpbbshop_v422_t('Unavailable', 'Nav pieejams')); ?></button>
        </div>
      <?php endforeach; ?>
      </div>
    </section>
    <?php
}, 25);

add_action('wp_enqueue_scripts', function() {
    $css = get_stylesheet_directory() . '/assets/css/v422-demo-blog-variations.css';
    $js = get_stylesheet_directory() . '/assets/js/v422-demo-blog-variations.js';
    if (is_readable($css)) { wp_enqueue_style('wpbbshop-v422-demo-blog-variations', get_stylesheet_directory_uri().'/assets/css/v422-demo-blog-variations.css', array('wpbbshop-v421-final'), (string) filemtime($css)); }
    if (is_readable($js) && (is_front_page() || (function_exists('is_product') && is_product()))) { wp_enqueue_script('wpbbshop-v422-demo-blog-variations', get_stylesheet_directory_uri().'/assets/js/v422-demo-blog-variations.js', array('jquery'), (string) filemtime($js), true); }
}, PHP_INT_MAX);
