<?php
/**
 * WP BB Home & Garden v4.0.23
 * Realistic demo imagery, richer variable-product demos, product-page alignment,
 * and editorial single-post presentation for the bilingual Product Guides.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V423_VERSION')) {
    define('WPBBSHOP_V423_VERSION', '4.0.23');
}

function wpbbshop_v423_lang() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (is_string($lang) && $lang !== '') {
            return strtolower($lang) === 'lv' ? 'lv' : 'en';
        }
    }
    return stripos((string) get_locale(), 'lv') === 0 ? 'lv' : 'en';
}

function wpbbshop_v423_t($en, $lv) {
    return wpbbshop_v423_lang() === 'lv' ? $lv : $en;
}

/**
 * Demo-only image library. These URLs point to public-domain / CC0 Wikimedia
 * Commons files and are intentionally remote so the theme does not bulk-import
 * media just to demonstrate catalogue layouts.
 */
function wpbbshop_v423_image_library() {
    $base = 'https://commons.wikimedia.org/wiki/Special:FilePath/';
    return array(
        'radiator'   => $base . 'Radiator.png?width=1600',
        'radiator_room' => $base . 'Caloriferi.png?width=1600',
        'underfloor' => $base . 'Public_domain_image_-_underfloor_heating_installation.JPG?width=1600',
        'tiller'     => $base . 'Power_tiller_or_cultivator.jpg?width=1600',
        'mower'      => $base . 'Ride-on_lawn_mower_Rhodes.jpg?width=1600',
        'tractor'    => $base . 'John_Deere_LX_279.jpg?width=1600',
        'pump'       => $base . 'Heizpumpe_Heizung_1.jpg?width=1400',
        'boiler'     => $base . 'Outdoor_wood-fired_boiler.webp?width=1400',
        'heatpump'   => $base . 'Air_Source_Heat_Pump_-_Vailliant_aroTherm_Plus_on_a_terraced_house.jpg?width=1600',
    );
}

function wpbbshop_v423_product_image_map() {
    $img = wpbbshop_v423_image_library();
    return array(
        'panel-radiator-22'        => $img['radiator'],
        'underfloor-mat'           => $img['underfloor'],
        'cultivator-1050'          => $img['tiller'],
        'mini-tractor-15hp'        => $img['tractor'],
        'towel-radiator'           => $img['radiator_room'],
        'boiler-24kw'              => $img['boiler'],
        'pump-25-60'               => $img['pump'],
        'riding-mower-1050a'       => $img['mower'],
        'air-source-heat-pump'     => $img['heatpump'],
        'underfloor-manifold'      => $img['underfloor'],
        'radiator-valve-pack'      => $img['radiator_room'],
        'compact-tractor-package'  => $img['tractor'],
    );
}

function wpbbshop_v423_demo_image_url($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $id = $product->get_id();
    $key = (string) get_post_meta($id, '_wpbbshop_v422_demo_key', true);
    if ($key === '' && $product instanceof WC_Product_Variation) {
        $key = (string) get_post_meta($product->get_parent_id(), '_wpbbshop_v422_demo_key', true);
    }
    $map = wpbbshop_v423_product_image_map();
    if ($key !== '' && isset($map[$key])) { return esc_url_raw($map[$key]); }
    $remote = esc_url_raw((string) get_post_meta($id, '_wpbbshop_v423_demo_image_url', true));
    if ($remote) { return $remote; }
    return '';
}

/** Last image filter wins over the older generic demo-image fallback. */
add_filter('woocommerce_product_get_image', function($html, $product, $size, $attr, $placeholder) {
    $url = wpbbshop_v423_demo_image_url($product);
    if (!$url) { return $html; }
    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wpbb-v423-demo-image';
    if (is_array($attr) && !empty($attr['class'])) { $classes .= ' ' . sanitize_html_class($attr['class']); }
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($classes) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">';
}, PHP_INT_MAX, 5);

function wpbbshop_v423_single_demo_image($product) {
    $url = wpbbshop_v423_demo_image_url($product);
    if (!$url) { return ''; }
    return '<figure class="llg-single-main-image wpbb-v423-single-demo"><img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" fetchpriority="high" decoding="async" referrerpolicy="no-referrer"></figure>';
}

function wpbbshop_v423_post_specs() {
    $img = wpbbshop_v423_image_library();
    return array(
        'heating-plan' => array(
            'image' => $img['heatpump'],
            'en' => array(
                'title' => 'Planning a practical home heating system',
                'excerpt' => 'A room-by-room approach to heat demand, emitters, controls and circulation components.',
                'content' => '<p class="wpbb-v423-lead">A comfortable heating system starts with the building and the rooms, not with one appliance. The aim is to match heat demand, emitters, controls and water flow so every component works as part of one system.</p><h2>Start with the room-by-room heat requirement</h2><p>Estimate the design heat loss for each room and use that figure to size radiators, underfloor circuits or fan-assisted emitters. Oversizing every component can make control less stable, while undersizing can leave rooms cold on the worst winter days.</p><div class="wpbb-v423-callout"><strong>Useful shopping data</strong><p>Look for output at stated flow temperatures, connection size, dimensions, control compatibility and real stock for the exact variant you need.</p></div><h2>Think in complete circuits</h2><p>Boiler or heat-pump output, radiator size, pump duty, valves, pipework and thermostatic controls all affect comfort and efficiency. A well-structured product catalogue should let you compare these specifications without jumping between unrelated pages.</p><h3>Before you order</h3><ul><li>Confirm the room heat requirement.</li><li>Check emitter dimensions and connection position.</li><li>Allow for valves, controls and commissioning accessories.</li><li>Check the actual stock level of the selected variant.</li></ul>',
            ),
            'lv' => array(
                'title' => 'Kā plānot praktisku mājas apkures sistēmu',
                'excerpt' => 'Telpu siltuma slodze, sildķermeņi, vadība un cirkulācija vienā saprotamā plānā.',
                'content' => '<p class="wpbb-v423-lead">Laba apkures sistēma sākas ar ēku un telpām, nevis ar vienu iekārtu. Mērķis ir savstarpēji saskaņot siltuma slodzi, sildķermeņus, vadību un ūdens plūsmu.</p><h2>Sāc ar katras telpas siltuma nepieciešamību</h2><p>Nosaki aptuvenos siltuma zudumus katrai telpai un pēc tiem izvēlies radiatorus, grīdas apkures kontūras vai citus sildķermeņus. Pārāk liela jauda var pasliktināt regulēšanu, bet pārāk maza — neļaut uzturēt komfortu aukstākajās dienās.</p><div class="wpbb-v423-callout"><strong>Noderīgi dati preces lapā</strong><p>Jauda pie norādītas temperatūras, pieslēguma izmērs, gabarīti, vadības saderība un konkrētā varianta reāls atlikums.</p></div><h2>Domā par visu sistēmu kopumā</h2><p>Katla vai siltumsūkņa jauda, radiatoru izmēri, sūknis, vārsti, cauruļvadi un termostati kopā nosaka komfortu un efektivitāti.</p><h3>Pirms pasūtīšanas</h3><ul><li>Pārbaudi telpas siltuma nepieciešamību.</li><li>Salīdzini izmērus un pieslēguma vietu.</li><li>Ieplāno vārstus, vadību un montāžas piederumus.</li><li>Pārbaudi tieši izvēlētā varianta atlikumu.</li></ul>',
            ),
        ),
        'radiator-size' => array(
            'image' => $img['radiator_room'],
            'en' => array(
                'title' => 'Choosing radiator sizes and variants',
                'excerpt' => 'How width, height, panel type and connections affect output, price and availability.',
                'content' => '<p class="wpbb-v423-lead">Radiators are ideal variable products because the model name can stay the same while dimensions, output, price and stock change substantially.</p><h2>Compare more than the outside dimensions</h2><p>Width and height are only the start. Panel configuration, connection position and design temperatures all influence useful heat output. For replacement work, measure pipe centres and available wall space before ordering.</p><div class="wpbb-v423-callout"><strong>Variant-level availability matters</strong><p>A 600 × 1000 mm radiator may be in stock while the 600 × 1200 mm version is sold out. Showing stock beside each option prevents avoidable back-orders.</p></div><h2>Use the product matrix</h2><p>The demo products in this theme show SKU, price and stock for every variation so the customer can compare sizes before selecting the WooCommerce dropdown.</p>',
            ),
            'lv' => array(
                'title' => 'Radiatoru izmēru un variantu izvēle',
                'excerpt' => 'Kā izmērs, tips un pieslēgums ietekmē jaudu, cenu un pieejamību.',
                'content' => '<p class="wpbb-v423-lead">Radiatori ir labs variāciju preču piemērs: modeļa nosaukums var būt viens, bet izmērs, jauda, cena un atlikums būtiski atšķiras.</p><h2>Salīdzini vairāk nekā tikai gabarītus</h2><p>Platums un augstums ir tikai sākums. Paneļu konfigurācija, pieslēguma vieta un aprēķina temperatūra ietekmē reālo siltuma jaudu. Mainot vecu radiatoru, pirms pirkuma izmēri cauruļu pieslēgumus un brīvo vietu pie sienas.</p><div class="wpbb-v423-callout"><strong>Atlikumam jābūt redzamam katram variantam</strong><p>600 × 1000 mm radiators var būt noliktavā, bet 600 × 1200 mm variants — izpārdots. Atlikums pie konkrētās izvēles samazina kļūdainus pasūtījumus.</p></div><h2>Izmanto variāciju tabulu</h2><p>Šīs tēmas demo preces parāda SKU, cenu un atlikumu katram variantam vēl pirms izvēles WooCommerce laukos.</p>',
            ),
        ),
        'cultivator-guide' => array(
            'image' => $img['tiller'],
            'en' => array(
                'title' => 'Cultivator or mini tractor: what suits the job?',
                'excerpt' => 'Working width, transmission, weight and attachments are the key differences.',
                'content' => '<p class="wpbb-v423-lead">A cultivator and a compact tractor can both save time, but they solve different problems. Choose by the work you repeat most often, the size of the property and the attachments you genuinely need.</p><h2>When a cultivator makes sense</h2><p>For vegetable plots, seed-bed preparation and seasonal soil work, a walk-behind cultivator is compact, easy to store and relatively simple to transport.</p><h2>When to step up to a mini tractor</h2><p>Larger sites benefit from a seated machine when mowing, towing and seasonal attachments are used regularly. Compare transmission, turning radius, hitch or attachment system and parts support.</p><div class="wpbb-v423-check-grid"><span>Working width</span><span>Forward / reverse gears</span><span>Attachment support</span><span>Machine weight</span></div>',
            ),
            'lv' => array(
                'title' => 'Kultivators vai mini traktors: ko izvēlēties?',
                'excerpt' => 'Darba platums, transmisija, svars un agregāti ir galvenās atšķirības.',
                'content' => '<p class="wpbb-v423-lead">Gan kultivators, gan kompakts traktors ietaupa laiku, bet tie paredzēti atšķirīgiem darbiem. Izvēlies pēc tā, ko dari visbiežāk, teritorijas lieluma un vajadzīgajiem agregātiem.</p><h2>Kad pietiek ar kultivatoru</h2><p>Sakņu dārzam, augsnes sagatavošanai un sezonas darbiem stumjams kultivators ir kompakts, vienkārši glabājams un pārvadājams.</p><h2>Kad izvēlēties mini traktoru</h2><p>Lielākā teritorijā sēžama tehnika ir ērtāka, ja regulāri pļauj, velc piekabi vai izmanto sezonas agregātus. Salīdzini transmisiju, apgriešanās rādiusu, sakabes sistēmu un rezerves daļu pieejamību.</p><div class="wpbb-v423-check-grid"><span>Darba platums</span><span>Uz priekšu / atpakaļ</span><span>Agregātu iespējas</span><span>Mašīnas svars</span></div>',
            ),
        ),
        'variation-stock' => array(
            'image' => $img['radiator'],
            'en' => array(
                'title' => 'Why variation-level stock improves product pages',
                'excerpt' => 'Show the customer exactly which size, package or configuration is available.',
                'content' => '<p class="wpbb-v423-lead">A variable product can be “in stock” overall while the customer’s required size is unavailable. That is why availability should be attached to the exact option, not only to the parent product.</p><h2>Make the decision visible</h2><p>Each row should show the human-readable option, SKU, price and stock state. Low stock can be highlighted without hiding the product, while sold-out options remain visible for comparison.</p><div class="wpbb-v423-callout"><strong>Good UX rule</strong><p>Do not force a customer to make several dropdown selections just to discover that the final combination is unavailable.</p></div>',
            ),
            'lv' => array(
                'title' => 'Kāpēc atlikums jāparāda katram preces variantam',
                'excerpt' => 'Parādi pircējam tieši to izmēru, komplektu vai konfigurāciju, kas ir pieejama.',
                'content' => '<p class="wpbb-v423-lead">Maināma prece kopumā var būt “noliktavā”, bet pircējam vajadzīgais izmērs var nebūt pieejams. Tāpēc atlikums jāpiesaista konkrētajam variantam.</p><h2>Parādi lēmumam svarīgo informāciju</h2><p>Katrā rindā noder varianta nosaukums, SKU, cena un atlikuma statuss. Mazs atlikums var tikt izcelts, bet izpārdotie varianti paliek redzami salīdzināšanai.</p><div class="wpbb-v423-callout"><strong>Labs UX princips</strong><p>Nespied pircēju aizpildīt vairākus izvēles laukus tikai tādēļ, lai beigās uzzinātu, ka kombinācija nav pieejama.</p></div>',
            ),
        ),
        'underfloor-heating' => array(
            'image' => $img['underfloor'],
            'en' => array(
                'title' => 'Sizing electric underfloor heating mats',
                'excerpt' => 'Measure the usable heated area first, then choose the nearest compatible kit.',
                'content' => '<p class="wpbb-v423-lead">Underfloor heating kits are commonly sold by coverage area. The useful area is not always the same as the room footprint, especially in kitchens and bathrooms.</p><h2>Measure only the heated floor area</h2><p>Exclude fixed cabinets, baths and permanent fixtures where heating should not be installed. Then choose the closest mat or cable size without cutting the heating cable.</p><h2>Check the electrical details</h2><p>Compare total wattage, supply requirements, thermostat compatibility and floor-finish limits. The demo product variants show separate stock for each coverage size.</p>',
            ),
            'lv' => array(
                'title' => 'Elektriskās grīdas apkures paklāja izmēra izvēle',
                'excerpt' => 'Aprēķini reāli apsildāmo laukumu un izvēlies tuvāko piemēroto komplektu.',
                'content' => '<p class="wpbb-v423-lead">Grīdas apkures komplektus parasti izvēlas pēc apsildāmās platības. Tā ne vienmēr sakrīt ar visas telpas platību, īpaši virtuvē un vannas istabā.</p><h2>Izmēri tikai apsildāmo grīdas zonu</h2><p>Neiekļauj stacionāras mēbeles, vannu un citus elementus, zem kuriem apkuri neuzstāda. Pēc tam izvēlies tuvāko paklāja vai kabeļa izmēru, nesamazinot sildkabeļa garumu.</p><h2>Pārbaudi elektriskos parametrus</h2><p>Salīdzini kopējo jaudu, pieslēgumu, termostata saderību un grīdas seguma prasības. Demo variantiem katram laukuma izmēram redzams atsevišķs atlikums.</p>',
            ),
        ),
        'garden-maintenance' => array(
            'image' => $img['mower'],
            'en' => array(
                'title' => 'Seasonal checks for garden machinery',
                'excerpt' => 'A simple pre-season checklist for engines, filters, belts and safety controls.',
                'content' => '<p class="wpbb-v423-lead">A short inspection before the busy season is cheaper than losing a weekend to a machine that will not start.</p><h2>Start with service items</h2><p>Check oil level and condition, air filtration, fuel condition, spark plug, cables, tyres and fasteners. Cutting equipment should be inspected for damage and sharpened or replaced when required.</p><h2>Do not skip safety systems</h2><p>Seat switches, blade-stop systems, guards and emergency controls should work before the machine goes back into regular use.</p>',
            ),
            'lv' => array(
                'title' => 'Sezonas pārbaudes dārza tehnikai',
                'excerpt' => 'Vienkāršs kontrolsaraksts dzinējam, filtriem, siksnām un drošības elementiem.',
                'content' => '<p class="wpbb-v423-lead">Īsa pārbaude pirms aktīvās sezonas ir lētāka nekā zaudēta nedēļas nogale, kad tehnika neiedarbojas.</p><h2>Sāc ar apkopes elementiem</h2><p>Pārbaudi eļļas līmeni un stāvokli, gaisa filtru, degvielu, sveci, troses, riepas un stiprinājumus. Griešanas elementi jāpārbauda, jāuzasina vai vajadzības gadījumā jānomaina.</p><h2>Neaizmirsti drošības sistēmas</h2><p>Sēdekļa slēdzim, naža apturēšanai, aizsargiem un avārijas vadībai jādarbojas pirms regulāras lietošanas.</p>',
            ),
        ),
        'heat-pump-guide' => array(
            'image' => $img['heatpump'],
            'en' => array(
                'title' => 'Air-source heat pumps: compare output, not just model names',
                'excerpt' => 'A simple guide to output, flow temperature, electrical supply and system compatibility.',
                'content' => '<p class="wpbb-v423-lead">Heat-pump model ranges often look similar while useful output changes with outdoor temperature and required water temperature.</p><h2>Check the design condition</h2><p>Compare output at the outdoor temperature and flow temperature relevant to the property. A unit that looks large on a headline rating may deliver less at a colder design point.</p><h2>Plan the system around lower temperatures</h2><p>Larger radiators or correctly designed underfloor heating can reduce required flow temperature and improve seasonal efficiency.</p>',
            ),
            'lv' => array(
                'title' => 'Gaiss–ūdens siltumsūkņi: salīdzini reālo jaudu, ne tikai modeli',
                'excerpt' => 'Vienkāršs ceļvedis par jaudu, plūsmas temperatūru, elektrības pieslēgumu un saderību.',
                'content' => '<p class="wpbb-v423-lead">Siltumsūkņu modeļu nosaukumi var būt līdzīgi, taču pieejamā jauda mainās atkarībā no āra un nepieciešamās ūdens temperatūras.</p><h2>Pārbaudi aprēķina darba punktu</h2><p>Salīdzini jaudu pie tādas āra un plūsmas temperatūras, kas atbilst konkrētajai ēkai. Virsrakstā norādīta liela jauda ne vienmēr nozīmē to pašu aukstā ziemas dienā.</p><h2>Plāno sistēmu zemākai temperatūrai</h2><p>Lielāki radiatori vai pareizi projektēta grīdas apkure var samazināt vajadzīgo plūsmas temperatūru un uzlabot sezonas efektivitāti.</p>',
            ),
        ),
        'tractor-attachments' => array(
            'image' => $img['tractor'],
            'en' => array(
                'title' => 'Mini tractor attachments worth planning before purchase',
                'excerpt' => 'Mower decks, trailers and seasonal attachments can change which base machine is the best buy.',
                'content' => '<p class="wpbb-v423-lead">The best compact tractor is not always the machine with the longest specification list. Attachment compatibility often matters more.</p><h2>Start with the jobs</h2><p>List mowing, towing, soil work, snow clearance and transport tasks before choosing the base machine. Then confirm hitch type, power requirements and permitted attachment weight.</p><div class="wpbb-v423-callout"><strong>Buy the system</strong><p>Price the machine together with the attachments you expect to use in year one, not as an isolated chassis.</p></div>',
            ),
            'lv' => array(
                'title' => 'Mini traktora agregāti, ko ieplānot pirms pirkuma',
                'excerpt' => 'Pļaušanas bloks, piekabe un sezonas agregāti var mainīt piemērotākās bāzes mašīnas izvēli.',
                'content' => '<p class="wpbb-v423-lead">Labākais kompaktais traktors ne vienmēr ir modelis ar garāko specifikāciju. Bieži svarīgāka ir agregātu saderība.</p><h2>Sāc ar darbiem</h2><p>Pirms pirkuma uzraksti, vai būs jāpļauj, jāvelk piekabe, jāapstrādā augsne, jātīra sniegs vai jāpārvadā materiāli. Tad pārbaudi sakabes tipu, jaudas prasības un pieļaujamo agregāta svaru.</p><div class="wpbb-v423-callout"><strong>Pērc sistēmu, nevis tikai mašīnu</strong><p>Salīdzini cenu kopā ar tiem agregātiem, ko plāno izmantot jau pirmajā sezonā.</p></div>',
            ),
        ),
    );
}

function wpbbshop_v423_seed_posts() {
    if (!function_exists('wpbbshop_v422_guide_category')) { return array('created'=>0,'updated'=>0); }
    $created = 0; $updated = 0; $pairs = array(); $order = 0;
    $category_ids = array('en'=>wpbbshop_v422_guide_category('en'),'lv'=>wpbbshop_v422_guide_category('lv'));
    foreach (wpbbshop_v423_post_specs() as $key => $spec) {
        foreach (array('en','lv') as $lang) {
            $data = $spec[$lang];
            $id = function_exists('wpbbshop_v422_post_id') ? wpbbshop_v422_post_id($key, $lang) : 0;
            $args = array(
                'post_type'=>'post','post_status'=>'publish','post_title'=>$data['title'],'post_excerpt'=>$data['excerpt'],
                'post_content'=>$data['content'],'post_name'=>sanitize_title($data['title']),'comment_status'=>'closed',
                'post_category'=>array_filter(array($category_ids[$lang])),
            );
            if ($id) { $args['ID']=$id; $result=wp_update_post($args,true); if (!is_wp_error($result)) { $updated++; } }
            else { $result=wp_insert_post($args,true); if (!is_wp_error($result)) { $id=(int)$result; $created++; } }
            if (!$id || is_wp_error($result)) { continue; }
            update_post_meta($id,'_wpbbshop_v422_guide','1');
            update_post_meta($id,'_wpbbshop_v422_guide_key',$key);
            update_post_meta($id,'_wpbbshop_v422_lang',$lang);
            update_post_meta($id,'_wpbbshop_v422_order',$order);
            update_post_meta($id,'_wpbbshop_v423_featured_image_url',esc_url_raw($spec['image']));
            update_post_meta($id,'_wpbbshop_v423_read_minutes', max(3, (int) ceil(str_word_count(wp_strip_all_tags($data['content'])) / 180)));
            if (function_exists('pll_set_post_language')) { pll_set_post_language($id,$lang); }
            $pairs[$key][$lang]=$id;
        }
        $order++;
    }
    if (function_exists('pll_save_post_translations')) {
        foreach ($pairs as $pair) { if (!empty($pair['en']) && !empty($pair['lv'])) { pll_save_post_translations(array('en'=>$pair['en'],'lv'=>$pair['lv'])); } }
    }
    return array('created'=>$created,'updated'=>$updated);
}

function wpbbshop_v423_guide_image_url($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) { return ''; }
    if (has_post_thumbnail($post_id)) {
        $url = get_the_post_thumbnail_url($post_id, 'large');
        if ($url) { return $url; }
    }
    return esc_url_raw((string) get_post_meta($post_id, '_wpbbshop_v423_featured_image_url', true));
}

function wpbbshop_v423_product_specs($lang, $cats) {
    $lv = $lang === 'lv';
    $heating = array_filter(array($cats[$lang]['heating'], $cats[$lang]['heating_parent']));
    $garden = array_filter(array($cats[$lang]['garden'], $cats[$lang]['tillers']));
    $img = wpbbshop_v423_product_image_map();
    $local = 'assets/img/hero-mower.jpg';
    return array(
        array('type'=>'variable','key'=>'panel-radiator-22','sku'=>'HG-SHOWCASE-RAD22-'.strtoupper($lang),'name'=>$lv?'Demo paneļu radiators Type 22':'Demo Panel Radiator Type 22','categories'=>$heating,'image'=>$local,'remote'=>$img['panel-radiator-22'],'short'=>$lv?'Paneļu radiators ar izmēram atbilstošu cenu un atlikumu.':'Panel radiator with size-specific price and stock.','description'=>$lv?'<p>Demo Type 22 paneļu radiators ar vairākām izmēru izvēlēm.</p>':'<p>Demo Type 22 panel radiator with multiple size choices.</p>','attribute_name'=>$lv?'Izmērs':'Size','variations'=>array(array('label'=>'600 × 800 mm','price'=>89,'stock'=>14),array('label'=>'600 × 1000 mm','price'=>119,'stock'=>6),array('label'=>'600 × 1200 mm','price'=>139,'stock'=>0),array('label'=>'600 × 1600 mm','price'=>179,'stock'=>3),array('label'=>'600 × 1800 mm','price'=>209,'stock'=>7))),
        array('type'=>'variable','key'=>'underfloor-mat','sku'=>'HG-SHOWCASE-UFH-'.strtoupper($lang),'name'=>$lv?'Demo elektriskās grīdas apkures komplekts':'Demo Electric Underfloor Heating Kit','categories'=>$heating,'image'=>$local,'remote'=>$img['underfloor-mat'],'short'=>$lv?'Grīdas apkures komplekts ar platībai atbilstošu jaudu un atlikumu.':'Underfloor heating kit with coverage-specific wattage and stock.','description'=>$lv?'<p>Demo elektriskās grīdas apkures komplekts vairākiem laukumiem.</p>':'<p>Demo electric floor-heating kit for multiple coverage sizes.</p>','attribute_name'=>$lv?'Platība':'Coverage','variations'=>array(array('label'=>'2 m² / 320 W','price'=>64,'stock'=>18),array('label'=>'4 m² / 640 W','price'=>99,'stock'=>9),array('label'=>'6 m² / 960 W','price'=>139,'stock'=>2),array('label'=>'8 m² / 1280 W','price'=>169,'stock'=>6),array('label'=>'10 m² / 1600 W','price'=>199,'stock'=>0))),
        array('type'=>'variable','key'=>'cultivator-1050','sku'=>'HG-SHOWCASE-CULT1050-'.strtoupper($lang),'name'=>$lv?'Demo benzīna kultivators 1050 Pro':'Demo Petrol Cultivator 1050 Pro','categories'=>$garden,'image'=>$local,'remote'=>$img['cultivator-1050'],'short'=>$lv?'Jaudīgs kultivators ar darba platuma un komplekta variantiem.':'Heavy-duty cultivator with working-width and package choices.','description'=>$lv?'<p>Demo četrtaktu kultivators augsnes apstrādei ar dažādiem komplektiem.</p>':'<p>Demo four-stroke cultivator for soil preparation with multiple packages.</p>','attribute_name'=>$lv?'Komplekts':'Package','variations'=>$lv?array(array('label'=>'75 cm pamata','price'=>579,'stock'=>9),array('label'=>'85 cm + sānu diski','price'=>679,'stock'=>7),array('label'=>'105 cm frēzēšana','price'=>829,'stock'=>5),array('label'=>'105 cm + arkls','price'=>929,'stock'=>2),array('label'=>'Pilns dārza komplekts','price'=>1199,'stock'=>0)):array(array('label'=>'75 cm base','price'=>579,'stock'=>9),array('label'=>'85 cm + side discs','price'=>679,'stock'=>7),array('label'=>'105 cm tilling kit','price'=>829,'stock'=>5),array('label'=>'105 cm + plough','price'=>929,'stock'=>2),array('label'=>'Full garden kit','price'=>1199,'stock'=>0))),
        array('type'=>'variable','key'=>'mini-tractor-15hp','sku'=>'HG-SHOWCASE-MINITRACTOR-'.strtoupper($lang),'name'=>$lv?'Demo kompaktais mini traktors 15 HP':'Demo Compact Mini Tractor 15 HP','categories'=>$garden,'image'=>$local,'remote'=>$img['mini-tractor-15hp'],'short'=>$lv?'Kompakts traktors ar pļaušanas, piekabes un sezonas agregātu pakām.':'Compact tractor with mower, trailer and seasonal attachment packages.','description'=>$lv?'<p>Demo mini traktors lielākam dārzam un teritorijas kopšanai.</p>':'<p>Demo compact tractor for larger gardens and estate maintenance.</p>','attribute_name'=>$lv?'Aprīkojums':'Equipment','variations'=>$lv?array(array('label'=>'Pamata traktors','price'=>3290,'stock'=>3),array('label'=>'105 cm pļaušanas bloks','price'=>3790,'stock'=>2),array('label'=>'Pļaušana + piekabe','price'=>4190,'stock'=>1),array('label'=>'Sniega lāpsta + ķēdes','price'=>4490,'stock'=>2),array('label'=>'Pilns sezonas komplekts','price'=>4890,'stock'=>0)):array(array('label'=>'Base tractor','price'=>3290,'stock'=>3),array('label'=>'105 cm mower deck','price'=>3790,'stock'=>2),array('label'=>'Mower + trailer','price'=>4190,'stock'=>1),array('label'=>'Snow blade + chains','price'=>4490,'stock'=>2),array('label'=>'Full seasonal kit','price'=>4890,'stock'=>0))),
        array('type'=>'variable','key'=>'towel-radiator','sku'=>'HG-SHOWCASE-TOWEL-'.strtoupper($lang),'name'=>$lv?'Demo vannas istabas radiators':'Demo Bathroom Radiator','categories'=>$heating,'image'=>$local,'remote'=>$img['towel-radiator'],'short'=>$lv?'Radiators ar vairākiem izmēriem un atsevišķu variantu atlikumu.':'Bathroom radiator with multiple sizes and separate variant stock.','description'=>$lv?'<p>Demo vannas istabas radiators variāciju un atlikumu demonstrācijai.</p>':'<p>Demo bathroom radiator for variation and stock layouts.</p>','attribute_name'=>$lv?'Izmērs':'Size','variations'=>array(array('label'=>'500 × 800 mm','price'=>79,'stock'=>11),array('label'=>'500 × 1000 mm','price'=>94,'stock'=>8),array('label'=>'500 × 1200 mm','price'=>109,'stock'=>4),array('label'=>'600 × 1500 mm','price'=>149,'stock'=>0))),
        array('type'=>'variable','key'=>'boiler-24kw','sku'=>'HG-SHOWCASE-BOILER-'.strtoupper($lang),'name'=>$lv?'Demo apkures katlu sērija':'Demo Heating Boiler Range','categories'=>$heating,'image'=>$local,'remote'=>$img['boiler-24kw'],'short'=>$lv?'Apkures katlu sērija ar jaudas un komplektācijas variantiem.':'Heating boiler range with output and package variants.','description'=>$lv?'<p>Demo katlu sērija apkures kataloga variāciju testiem.</p>':'<p>Demo boiler range for heating-catalogue variation testing.</p>','attribute_name'=>$lv?'Jauda':'Output','variations'=>array(array('label'=>'18 kW','price'=>699,'stock'=>6),array('label'=>'24 kW','price'=>799,'stock'=>4),array('label'=>'30 kW','price'=>929,'stock'=>2),array('label'=>'36 kW','price'=>1099,'stock'=>0))),
        array('type'=>'variable','key'=>'pump-25-60','sku'=>'HG-SHOWCASE-PUMP2560-'.strtoupper($lang),'name'=>$lv?'Demo apkures cirkulācijas sūknis 25-60':'Demo Heating Circulation Pump 25-60','categories'=>$heating,'image'=>$local,'remote'=>$img['pump-25-60'],'short'=>$lv?'Cirkulācijas sūknis ar dažādiem vadības režīmu komplektiem.':'Circulation pump with different control-mode packages.','description'=>$lv?'<p>Demo cirkulācijas sūknis radiatoru un grīdas apkures sistēmām.</p>':'<p>Demo circulation pump for radiator and underfloor-heating systems.</p>','attribute_name'=>$lv?'Komplekts':'Package','variations'=>$lv?array(array('label'=>'Pamata sūknis','price'=>89,'stock'=>15),array('label'=>'Ar izolāciju','price'=>99,'stock'=>8),array('label'=>'Ar vārstu komplektu','price'=>119,'stock'=>3),array('label'=>'Pilns montāžas komplekts','price'=>139,'stock'=>0)):array(array('label'=>'Pump only','price'=>89,'stock'=>15),array('label'=>'With insulation','price'=>99,'stock'=>8),array('label'=>'With valve set','price'=>119,'stock'=>3),array('label'=>'Full installation kit','price'=>139,'stock'=>0))),
        array('type'=>'variable','key'=>'riding-mower-1050a','sku'=>'HG-SHOWCASE-RIDER1050A-'.strtoupper($lang),'name'=>$lv?'Demo braucamais zāles pļāvējs 1050A':'Demo Riding Lawn Mower 1050A','categories'=>$garden,'image'=>$local,'remote'=>$img['riding-mower-1050a'],'short'=>$lv?'Braucams pļāvējs ar dažādām pļaušanas un savākšanas komplektācijām.':'Ride-on mower with several mowing and collection packages.','description'=>$lv?'<p>Demo braucamais pļāvējs lielākām teritorijām.</p>':'<p>Demo ride-on mower for larger gardens and grounds.</p>','attribute_name'=>$lv?'Komplektācija':'Package','variations'=>$lv?array(array('label'=>'Pamatmodelis','price'=>1299,'stock'=>5),array('label'=>'Ar savācējgrozu','price'=>1449,'stock'=>4),array('label'=>'Mulčēšanas komplekts','price'=>1499,'stock'=>2),array('label'=>'Pilns sezonas komplekts','price'=>1699,'stock'=>0)):array(array('label'=>'Base mower','price'=>1299,'stock'=>5),array('label'=>'With grass collector','price'=>1449,'stock'=>4),array('label'=>'Mulching package','price'=>1499,'stock'=>2),array('label'=>'Full seasonal package','price'=>1699,'stock'=>0))),
        array('type'=>'variable','key'=>'air-source-heat-pump','sku'=>'HG-SHOWCASE-ASHP-'.strtoupper($lang),'name'=>$lv?'Demo gaiss–ūdens siltumsūknis':'Demo Air-source Heat Pump','categories'=>$heating,'image'=>$local,'remote'=>$img['air-source-heat-pump'],'short'=>$lv?'Siltumsūkņa modeļu sērija ar dažādu nominālo jaudu.':'Heat-pump model range with several nominal outputs.','description'=>$lv?'<p>Demo siltumsūkņu sērija apkures sistēmu kataloga testiem.</p>':'<p>Demo heat-pump range for heating-system catalogue tests.</p>','attribute_name'=>$lv?'Jauda':'Output','variations'=>array(array('label'=>'5 kW','price'=>2190,'stock'=>5),array('label'=>'8 kW','price'=>2790,'stock'=>4),array('label'=>'12 kW','price'=>3490,'stock'=>2),array('label'=>'16 kW','price'=>4190,'stock'=>0))),
        array('type'=>'variable','key'=>'underfloor-manifold','sku'=>'HG-SHOWCASE-MANIFOLD-'.strtoupper($lang),'name'=>$lv?'Demo grīdas apkures kolektora komplekts':'Demo Underfloor Heating Manifold Kit','categories'=>$heating,'image'=>$local,'remote'=>$img['underfloor-manifold'],'short'=>$lv?'Kolektora komplekti dažādam kontūru skaitam.':'Manifold kits for different numbers of heating loops.','description'=>$lv?'<p>Demo grīdas apkures kolektoru komplekti.</p>':'<p>Demo underfloor-heating manifold kits.</p>','attribute_name'=>$lv?'Kontūras':'Loops','variations'=>$lv?array(array('label'=>'2 kontūras','price'=>119,'stock'=>10),array('label'=>'4 kontūras','price'=>159,'stock'=>7),array('label'=>'6 kontūras','price'=>199,'stock'=>3),array('label'=>'8 kontūras','price'=>239,'stock'=>0)):array(array('label'=>'2 loops','price'=>119,'stock'=>10),array('label'=>'4 loops','price'=>159,'stock'=>7),array('label'=>'6 loops','price'=>199,'stock'=>3),array('label'=>'8 loops','price'=>239,'stock'=>0))),
        array('type'=>'variable','key'=>'radiator-valve-pack','sku'=>'HG-SHOWCASE-VALVES-'.strtoupper($lang),'name'=>$lv?'Demo radiatoru vārstu komplekts':'Demo Radiator Valve Pack','categories'=>$heating,'image'=>$local,'remote'=>$img['radiator-valve-pack'],'short'=>$lv?'Termostatisko vārstu komplekti vienai vai vairākām telpām.':'Thermostatic valve packs for one or several rooms.','description'=>$lv?'<p>Demo termostatisko radiatoru vārstu komplekti.</p>':'<p>Demo thermostatic radiator valve packs.</p>','attribute_name'=>$lv?'Komplekts':'Pack','variations'=>$lv?array(array('label'=>'1 radiatoram','price'=>29,'stock'=>22),array('label'=>'4 radiatoriem','price'=>99,'stock'=>9),array('label'=>'8 radiatoriem','price'=>179,'stock'=>3),array('label'=>'12 radiatoriem','price'=>249,'stock'=>0)):array(array('label'=>'1 radiator','price'=>29,'stock'=>22),array('label'=>'4 radiators','price'=>99,'stock'=>9),array('label'=>'8 radiators','price'=>179,'stock'=>3),array('label'=>'12 radiators','price'=>249,'stock'=>0))),
    );
}

function wpbbshop_v423_seed_products() {
    if (!function_exists('wpbbshop_v422_showcase_categories') || !function_exists('wpbbshop_v422_seed_variable_product')) { return array('created'=>0,'updated'=>0); }
    $cats=wpbbshop_v422_showcase_categories(); $pairs=array(); $count=0;
    foreach (array('en','lv') as $lang) {
        foreach (wpbbshop_v423_product_specs($lang,$cats) as $spec) {
            $id=wpbbshop_v422_seed_variable_product($spec,$lang);
            if (!$id) { continue; }
            update_post_meta($id,'_wpbbshop_v423_demo_image_url',esc_url_raw($spec['remote']));
            update_post_meta($id,'_wpbbshop_demo_image_url',esc_url_raw($spec['remote']));
            $pairs[$spec['key']][$lang]=$id; $count++;
        }
    }
    if (function_exists('pll_save_post_translations')) {
        foreach ($pairs as $pair) { if (!empty($pair['en']) && !empty($pair['lv'])) { pll_save_post_translations(array('en'=>$pair['en'],'lv'=>$pair['lv'])); } }
    }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    return array('created'=>$count,'updated'=>0);
}

function wpbbshop_v423_seed_all() {
    $posts=wpbbshop_v423_seed_posts(); $products=wpbbshop_v423_seed_products();
    update_option('wpbbshop_v423_showcase_version',WPBBSHOP_V423_VERSION,false);
    return array('posts'=>$posts,'products'=>$products);
}

add_action('admin_init', function() {
    if (!current_user_can('manage_options')) { return; }
    if (function_exists('wpbbshop_v429_demo_tools_enabled') && !wpbbshop_v429_demo_tools_enabled()) { return; }
    if (function_exists('wpbbshop_v422_is_local_site') && !wpbbshop_v422_is_local_site()) { return; }
    if ((string)get_option('wpbbshop_v423_showcase_version','') === WPBBSHOP_V423_VERSION) { return; }
    wpbbshop_v423_seed_all();
}, 115);

/** Product matrix rendered below the add-to-cart box to prevent form overlap. */
function wpbbshop_v423_variation_matrix_html($product) {
    if (!$product instanceof WC_Product_Variable) { return ''; }
    $children=array_values(array_filter($product->get_children())); if (!$children) { return ''; }
    ob_start(); ?>
    <section class="wpbb-v423-variation-matrix" aria-label="<?php echo esc_attr(wpbbshop_v423_t('Options and availability','Varianti un pieejamība')); ?>">
      <header><div><span><?php echo esc_html(wpbbshop_v423_t('ALL OPTIONS','VISI VARIANTI')); ?></span><h3><?php echo esc_html(wpbbshop_v423_t('Compare every variant','Salīdzini visus variantus')); ?></h3><p><?php echo esc_html(wpbbshop_v423_t('Price, SKU and real stock before you choose.','Cena, SKU un reāls atlikums vēl pirms izvēles.')); ?></p></div><small><?php echo esc_html(sprintf(wpbbshop_v423_t('%d variations','%d varianti'),count($children))); ?></small></header>
      <div class="wpbb-v423-variation-rows">
      <?php foreach ($children as $variation_id) : $variation=wc_get_product($variation_id); if (!$variation instanceof WC_Product_Variation) { continue; } $attrs=$variation->get_variation_attributes(); $in=$variation->is_in_stock(); $qty=$variation->get_stock_quantity(); $low=$in && $variation->managing_stock() && $qty!==null && (int)$qty<=3; ?>
        <div class="wpbb-v423-variation-row<?php echo $in?'':' is-out'; ?><?php echo $low?' is-low':''; ?>" data-variation-id="<?php echo esc_attr($variation_id); ?>">
          <div class="wpbb-v423-variation-main"><div class="wpbb-v423-variation-attrs"><?php echo function_exists('wpbbshop_v422_variation_attributes_html') ? wp_kses_post(wpbbshop_v422_variation_attributes_html($variation)) : ''; ?></div><small class="wpbb-v423-variation-sku">SKU <?php echo esc_html($variation->get_sku() ?: '—'); ?></small></div>
          <div class="wpbb-v423-variation-price"><?php echo wp_kses_post($variation->get_price_html()); ?></div>
          <div class="wpbb-v423-variation-stock"><span></span><?php echo esc_html(function_exists('wpbbshop_v422_variation_stock_text') ? wpbbshop_v422_variation_stock_text($variation) : ($in?'In stock':'Out of stock')); ?></div>
          <button type="button" class="wpbb-v422-variation-choose wpbb-v423-variation-choose" data-wpbb-variation="<?php echo esc_attr(wp_json_encode($attrs)); ?>" <?php disabled(!$in); ?>><?php echo esc_html($in ? wpbbshop_v423_t('Choose','Izvēlēties') : wpbbshop_v423_t('Unavailable','Nav pieejams')); ?></button>
        </div>
      <?php endforeach; ?>
      </div>
    </section>
    <?php return (string) ob_get_clean();
}

/** Blog helpers used by single.php. */
function wpbbshop_v423_is_guide_post($post_id=0) {
    $post_id=$post_id?:get_the_ID();
    return (string)get_post_meta($post_id,'_wpbbshop_v422_guide',true)==='1';
}

function wpbbshop_v423_read_minutes($post_id=0) {
    $post_id=$post_id?:get_the_ID(); $stored=absint(get_post_meta($post_id,'_wpbbshop_v423_read_minutes',true));
    if ($stored) { return $stored; }
    $words=str_word_count(wp_strip_all_tags((string)get_post_field('post_content',$post_id)));
    return max(2,(int)ceil($words/180));
}

function wpbbshop_v423_related_guides($post_id,$limit=3) {
    $lang=(string)get_post_meta($post_id,'_wpbbshop_v422_lang',true); if (!$lang) { $lang=wpbbshop_v423_lang(); }
    return get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>$limit,'post__not_in'=>array($post_id),'meta_key'=>'_wpbbshop_v422_order','orderby'=>array('meta_value_num'=>'ASC','date'=>'DESC'),'meta_query'=>array(array('key'=>'_wpbbshop_v422_guide','value'=>'1'),array('key'=>'_wpbbshop_v422_lang','value'=>$lang)),'suppress_filters'=>true));
}

add_action('wp_enqueue_scripts', function() {
    $css=get_stylesheet_directory().'/assets/css/v423-blog-showcase-polish.css';
    if (is_readable($css)) {
        wp_enqueue_style('wpbbshop-v423-blog-showcase-polish',get_stylesheet_directory_uri().'/assets/css/v423-blog-showcase-polish.css',array('wpbbshop-v422-demo-blog-variations'),(string)filemtime($css));
    }
}, PHP_INT_MAX);
