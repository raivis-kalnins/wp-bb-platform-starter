<?php
/**
 * Shared starter-site contract.
 *
 * The parent owns importer mechanics and valid Gutenberg serialization only.
 * Child themes supply sector copy, imagery, patterns and presentation.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_demo_enrich_profile( $profile ) {
    $id = sanitize_key( $profile['id'] ?? 'business' );
    $uri = trailingslashit( get_stylesheet_directory_uri() );
    $dir = trailingslashit( get_stylesheet_directory() );
    $sets = array(
        'business' => array(
            'contact' => array( 'email'=>'hello@example.com','phone'=>'+44 20 7946 0180','address'=>'20 Finsbury Square, London','hours'=>'Mon–Fri · 09:00–17:30','map'=>'Finsbury Square, London' ),
            'history' => array( array('2018','Started with strategy and design.','A small senior team focused on useful digital work.'), array('2020','Built a reusable delivery system.','Shared components made launches faster and easier to maintain.'), array('2023','Expanded into ongoing growth.','Content, optimisation and support became part of the same service.'), array('2026','One clear platform.','Strategy, design, delivery and improvement now work as one system.') ),
            'cases' => array( array('B2B platform refresh','A clearer service architecture and conversion path for a complex offer.','42%','more qualified enquiries'), array('Product launch system','A reusable campaign framework for multiple regions and product teams.','6','markets launched'), array('Content operations redesign','A component-led editorial workflow with less production friction.','2×','publishing pace') ),
            'gallery' => array( array($uri.'assets/img/demo/office-wide.jpg','Studio workspace'), array($uri.'assets/img/demo/office-detail.jpg','Team workspace'), array($uri.'assets/img/demo/office-planning.jpg','Project workspace') ),
            'blog' => array( 'How to plan a service website around real decisions','A practical content system for teams that publish often','What a maintainable design system looks like in WordPress','Five ways to improve a homepage without rebuilding it','When to use a pattern, a block or a custom integration' ),
            'blog_categories' => array( 'Strategy', 'Design', 'Delivery', 'Insights' ),
            'blog_paths' => array( $dir.'assets/img/demo/office-wide.jpg', $dir.'assets/img/demo/office-detail.jpg', $dir.'assets/img/demo/office-planning.jpg' ),
        ),
        'realestate' => array(
            'contact' => array( 'email'=>'property@example.com','phone'=>'+44 20 7946 0280','address'=>'18 High Street, Richmond','hours'=>'Mon–Sat · 08:30–18:00','map'=>'Richmond, London' ),
            'history' => array( array('2012','Local agency founded.','Residential sales started with a neighbourhood-first approach.'), array('2016','Lettings team launched.','Landlord support and managed lettings joined the business.'), array('2021','Digital viewings and valuations.','Search, viewing and valuation journeys moved into one platform.'), array('2026','Area expertise at scale.','Local insight now supports sales, lettings and new developments.') ),
            'cases' => array( array('Riverside sale campaign','Positioning, photography and buyer matching for a premium family home.','18','qualified viewings'), array('Landlord portfolio relaunch','A clearer lettings journey for a multi-property portfolio.','11 days','to first let'), array('New development launch','Search-led presentation for a small collection of new homes.','92%','reserved in phase one') ),
            'gallery' => array( array($uri.'assets/img/properties/willow-house.jpg','Willow House'),array($uri.'assets/img/properties/riverside-loft.jpg','Riverside Loft'),array($uri.'assets/img/properties/cedar-cottage.jpg','Cedar Cottage'),array($uri.'assets/img/properties/harbour-house.jpg','Harbour House') ),
            'blog' => array( 'How to prepare a home for its first week on the market','What buyers ask before booking a viewing','A landlord checklist for a smoother tenancy','How local data can guide a realistic valuation','New development buying: what to review before reserving' ),
            'blog_categories' => array( 'Buying', 'Selling', 'Lettings', 'Market insight' ),
            'blog_paths' => array( $dir.'assets/img/properties/willow-house.jpg',$dir.'assets/img/properties/riverside-loft.jpg',$dir.'assets/img/properties/cedar-cottage.jpg',$dir.'assets/img/properties/harbour-house.jpg' ),
        ),
        'tech' => array(
            'contact' => array( 'email'=>'support@example.com','phone'=>'+44 20 7946 0380','address'=>'8 Innovation Way, London','hours'=>'Mon–Fri · 08:00–18:00','map'=>'Old Street, London' ),
            'history' => array( array('2019','Specialist store launched.','A curated range replaced an overwhelming catalogue.'), array('2021','Buying advice added.','Guides and comparisons became part of product discovery.'), array('2024','Support-first fulfilment.','Delivery, setup and account journeys were redesigned together.'), array('2026','A cleaner technology store.','Products, advice and support now live in one fast system.') ),
            'cases' => array( array('Hybrid work setup','A practical laptop, display and accessory bundle for distributed teams.','24h','dispatch on stock'), array('Creator audio desk','A compact audio setup with simpler comparisons and useful guidance.','4.9/5','customer satisfaction'), array('Secure smart home','A privacy-led starter kit with clear compatibility information.','7','devices compared') ),
            'gallery' => array( array($uri.'assets/img/store/tech-hero.jpg','Technology desk'),array($uri.'assets/img/store/ultralight-laptop.jpg','Ultralight laptop'),array($uri.'assets/img/store/studio-monitor.jpg','Studio monitor'),array($uri.'assets/img/store/noise-cancelling-headphones.jpg','Headphones') ),
            'blog' => array( 'How to build a home-office setup that lasts','What matters when comparing monitors for creative work','A simple guide to USB-C docks and charging','Choosing headphones for work, travel and focus','Smart-home privacy: a practical buying checklist' ),
            'blog_categories' => array( 'Buying guides', 'Work setup', 'Audio', 'Smart home' ),
            'blog_paths' => array( $dir.'assets/img/store/tech-hero.jpg',$dir.'assets/img/store/ultralight-laptop.jpg',$dir.'assets/img/store/studio-monitor.jpg',$dir.'assets/img/store/noise-cancelling-headphones.jpg' ),
        ),
        'clothes' => array(
            'contact' => array( 'email'=>'hello@example.com','phone'=>'+44 20 7946 0480','address'=>'24 Mercer Street, London','hours'=>'Mon–Sat · 10:00–18:00','map'=>'Covent Garden, London' ),
            'history' => array( array('2017','A smaller wardrobe idea.','The collection started with useful everyday pieces.'), array('2020','Materials first.','Care, provenance and repeat wear shaped buying decisions.'), array('2023','Seasonless edits.','Core pieces were organised into flexible wardrobe stories.'), array('2026','Quiet ecommerce.','Editorial content and shopping now share the same visual language.') ),
            'cases' => array( array('Workday capsule','A restrained edit built around layering, fabric and repeat wear.','12','core pieces'), array('Weekend travel edit','A compact set of pieces designed to pack and combine easily.','4','looks from one bag'), array('Material care programme','Clear care content designed to extend the useful life of garments.','3','care guides') ),
            'gallery' => array( array($uri.'assets/img/products/relaxed-cotton-shirt.jpg','Relaxed cotton shirt'),array($uri.'assets/img/products/utility-overshirt.jpg','Utility overshirt'),array($uri.'assets/img/products/merino-crew-knit.jpg','Merino knit'),array($uri.'assets/img/products/canvas-weekend-bag.jpg','Canvas weekend bag') ),
            'blog' => array( 'How to build a smaller wardrobe around repeat wear','A simple guide to cotton, merino and everyday care','Three ways to style an overshirt across seasons','Packing a useful weekend wardrobe without overthinking it','Why material care belongs in the shopping journey' ),
            'blog_categories' => array( 'Style', 'Materials', 'Care', 'Travel' ),
            'blog_paths' => array( $dir.'assets/img/products/relaxed-cotton-shirt.jpg',$dir.'assets/img/products/utility-overshirt.jpg',$dir.'assets/img/products/merino-crew-knit.jpg',$dir.'assets/img/products/canvas-weekend-bag.jpg' ),
        ),
        'building' => array(
            'contact' => array( 'email'=>'jobs@example.com','phone'=>'+44 20 7946 0680','address'=>'12 Workshop Lane, London','hours'=>'Mon–Sat · 07:00–18:00','map'=>'London' ),
            'history' => array( array('2016','Maintenance team formed.','Plumbing and electrical call-outs started with a small mobile team.'), array('2019','Developer support added.','Fit-out, snagging and coordinated multi-trade work joined the offer.'), array('2023','Structured job intake.','Property type, urgency and scope became part of every quote request.'), array('2026','One trade-services platform.','Maintenance, projects and developer work now share one operational system.') ),
            'cases' => array( array('Managed maintenance programme','Planned plumbing, electrical and fabric repairs across a managed property portfolio.','94%','first-visit resolution'), array('Developer snagging package','A coordinated multi-trade close-out programme for a new residential phase.','38','units completed'), array('Commercial fit-out support','Structured electrical, plumbing and finishing work for an occupied workspace.','12 days','programme saved') ),
            'gallery' => array( array($uri.'assets/img/demo/item-5.jpg','Maintenance team base'),array($uri.'assets/img/demo/item-1.jpg','Residential call-out'),array($uri.'assets/img/demo/item-2.jpg','Developer maintenance'),array($uri.'assets/img/demo/item-3.jpg','Planned property works') ),
            'blog' => array('What to include in a useful maintenance request','Planning developer snagging without repeated site visits','When a plumbing call-out becomes planned work','A practical electrical maintenance checklist','How multi-trade teams coordinate occupied properties'),
            'blog_categories' => array('Maintenance','Plumbing','Electrical','Developer work'),
            'blog_paths' => array($dir.'assets/img/demo/item-5.jpg',$dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-3.jpg'),
        ),
        'hotel' => array(
            'contact' => array( 'email'=>'stay@example.com','phone'=>'+44 20 7946 0780','address'=>'1 Riverside Walk, London','hours'=>'Daily · 07:00–23:00','map'=>'London' ),
            'history' => array( array('2015','First rooms opened.','A small city stay focused on calm rooms and useful service.'), array('2019','Suites and family stays.','Larger room types and family layouts joined the collection.'), array('2023','Direct booking journeys.','Room discovery, availability enquiries and stay details moved into one site.'), array('2026','A more useful hotel platform.','Rooms, local guidance and direct enquiries now share one clear experience.') ),
            'cases' => array( array('Weekend stay refresh','Room comparison and clearer inclusions improved direct booking enquiries.','31%','more direct enquiries'), array('Family room launch','Dedicated family-room content made capacity and layout easier to compare.','6','room types'), array('Long-stay content update','Workspace, storage and amenity information was added for longer visits.','4.8/5','guest clarity score') ),
            'gallery' => array( array($uri.'assets/img/demo/hero-photo.jpg','Hotel arrival'),array($uri.'assets/img/demo/item-1.jpg','Garden King'),array($uri.'assets/img/demo/item-2.jpg','City Suite'),array($uri.'assets/img/demo/item-6.jpg','Accessible Queen') ),
            'blog' => array('How to choose the right room for a weekend stay','What families should check before booking a hotel room','A useful guide to accessible room details','Planning a longer city stay without overpacking','What makes direct hotel booking easier to trust'),
            'blog_categories' => array('Rooms','Stays','Local guide','Hotel news'),
            'blog_paths' => array($dir.'assets/img/demo/hero-photo.jpg',$dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-6.jpg'),
        ),
        'insurance' => array(
            'contact' => array( 'email'=>'cover@example.com','phone'=>'+44 20 7946 0880','address'=>'30 King Street, London','hours'=>'Mon–Fri · 08:00–18:00','map'=>'London' ),
            'history' => array( array('2018','Roadside cover launched.','Simple vehicle-assistance packages started with clear monthly pricing.'), array('2021','More vehicle types.','Vans, motorbikes and mobility vehicles joined the cover finder.'), array('2024','Quote and purchase together.','Product comparison, configuration and quote routes moved into one journey.'), array('2026','Clearer insurance commerce.','Cover, billing and assistance choices now live on complete product pages.') ),
            'cases' => array( array('Roadside comparison refresh','Eight packages reorganised around vehicle type, recovery area and price.','8','cover packages'), array('Quote journey redesign','Vehicle and renewal information captured before team follow-up.','42%','fewer incomplete leads'), array('Account journey cleanup','Purchase, account and policy contact routes brought into one navigation system.','1','clear customer hub') ),
            'gallery' => array( array($uri.'assets/img/demo/hero.jpg','Vehicle cover overview'),array($uri.'assets/img/demo/about.jpg','Assistance package'),array($uri.'assets/img/demo/item-4.jpg','Roadside cover'),array($uri.'assets/img/demo/item-6.jpg','European cover') ),
            'blog' => array('What to compare before choosing roadside cover','When home assistance is useful','A practical guide to European breakdown cover','What van drivers should check before buying cover','How yearly and monthly roadside plans differ'),
            'blog_categories' => array('Roadside','Vehicles','Buying guide','Claims help'),
            'blog_paths' => array($dir.'assets/img/demo/hero.jpg',$dir.'assets/img/demo/about.jpg',$dir.'assets/img/demo/item-4.jpg',$dir.'assets/img/demo/item-6.jpg'),
        ),
        'courses' => array(
            'contact' => array( 'email'=>'learn@example.com','phone'=>'+44 20 7946 0980','address'=>'Online learning studio, London','hours'=>'Mon–Fri · 09:00–17:00','map'=>'London' ),
            'history' => array( array('2020','First practical courses.','Short lessons focused on useful skills rather than long lecture libraries.'), array('2022','Resources added.','Video, downloadable material and quizzes joined each course.'), array('2024','Structured course discovery.','Topics, level and study time became easier to compare.'), array('2026','A complete learning starter.','Courses, lessons, resources and knowledge checks now work as one demo.') ),
            'cases' => array( array('Automation learning path','Short Python lessons combined video, material and checks in one course.','7','lessons'), array('Team onboarding track','A reusable course format for structured internal learning.','10h','guided study'), array('Resource library cleanup','Supporting downloads were tied to the lesson where they are most useful.','1','clear curriculum') ),
            'gallery' => array( array($uri.'assets/img/demo/hero-photo.jpg','Learning dashboard'),array($uri.'assets/img/demo/item-1.jpg','Practical course'),array($uri.'assets/img/demo/item-2.jpg','Lesson resources'),array($uri.'assets/img/demo/item-3.jpg','Knowledge check') ),
            'blog' => array('How to structure a practical online course','Why short lessons improve course completion','Where supporting resources belong in a curriculum','How to write useful knowledge checks','A simple approach to course discovery and levels'),
            'blog_categories' => array('Learning design','Courses','Resources','Assessment'),
            'blog_paths' => array($dir.'assets/img/demo/hero-photo.jpg',$dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-3.jpg'),
        ),
        'restaurant' => array(
            'contact' => array( 'email'=>'table@example.com','phone'=>'+44 20 7946 1080','address'=>'14 Market Lane, London','hours'=>'Tue–Sun · 12:00–23:00','map'=>'London' ),
            'history' => array( array('2018','Neighbourhood restaurant opened.','A concise seasonal menu built around a few trusted producers.'), array('2021','Producer stories added.','Ingredients, provenance and menu notes became part of the guest experience.'), array('2024','Menu discovery improved.','Course, dietary and price filters made the menu easier to scan.'), array('2026','One restaurant platform.','Menu, booking routes, events and editorial stories share one visual system.') ),
            'cases' => array( array('Seasonal menu launch','A focused menu edit organised around starters, mains and desserts.','6','featured dishes'), array('Dietary discovery update','Dietary labels and allergens moved closer to each dish.','3','filter dimensions'), array('Producer story series','Editorial stories tied ingredients and suppliers back to the menu.','12','producer notes') ),
            'gallery' => array( array($uri.'assets/img/demo/hero.jpg','Seasonal table'),array($uri.'assets/img/demo/item-1.jpg','Roast squash'),array($uri.'assets/img/demo/item-3.jpg','Sea bass'),array($uri.'assets/img/demo/item-6.jpg','Chocolate tart') ),
            'blog' => array('What is on the menu this season','How producers shape a smaller restaurant menu','A practical guide to dietary menu information','Why restaurant photography should support the dish','Planning a relaxed neighbourhood dinner'),
            'blog_categories' => array('Menu','Producers','Restaurant news','Guides'),
            'blog_paths' => array($dir.'assets/img/demo/hero.jpg',$dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-3.jpg',$dir.'assets/img/demo/item-6.jpg'),
        ),
        'automotive' => array(
            'cases' => array( array('Used vehicle finder','Inventory organised by body type, fuel and availability.','24','demo vehicles'), array('Service booking route','Workshop enquiries structured around vehicle and service need.','3','clear steps'), array('Rental comparison','Short-term vehicles presented with simpler daily-rate comparison.','6','rental types') ),
            'gallery' => array( array($uri.'assets/img/demo/item-1.jpg','New SUV'),array($uri.'assets/img/demo/item-2.jpg','Used estate'),array($uri.'assets/img/demo/item-3.jpg','Daily rental'),array($uri.'assets/img/demo/item-4.jpg','Electric vehicle') ),
            'blog' => array('What to check before buying a used vehicle','How to compare rental vehicles for a weekend','A practical seasonal service checklist','When an EV charger changes the ownership experience','How vehicle filters help buyers narrow the shortlist'),
            'blog_categories' => array('Buying','Servicing','Rental','Electric'),
            'blog_paths' => array($dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-3.jpg',$dir.'assets/img/demo/item-4.jpg'),
        ),
        'logistics' => array(
            'cases' => array( array('Regional delivery network','Service pages organised around route, timing and load requirements.','18','service regions'), array('Shipment enquiry refresh','Structured job details reduced back-and-forth before quoting.','35%','faster qualification'), array('Fleet capability guide','Vehicle and load information made easier to compare.','9','fleet profiles') ),
            'gallery' => array( array($uri.'assets/img/demo/item-1.jpg','Regional delivery'),array($uri.'assets/img/demo/item-2.jpg','Warehouse handling'),array($uri.'assets/img/demo/item-3.jpg','Fleet operations'),array($uri.'assets/img/demo/item-4.jpg','Scheduled transport') ),
            'blog' => array('What information helps a transport quote','How to plan a time-critical delivery','A practical guide to pallet and load information','When scheduled transport beats ad-hoc booking','How logistics teams communicate delivery constraints'),
            'blog_categories' => array('Transport','Warehousing','Fleet','Planning'),
            'blog_paths' => array($dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-3.jpg',$dir.'assets/img/demo/item-4.jpg'),
        ),
        'travel' => array(
            'cases' => array( array('City break collection','Short trips organised around pace, season and useful inclusions.','12','trip ideas'), array('Family itinerary refresh','Accommodation and activity details grouped into one practical view.','5 days','sample itinerary'), array('Slow travel guide','Longer-stay content built around neighbourhoods and rail connections.','8','local guides') ),
            'gallery' => array( array($uri.'assets/img/demo/hero-photo.jpg','Featured trip'),array($uri.'assets/img/demo/item-1.jpg','City break'),array($uri.'assets/img/demo/item-2.jpg','Coastal stay'),array($uri.'assets/img/demo/item-3.jpg','Local experience') ),
            'blog' => array('How to choose a city break that fits your pace','What to check before booking a family trip','A practical guide to slower rail travel','Packing for a short trip without overdoing it','How neighbourhood guides improve a stay'),
            'blog_categories' => array('City breaks','Family travel','Slow travel','Guides'),
            'blog_paths' => array($dir.'assets/img/demo/hero-photo.jpg',$dir.'assets/img/demo/item-1.jpg',$dir.'assets/img/demo/item-2.jpg',$dir.'assets/img/demo/item-3.jpg'),
        ),
        'medicine' => array(
            'contact' => array( 'email'=>'care@example.com','phone'=>'+44 20 7946 0580','address'=>'10 Harley Street, London','hours'=>'Mon–Fri · 08:00–19:00','map'=>'Harley Street, London' ),
            'history' => array( array('2014','Clinic opened.','General medicine and specialist referrals began under one roof.'), array('2018','Multidisciplinary team.','Cardiology, dermatology and paediatrics joined the same patient pathway.'), array('2022','Digital appointments.','Booking and pre-visit information moved into a clearer online journey.'), array('2026','Connected patient care.','Specialists, booking and health guidance now work as one system.') ),
            'cases' => array( array('Same-week cardiology pathway','A clearer route from first concern to specialist assessment.','48h','typical triage'), array('Preventive health programme','Screening, advice and follow-up organised as one patient journey.','4','care checkpoints'), array('Multilingual patient support','Pre-visit information structured for international patients.','8','languages supported') ),
            'gallery' => array( array($uri.'assets/img/doctors/amelia-hart.svg','Dr Amelia Hart'),array($uri.'assets/img/doctors/daniel-lee.svg','Dr Daniel Lee'),array($uri.'assets/img/doctors/sofia-martin.svg','Dr Sofia Martin'),array($uri.'assets/img/doctors/noah-williams.svg','Dr Noah Williams') ),
            'blog' => array( 'How to prepare for a specialist consultation','When a same-week appointment can help','A practical guide to preventive health checks','What to bring to a dermatology appointment','Helping children feel more comfortable before a clinic visit' ),
            'blog_categories' => array( 'General health', 'Cardiology', 'Dermatology', 'Paediatrics' ),
            'blog_paths' => array( $dir.'assets/img/doctors/amelia-hart.svg',$dir.'assets/img/doctors/daniel-lee.svg',$dir.'assets/img/doctors/sofia-martin.svg',$dir.'assets/img/doctors/noah-williams.svg' ),
        ),
    );
    $extra = $sets[ $id ] ?? $sets['business'];
    foreach ( $extra as $key => $value ) if ( empty( $profile[ $key ] ) ) $profile[ $key ] = $value;
    return $profile;
}

function wp_theme_get_demo_profile() {
    $profile = array(
        'id' => 'business',
        'name' => __( 'Business', 'wp-theme' ),
        'eyebrow' => __( 'Independent business', 'wp-theme' ),
        'hero_title' => __( 'A flexible website that grows with your business.', 'wp-theme' ),
        'hero_text' => __( 'Launch a clear, accessible website using native WordPress blocks and reusable WP BBuilder layouts.', 'wp-theme' ),
        'hero_image' => get_template_directory_uri() . '/assets/img/demo/default-hero.svg',
        'primary_label' => __( 'Explore services', 'wp-theme' ),
        'primary_url' => '#services',
        'secondary_label' => __( 'Talk to us', 'wp-theme' ),
        'secondary_url' => '#contact',
        'commerce' => 'auto',
        'services' => array(
            array( __( 'Strategy', 'wp-theme' ), __( 'A practical plan shaped around your market, goals and customers.', 'wp-theme' ) ),
            array( __( 'Design', 'wp-theme' ), __( 'Clear interfaces and content patterns built for real people.', 'wp-theme' ) ),
            array( __( 'Delivery', 'wp-theme' ), __( 'A maintainable WordPress build your team can keep improving.', 'wp-theme' ) ),
        ),
        'industries' => array(
            array( __( 'Professional services', 'wp-theme' ), __( 'Clear journeys for expert teams and considered buying decisions.', 'wp-theme' ) ),
            array( __( 'Retail and ecommerce', 'wp-theme' ), __( 'Fast product discovery, useful content and confident conversion.', 'wp-theme' ) ),
            array( __( 'Property and places', 'wp-theme' ), __( 'Search-led experiences built around location and intent.', 'wp-theme' ) ),
            array( __( 'Technology teams', 'wp-theme' ), __( 'Product storytelling that makes complex offers easier to understand.', 'wp-theme' ) ),
        ),
        'stats' => array(
            array( '01', __( 'Clear editable system', 'wp-theme' ) ),
            array( '02', __( 'Performance-minded build', 'wp-theme' ) ),
            array( '03', __( 'Reusable page patterns', 'wp-theme' ) ),
            array( '04', __( 'Ready to customise', 'wp-theme' ) ),
        ),
        'process' => array(
            array( '01', __( 'Discover', 'wp-theme' ), __( 'Clarify the audience, offer and most important journeys.', 'wp-theme' ) ),
            array( '02', __( 'Shape', 'wp-theme' ), __( 'Turn strategy into a focused content and component system.', 'wp-theme' ) ),
            array( '03', __( 'Launch', 'wp-theme' ), __( 'Publish a fast first version, then improve it with real evidence.', 'wp-theme' ) ),
        ),
        'about_title' => __( 'Useful websites, built around useful content.', 'wp-theme' ),
        'about_text' => __( 'Familiar WordPress editing tools, strong content hierarchy and reusable sections keep the site straightforward to manage as content grows.', 'wp-theme' ),
        'about_image' => get_template_directory_uri() . '/assets/img/demo/default-about.svg',
        'cta_title' => __( 'Ready to build the next version of your website?', 'wp-theme' ),
        'cta_text' => __( 'Start with the imported structure, then make it unmistakably yours.', 'wp-theme' ),
        'footer_text' => __( 'A focused, editable WordPress website built for clear content and straightforward publishing.', 'wp-theme' ),
        'page_labels' => array(
            'about' => __( 'About', 'wp-theme' ),
            'services' => __( 'Services', 'wp-theme' ),
            'industries' => __( 'Industries', 'wp-theme' ),
            'contact' => __( 'Contact', 'wp-theme' ),
            'blog' => __( 'Insights', 'wp-theme' ),
        ),
    );

    $profile = apply_filters( 'wp_theme_demo_profile', $profile );
    $profile = is_array( $profile ) ? $profile : array();
    $profile = wp_theme_demo_enrich_profile( $profile );
    $custom = wp_theme_sector_customizer_values();
    foreach ( array( 'hero_title', 'hero_text', 'hero_image' ) as $field ) {
        if ( isset( $custom[ $field ] ) && '' !== $custom[ $field ] ) $profile[ $field ] = $custom[ $field ];
    }
    return wp_parse_args( $profile, array( 'id'=>'business','name'=>__( 'Business','wp-theme' ),'commerce'=>'auto','services'=>array(),'industries'=>array(),'stats'=>array(),'process'=>array() ) );
}

function wp_theme_sector_customizer_values() {
    $values = array();
    foreach ( array( 'brand_color', 'hero_title', 'hero_text', 'hero_image', 'card_radius' ) as $field ) {
        $value = get_theme_mod( 'wp_theme_sector_' . $field, false );
        if ( false !== $value ) $values[ $field ] = $value;
    }
    return $values;
}

function wp_theme_sector_customize_register( $customizer ) {
    $customizer->add_section( 'wp_theme_sector_presentation', array(
        'title' => __( 'Sector demo presentation', 'wp-theme' ),
        'description' => __( 'These choices are stored separately for each child theme.', 'wp-theme' ),
        'priority' => 35,
    ) );
    $fields = array(
        'brand_color' => array( 'label'=>__( 'Brand colour','wp-theme' ),'type'=>'color','default'=>'#3157ff' ),
        'hero_title' => array( 'label'=>__( 'Hero title','wp-theme' ),'type'=>'text','default'=>'' ),
        'hero_text' => array( 'label'=>__( 'Hero text','wp-theme' ),'type'=>'textarea','default'=>'' ),
        'hero_image' => array( 'label'=>__( 'Hero image URL','wp-theme' ),'type'=>'url','default'=>'' ),
        'card_radius' => array( 'label'=>__( 'Card radius','wp-theme' ),'type'=>'text','default'=>'18px' ),
    );
    foreach ( $fields as $key => $field ) {
        $customizer->add_setting( 'wp_theme_sector_' . $key, array( 'default'=>$field['default'],'sanitize_callback'=>'sanitize_text_field','transport'=>'refresh' ) );
        $customizer->add_control( 'wp_theme_sector_' . $key, array( 'section'=>'wp_theme_sector_presentation','label'=>$field['label'],'type'=>$field['type'] ) );
    }
}
add_action( 'customize_register', 'wp_theme_sector_customize_register' );

function wp_theme_sector_customizer_css( $brand_default, $radius_default, $brand_variable = '--sector-primary', $radius_variable = '--sector-radius' ) {
    $brand = sanitize_hex_color( get_theme_mod( 'wp_theme_sector_brand_color', $brand_default ) ) ?: $brand_default;
    $radius = sanitize_text_field( get_theme_mod( 'wp_theme_sector_card_radius', $radius_default ) );
    if ( ! preg_match( '/^\d+(?:\.\d+)?(?:px|rem|em|%)$/', $radius ) ) $radius = $radius_default;
    return ':root{' . $brand_variable . ':' . esc_attr( $brand ) . ';' . $radius_variable . ':' . esc_attr( $radius ) . ';}';
}

function wp_theme_demo_commerce_enabled( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    if ( true === ( $profile['commerce'] ?? null ) ) return true;
    if ( false === ( $profile['commerce'] ?? null ) ) return false;
    return class_exists( 'WooCommerce' );
}

function wp_theme_demo_profile_body_class( $classes ) {
    $profile = wp_theme_get_demo_profile();
    $classes[] = 'wp-theme-sector-' . sanitize_html_class( $profile['id'] ?? 'business' );
    if ( wp_theme_demo_commerce_enabled( $profile ) ) $classes[] = 'wp-theme-sector-commerce';
    return $classes;
}
add_filter( 'body_class', 'wp_theme_demo_profile_body_class' );

/** Small block helpers. All imported child content must be a real block. */
function wp_theme_demo_block_attrs( $attrs ) {
    return wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}
function wp_theme_demo_p( $text, $class = '' ) {
    $attrs = $class ? ' ' . wp_theme_demo_block_attrs( array( 'className'=>$class ) ) : '';
    return '<!-- wp:paragraph' . $attrs . ' --><p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . wp_kses_post( $text ) . '</p><!-- /wp:paragraph -->';
}
function wp_theme_demo_h( $text, $level = 2, $class = '' ) {
    $attrs = array( 'level'=>(int) $level ); if ( $class ) $attrs['className'] = $class;
    return '<!-- wp:heading ' . wp_theme_demo_block_attrs( $attrs ) . ' --><h' . (int) $level . ' class="wp-block-heading' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . esc_html( $text ) . '</h' . (int) $level . '><!-- /wp:heading -->';
}
function wp_theme_demo_buttons( $primary_label, $primary_url, $secondary_label = '', $secondary_url = '' ) {
    $html = '<!-- wp:buttons {"className":"wp-theme-demo-buttons"} --><div class="wp-block-buttons wp-theme-demo-buttons">';
    $html .= '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $primary_url ) . '">' . esc_html( $primary_label ) . '</a></div><!-- /wp:button -->';
    if ( $secondary_label ) $html .= '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $secondary_url ) . '">' . esc_html( $secondary_label ) . '</a></div><!-- /wp:button -->';
    return $html . '</div><!-- /wp:buttons -->';
}
function wp_theme_demo_icon_svg( $name = 'spark' ) {
    $paths = array(
        'strategy' => '<path d="M4 17.5 9.5 12l3.2 3.2L20 7.8"/><path d="M14.5 7.8H20v5.5"/>',
        'design' => '<rect x="4" y="5" width="16" height="14" rx="3"/><path d="M8 9h8M8 13h5"/>',
        'delivery' => '<path d="M4 12h12M12 7l5 5-5 5"/><circle cx="6" cy="18" r="1.5"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="5.5"/><path d="m15 15 5 5"/>',
        'support' => '<path d="M5 13v-2a7 7 0 0 1 14 0v2"/><path d="M5 13H3v4h4v-4H5Zm14 0h2v4h-4v-4h2Z"/><path d="M17 19c-1 1-2.6 1.5-4.5 1.5"/>',
        'secure' => '<path d="M12 3 19 6v5c0 4.8-2.8 8-7 10-4.2-2-7-5.2-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'location' => '<path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"/><circle cx="12" cy="10" r="2"/>',
        'health' => '<path d="M12 21s-7-4.3-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 11c0 5.7-7 10-7 10Z"/><path d="M9 12h2l1-2 1 4 1-2h2"/>',
        'home' => '<path d="m4 11 8-7 8 7"/><path d="M6 10v10h12V10M10 20v-6h4v6"/>',
        'cart' => '<path d="M4 5h2l2 10h9l2-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
        'spark' => '<path d="m12 3 1.4 4.6L18 9l-4.6 1.4L12 15l-1.4-4.6L6 9l4.6-1.4L12 3Z"/><path d="m18 15 .8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15Z"/>',
    );
    $body = $paths[ sanitize_key( $name ) ] ?? $paths['spark'];
    return '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}
function wp_theme_demo_card_block( $title, $text, $number = '', $icon = '' ) {
    $inner = '';
    if ( $icon ) {
        $inner .= '<!-- wp:html --><span class="wp-theme-card-icon" aria-hidden="true">' . wp_theme_demo_icon_svg( $icon ) . '</span><!-- /wp:html -->';
    }
    $inner .= $number ? wp_theme_demo_p( esc_html( $number ), 'wp-theme-card-number' ) : '';
    $inner .= wp_theme_demo_h( $title, 3 );
    $inner .= wp_theme_demo_p( esc_html( $text ) );
    return '<!-- wp:group {"className":"wp-theme-sector-card motion-fade-up","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-sector-card motion-fade-up">' . $inner . '</div><!-- /wp:group -->';
}

function wp_theme_demo_cards_markup( $items, $class_name ) {
    if ( empty( $items ) ) return '';
    $count = count( $items );
    $lg = $count >= 4 ? 3 : ( $count === 3 ? 4 : 6 );
    $icons = array( 'strategy', 'design', 'delivery', 'search', 'support', 'secure', 'location', 'spark' );
    $out = '<!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container','gutterX'=>'gx-4','gutterY'=>'gy-4','customClasses'=>$class_name ) ) . ' -->';
    foreach ( $items as $index => $item ) {
        if ( is_string( $item ) ) $item = array( $item, __( 'A focused route into this part of the offer.', 'wp-theme' ) );
        $icon = $item['icon'] ?? ( $item[2] ?? $icons[ $index % count( $icons ) ] );
        $out .= '<!-- wp:wpbb/column ' . wp_theme_demo_block_attrs( array( 'xs'=>12,'md'=>6,'lg'=>$lg ) ) . ' -->' . wp_theme_demo_card_block( $item[0] ?? '', $item[1] ?? '', '', $icon ) . '<!-- /wp:wpbb/column -->';
    }
    return $out . '<!-- /wp:wpbb/row -->';
}

function wp_theme_demo_stats_markup( $items, $compact = false ) {
    if ( empty( $items ) ) return '';
    $icons = array( '', '', '', '' );
    $proof_class = 'wp-theme-sector-proof' . ( $compact ? ' wp-theme-sector-proof--compact' : '' );
    $out = '<!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container','gutterX'=>'gx-3','gutterY'=>'gy-3','customClasses'=>$proof_class ) ) . ' -->';
    foreach ( array_values( $items ) as $index => $item ) {
        $attrs = array(
            'number'       => (string) ( $item[0] ?? '' ),
            'label'        => (string) ( $item[1] ?? '' ),
            'icon'         => $icons[ $index % count( $icons ) ],
            'styleVariant' => 'sector-proof',
            'className'    => 'wp-theme-sector-proof__item',
        );
        $column_attrs = $compact ? '{"xs":6,"lg":6}' : '{"xs":6,"lg":3}';
        $out .= '<!-- wp:wpbb/column ' . $column_attrs . ' --><!-- wp:wpbb/fun-fact ' . wp_theme_demo_block_attrs( $attrs ) . ' /--><!-- /wp:wpbb/column -->';
    }
    return $out . '<!-- /wp:wpbb/row -->';
}

function wp_theme_demo_process_markup( $items ) {
    if ( empty( $items ) ) return '';
    $out = '<!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container','gutterX'=>'gx-4','gutterY'=>'gy-4','customClasses'=>'wp-theme-sector-process-grid' ) ) . ' -->';
    foreach ( $items as $item ) {
        $badge = '<!-- wp:wpbb/badge ' . wp_theme_demo_block_attrs( array( 'text'=>(string) ( $item[0] ?? '' ), 'variant'=>'light', 'pill'=>true, 'className'=>'wp-theme-process-badge' ) ) . ' /-->';
        $out .= '<!-- wp:wpbb/column {"xs":12,"md":4} -->' . $badge . wp_theme_demo_card_block( $item[1] ?? '', $item[2] ?? '' ) . '<!-- /wp:wpbb/column -->';
    }
    return $out . '<!-- /wp:wpbb/row -->';
}

function wp_theme_demo_gallery_markup( $profile ) {
    $slides = array();
    foreach ( (array) ( $profile['gallery'] ?? array() ) as $row ) {
        if ( empty( $row[0] ) ) continue;
        $slides[] = array( 'type'=>'gallery','eyebrow'=>__( 'Gallery','wp-theme' ),'title'=>$row[1] ?? '','text'=>$row[2] ?? '','image'=>$row[0] );
    }
    if ( count( $slides ) < 2 ) return '';
    $attrs = array( 'slides'=>$slides,'slidesPerView'=>3,'slidesTablet'=>2,'slidesMobile'=>1,'spaceBetween'=>22,'speed'=>650,'loop'=>false,'rewind'=>true,'autoplay'=>false,'demoStyle'=>'gallery','showPagination'=>true,'showNavigation'=>false );
    return '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-gallery-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-gallery-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html__( 'Gallery','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $profile['gallery_heading'] ?? __( 'A closer look at the work, people and places behind the service.','wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} --><!-- wp:wpbb/swiper ' . wp_theme_demo_block_attrs( $attrs ) . ' /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
}

function wp_theme_demo_case_studies_markup( $profile ) {
    $items = (array) ( $profile['cases'] ?? array() );
    if ( ! $items ) return '';
    $out = '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-cases-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-cases-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html__( 'Selected stories','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $profile['cases_heading'] ?? __( 'Recent work and measurable outcomes.','wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:wpbb/row {"containerClass":"container","gutterX":"gx-4","gutterY":"gy-4","customClasses":"wp-theme-case-grid"} -->';
    foreach ( array_slice( $items, 0, 3 ) as $index => $case ) {
        $metric = '<!-- wp:wpbb/fun-fact ' . wp_theme_demo_block_attrs( array( 'number'=>(string) ( $case[2] ?? '' ), 'label'=>(string) ( $case[3] ?? '' ), 'icon'=>'', 'styleVariant'=>'case-metric', 'className'=>'wp-theme-case-card__metric' ) ) . ' /-->';
        $out .= '<!-- wp:wpbb/column {"xs":12,"md":4} --><!-- wp:group {"className":"wp-theme-case-card motion-fade-up","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-case-card motion-fade-up">' . wp_theme_demo_p( sprintf( '%02d', $index + 1 ), 'wp-theme-case-card__index' ) . wp_theme_demo_h( $case[0] ?? '', 3 ) . wp_theme_demo_p( esc_html( $case[1] ?? '' ) ) . $metric . '</div><!-- /wp:group --><!-- /wp:wpbb/column -->';
    }
    return $out . '<!-- /wp:wpbb/row --></div><!-- /wp:group -->';
}

function wp_theme_demo_timeline_markup( $profile ) {
    $items = (array) ( $profile['history'] ?? array() );
    if ( ! $items ) return '';
    $out = '<!-- wp:group {"className":"wp-theme-history-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-history-section"><!-- wp:wpbb/row {"containerClass":"container","gutterX":"gx-5","gutterY":"gy-5"} --><!-- wp:wpbb/column {"xs":12,"lg":4} -->' . wp_theme_demo_p( esc_html__( 'Our story','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( __( 'How the organisation has grown.','wp-theme' ), 2 ) . wp_theme_demo_p( esc_html__( 'Key milestones that shaped the team, service and way we work today.','wp-theme' ) ) . '<!-- /wp:wpbb/column --><!-- wp:wpbb/column {"xs":12,"lg":8} --><!-- wp:group {"className":"wp-theme-timeline","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-timeline">';
    foreach ( $items as $item ) {
        $dot = '<!-- wp:html --><span class="wp-theme-timeline__dot" aria-hidden="true"></span><!-- /wp:html -->';
        $out .= '<!-- wp:group {"className":"wp-theme-timeline__item motion-fade-up","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-timeline__item motion-fade-up">' . $dot . wp_theme_demo_p( esc_html( $item[0] ?? '' ), 'wp-theme-timeline__year' ) . wp_theme_demo_h( $item[1] ?? '', 3 ) . wp_theme_demo_p( esc_html( $item[2] ?? '' ) ) . '</div><!-- /wp:group -->';
    }
    return $out . '</div><!-- /wp:group --><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
}

function wp_theme_demo_contact_details_markup( $profile ) {
    $c = (array) ( $profile['contact'] ?? array() );
    $links = '<!-- wp:wpbb/contact-links ' . wp_theme_demo_block_attrs( array(
        'email'       => $c['email'] ?? 'hello@example.com',
        'phone'       => $c['phone'] ?? '+44 20 7946 0000',
        'emailIcon'   => 'email',
        'phoneIcon'   => 'whatsapp',
        'layoutClass' => 'wp-theme-contact-links-stack',
        'className'   => 'wp-theme-contact-links-block',
    ) ) . ' /-->';
    $rows = array(
        array( 'location', __( 'Visit','wp-theme' ), $c['address'] ?? 'London, United Kingdom' ),
        array( 'clock', __( 'Opening hours','wp-theme' ), $c['hours'] ?? 'Mon–Fri · 09:00–17:30' ),
    );
    $out = '<!-- wp:group {"className":"wp-theme-contact-details","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-contact-details">' . $links;
    foreach ( $rows as $row ) {
        $icon = 'location' === $row[0] ? 'location' : 'spark';
        $icon_block = '<!-- wp:html --><span class="wp-theme-card-icon" aria-hidden="true">' . wp_theme_demo_icon_svg( $icon ) . '</span><!-- /wp:html -->';
        $detail = '<!-- wp:group {"className":"wp-theme-contact-detail__copy","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-contact-detail__copy">' . wp_theme_demo_p( esc_html( $row[1] ), 'wp-theme-contact-detail__label' ) . wp_theme_demo_h( $row[2], 4 ) . '</div><!-- /wp:group -->';
        $out .= '<!-- wp:group {"className":"wp-theme-contact-detail","layout":{"type":"flex","flexWrap":"nowrap"}} --><div class="wp-block-group wp-theme-contact-detail">' . $icon_block . $detail . '</div><!-- /wp:group -->';
    }
    return $out . '</div><!-- /wp:group -->';
}

function wp_theme_demo_blog_preview_markup( $profile ) {
    $blog_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' );
    return '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-insights-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-insights-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html__( 'Latest thinking','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $profile['blog_heading'] ?? __( 'Latest guides, news and practical advice.','wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:group {"className":"container wp-theme-blog-preview-container","layout":{"type":"default"}} --><div class="wp-block-group container wp-theme-blog-preview-container"><!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"displayLayout":{"type":"grid","columns":3},"className":"wp-theme-blog-preview"} --><div class="wp-block-query wp-theme-blog-preview"><!-- wp:post-template --><!-- wp:group {"className":"wp-theme-blog-card motion-fade-up","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-blog-card motion-fade-up"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10"} /--><!-- wp:post-date {"className":"wp-theme-blog-card__date"} /--><!-- wp:post-title {"isLink":true,"level":3} /--><!-- wp:post-excerpt {"moreText":"Read article"} /--></div><!-- /wp:group --><!-- /wp:post-template --></div><!-- /wp:query --></div><!-- /wp:group --><!-- wp:group {"className":"container wp-theme-blog-preview-actions","layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-group container wp-theme-blog-preview-actions">' . wp_theme_demo_buttons( __( 'View all articles','wp-theme' ), $blog_url ) . '</div><!-- /wp:group --></div><!-- /wp:group -->';
}

function wp_theme_demo_partner_slider_markup( $profile ) {
    $base = get_template_directory_uri() . '/assets/img/partners/';
    $names = array( 'Northstar', 'Atlas', 'Horizon', 'Summit', 'Vertex', 'Harbour' );
    $slides = array();
    foreach ( $names as $index => $name ) {
        $slides[] = array(
            'type' => 'gallery',
            'eyebrow' => __( 'Partner', 'wp-theme' ),
            'title' => $name,
            'text' => '',
            'image' => $base . 'partner-' . sprintf( '%02d', $index + 1 ) . '.svg',
        );
    }
    $attrs = array(
        'slides' => $slides,
        'slidesPerView' => 5,
        'slidesTablet' => 3,
        'slidesMobile' => 2,
        'spaceBetween' => 18,
        'speed' => 650,
        'loop' => true,
        'rewind' => false,
        'autoplay' => true,
        'autoplayDelay' => 2800,
        'pauseOnHover' => true,
        'demoStyle' => 'logos',
        'showPagination' => false,
        'showNavigation' => false,
    );
    $heading = $profile['partners_heading'] ?? __( 'Trusted by teams, clients and delivery partners', 'wp-theme' );
    return '<!-- wp:group {"className":"wp-theme-partners-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-partners-section"><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} --><p class="wp-theme-partners-heading">' . esc_html( $heading ) . '</p><!-- wp:wpbb/swiper ' . wp_theme_demo_block_attrs( $attrs ) . ' /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
}

function wp_theme_build_sector_homepage_content() {
    $p = wp_theme_get_demo_profile();
    $slides = ! empty( $p['hero_slides'] ) && is_array( $p['hero_slides'] ) ? array_values( $p['hero_slides'] ) : array(
        array( 'type'=>'hero','eyebrow'=>$p['eyebrow'] ?? '','title'=>$p['hero_title'] ?? '','text'=>$p['hero_text'] ?? '','image'=>$p['hero_image'] ?? '','buttonText'=>$p['primary_label'] ?? '','buttonUrl'=>$p['primary_url'] ?? '#' ),
        array( 'type'=>'hero','eyebrow'=>__( 'Built to edit','wp-theme' ),'title'=>$p['about_title'] ?? '','text'=>$p['about_text'] ?? '','image'=>$p['about_image'] ?? '','buttonText'=>$p['secondary_label'] ?? __( 'Learn more','wp-theme' ),'buttonUrl'=>$p['secondary_url'] ?? '#services' ),
    );
    $swiper = array( 'slides'=>$slides,'slidesPerView'=>1,'slidesTablet'=>1,'slidesMobile'=>1,'spaceBetween'=>0,'speed'=>700,'loop'=>false,'rewind'=>true,'autoplay'=>false,'demoStyle'=>'hero','showPagination'=>true,'showNavigation'=>true );
    $content = '<!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container-fluid','customClasses'=>'wp-theme-sector-hero','uniqueId'=>'wpbb-sector-hero' ) ) . ' --><!-- wp:wpbb/column {"xs":12} --><!-- wp:wpbb/swiper ' . wp_theme_demo_block_attrs( $swiper ) . ' /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->';
    $content .= wp_theme_demo_partner_slider_markup( $p );
    $content = apply_filters( 'wp_theme_demo_after_hero_sections', $content, $p );

    $content .= '<!-- wp:group {"anchor":"services","className":"wp-theme-section-shell wp-theme-services-section","layout":{"type":"default"}} --><div id="services" class="wp-block-group wp-theme-section-shell wp-theme-services-section">';
    $content .= '<!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $p['services_eyebrow'] ?? __( 'What we do','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['services_heading'] ?? __( 'Practical services built around what you need next.', 'wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->';
    $content .= wp_theme_demo_cards_markup( $p['services'] ?? array(), 'wp-theme-sector-services' ) . '</div><!-- /wp:group -->';

    $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-about-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-about-section"><!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container','gutterX'=>'gx-5','gutterY'=>'gy-5','customClasses'=>'align-items-center wp-theme-sector-media-text' ) ) . ' -->';
    $content .= '<!-- wp:wpbb/column {"xs":12,"lg":6} --><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"wp-theme-sector-media-text__media"} --><figure class="wp-block-image size-large wp-theme-sector-media-text__media"><img src="' . esc_url( $p['about_image'] ?? '' ) . '" alt=""/></figure><!-- /wp:image --><!-- /wp:wpbb/column -->';
    $content .= '<!-- wp:wpbb/column {"xs":12,"lg":6} -->' . wp_theme_demo_p( esc_html( $p['about_eyebrow'] ?? __( 'Why this approach works','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['about_title'] ?? '', 2 ) . wp_theme_demo_p( esc_html( $p['about_text'] ?? '' ) ) . wp_theme_demo_buttons( $p['secondary_label'] ?? __( 'Learn more','wp-theme' ), $p['secondary_url'] ?? wp_theme_demo_page_url( 'about' ) ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';

    $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-industries-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-industries-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $p['industries_eyebrow'] ?? __( 'Designed for real teams','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['industries_heading'] ?? __( 'Flexible enough to fit different audiences and offers.', 'wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->' . wp_theme_demo_cards_markup( $p['industries'] ?? array(), 'wp-theme-sector-industries' ) . '</div><!-- /wp:group -->';

    $stats_markup = wp_theme_demo_stats_markup( $p['stats'] ?? array() );
    if ( $stats_markup ) {
        $content .= '<!-- wp:group {"className":"wp-theme-home-stats","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-home-stats">' . $stats_markup . '</div><!-- /wp:group -->';
    }
    $content .= apply_filters( 'wp_theme_demo_extra_home_sections', '', $p );
    $content .= wp_theme_demo_case_studies_markup( $p );
    $content .= wp_theme_demo_gallery_markup( $p );

    if ( wp_theme_demo_commerce_enabled( $p ) ) {
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-shop-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-shop-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $p['shop_eyebrow'] ?? __( 'Shop','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['shop_heading'] ?? __( 'Find the right product without the noise.', 'wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} --><!-- wp:wpbb/catalogue {"title":"","postsToShow":8,"postType":"product","taxonomy":"product_cat","sortBy":"menu_order","sortOrder":"ASC","showImage":true,"showExcerpt":true,"className":"wp-theme-home-product-catalogue"} /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
    }

    $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-process-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-process-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $p['process_eyebrow'] ?? __( 'How it works','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['process_heading'] ?? __( 'Enough structure to move quickly. Enough flexibility to stay useful.', 'wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->' . wp_theme_demo_process_markup( $p['process'] ?? array() ) . '</div><!-- /wp:group -->';

    $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-faq-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-faq-section"><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12,"lg":12} -->' . wp_theme_demo_p( esc_html__( 'FAQ','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $p['faq_heading'] ?? __( 'Common questions, answered clearly.', 'wp-theme' ), 2 );
    $faqs = array(
        array( __( 'Can I change every section?','wp-theme' ), __( 'Yes. The page is made from editable WordPress and WP BBuilder blocks.','wp-theme' ) ),
        array( __( 'Can the website grow with the organisation?','wp-theme' ), __( 'Yes. Keep the editable structure and add only the integrations the active project actually needs.','wp-theme' ) ),
        array( __( 'Will the imported menus remain editable?','wp-theme' ), __( 'Yes. Header, utility, footer and mega-menu content are normal WordPress objects managed in wp-admin.','wp-theme' ) ),
    );
    foreach ( $faqs as $faq ) $content .= '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html( $faq[0] ) . '</summary>' . wp_theme_demo_p( esc_html( $faq[1] ) ) . '</details><!-- /wp:details -->';
    $content .= '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';

    $content .= wp_theme_demo_blog_preview_markup( $p );

    $content .= '<!-- wp:wpbb/cta-section ' . wp_theme_demo_block_attrs( array( 'title'=>$p['cta_title'] ?? '', 'titleTag'=>'h2', 'text'=>$p['cta_text'] ?? '', 'buttonText'=>__( 'Start a conversation','wp-theme' ), 'buttonUrl'=>wp_theme_demo_page_url( 'contact' ), 'className'=>'wp-theme-home-cta wp-theme-home-cta--bbuilder' ) ) . ' /-->';
    return $content;
}

function wp_theme_sector_page_content( $type, $profile ) {
    $label = wp_theme_demo_page_label( $type, $profile );
    $intro = array(
        'about' => $profile['about_text'] ?? __( 'Introduce the team, purpose and approach behind the work.','wp-theme' ),
        'services' => __( 'Explore the services, expertise and practical support available from our team.','wp-theme' ),
        'industries' => __( 'See where our experience is most useful and how we support different needs.','wp-theme' ),
        'contact' => __( 'Speak with our team, send an enquiry or find the details you need to get started.','wp-theme' ),
    );
    $content = '<!-- wp:group {"className":"wp-theme-inner-hero","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-inner-hero"><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $profile['name'] ?? '' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $label, 1 ) . wp_theme_demo_p( esc_html( $intro[ $type ] ?? '' ), 'wp-theme-sector-lead' ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
    if ( 'services' === $type ) {
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell">' . wp_theme_demo_cards_markup( $profile['services'] ?? array(), 'wp-theme-sector-services wp-theme-inner-grid' ) . '</div><!-- /wp:group -->';
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-process-section wp-theme-inner-process-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-process-section wp-theme-inner-process-section"><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-section-heading"} --><!-- wp:wpbb/column {"xs":12} -->' . wp_theme_demo_p( esc_html( $profile['process_eyebrow'] ?? __( 'How it works','wp-theme' ) ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $profile['process_heading'] ?? __( 'A simple process from first question to measurable release.', 'wp-theme' ), 2 ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->' . wp_theme_demo_process_markup( $profile['process'] ?? array() ) . '</div><!-- /wp:group -->';
        $content .= wp_theme_demo_case_studies_markup( $profile );
    } elseif ( 'industries' === $type ) {
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell">' . wp_theme_demo_cards_markup( $profile['industries'] ?? array(), 'wp-theme-sector-industries wp-theme-inner-grid' ) . '</div><!-- /wp:group -->' . wp_theme_demo_gallery_markup( $profile );
    } elseif ( 'about' === $type ) {
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-about-page-intro","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-about-page-intro"><!-- wp:wpbb/row ' . wp_theme_demo_block_attrs( array( 'containerClass'=>'container','gutterX'=>'gx-5','gutterY'=>'gy-5','customClasses'=>'align-items-center' ) ) . ' --><!-- wp:wpbb/column {"xs":12,"lg":6} --><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"wp-theme-sector-media-text__media"} --><figure class="wp-block-image size-large wp-theme-sector-media-text__media"><img src="' . esc_url( $profile['about_image'] ?? '' ) . '" alt=""/></figure><!-- /wp:image --><!-- /wp:wpbb/column --><!-- wp:wpbb/column {"xs":12,"lg":6} -->' . wp_theme_demo_p( esc_html__( 'What guides us','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $profile['about_title'] ?? '', 2 ) . wp_theme_demo_p( esc_html( $profile['about_text'] ?? '' ), 'wp-theme-sector-lead' ) . wp_theme_demo_stats_markup( array_slice( (array) ( $profile['stats'] ?? array() ), 0, 4 ), true ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
        $content .= wp_theme_demo_timeline_markup( $profile ) . wp_theme_demo_case_studies_markup( $profile ) . wp_theme_demo_gallery_markup( $profile );
    } elseif ( 'contact' === $type ) {
        $c = (array) ( $profile['contact'] ?? array() );
        $fields = array(
            array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
            array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
            array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Optional','wp-theme'),'step'=>1),
            array('type'=>'select','name'=>'topic','label'=>__('What can we help with?','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Choose a topic','wp-theme'),'options'=>"General enquiry\nNew project\nSupport\nAppointment\nOther",'step'=>1),
            array('type'=>'textarea','name'=>'message','label'=>__('Message','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Tell us a little about what you need','wp-theme'),'step'=>1),
        );
        $form_attrs = array( 'showTitle'=>true,'formTitle'=>__( 'Send us a message','wp-theme' ),'emailSubject'=>sprintf( __( '%s website enquiry','wp-theme' ), $profile['name'] ?? 'Website' ),'stylePreset'=>'soft','labelPosition'=>'top','gap'=>3,'fields'=>$fields,'className'=>'wp-theme-contact-form' );
        $map_attrs = array( 'address'=>$c['map'] ?? $c['address'] ?? 'London, United Kingdom','zoom'=>14,'height'=>'520px','className'=>'wp-theme-contact-map' );
        $content .= '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-contact-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-contact-section"><!-- wp:wpbb/row {"containerClass":"container","gutterX":"gx-5","gutterY":"gy-5","customClasses":"align-items-start"} --><!-- wp:wpbb/column {"xs":12,"lg":5} -->' . wp_theme_demo_p( esc_html__( 'Contact details','wp-theme' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( __( 'Talk to the right person and get a useful response.','wp-theme' ), 2 ) . wp_theme_demo_contact_details_markup( $profile ) . '<!-- /wp:wpbb/column --><!-- wp:wpbb/column {"xs":12,"lg":7} --><!-- wp:wpbb/dynamic-form ' . wp_theme_demo_block_attrs( $form_attrs ) . ' /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --><!-- wp:wpbb/row {"containerClass":"container","customClasses":"wp-theme-contact-map-row"} --><!-- wp:wpbb/column {"xs":12} --><!-- wp:wpbb/google-map ' . wp_theme_demo_block_attrs( $map_attrs ) . ' /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
    }
    return $content;
}

function wp_theme_demo_page_label( $slug, $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    return $profile['page_labels'][ $slug ] ?? ucfirst( str_replace( '-', ' ', $slug ) );
}

function wp_theme_seed_sector_pages( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    if ( function_exists( 'wp_theme_seed_site_essential_pages' ) ) wp_theme_seed_site_essential_pages();
    foreach ( array( 'about','services','industries','contact' ) as $slug ) {
        $existing = get_page_by_path( $slug );
        $args = array( 'post_type'=>'page','post_status'=>'publish','post_title'=>wp_theme_demo_page_label( $slug, $profile ),'post_name'=>$slug,'post_content'=>wp_theme_sector_page_content( $slug, $profile ) );
        if ( $existing instanceof WP_Post ) { $args['ID'] = $existing->ID; wp_update_post( $args ); } else { wp_insert_post( $args ); }
    }
    do_action( 'wp_theme_seed_sector_pages', $profile );
}

function wp_theme_demo_navigation_items( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    $items = array(
        array( 'key'=>'home','title'=>__( 'Home','wp-theme' ),'type'=>'homepage','locations'=>array('header','footer') ),
        array( 'key'=>'services','title'=>wp_theme_demo_page_label('services',$profile),'slug'=>'services','locations'=>array('header','footer') ),
        array( 'key'=>'industries','title'=>wp_theme_demo_page_label('industries',$profile),'slug'=>'industries','locations'=>array('header','footer') ),
        array( 'key'=>'about','title'=>wp_theme_demo_page_label('about',$profile),'slug'=>'about','locations'=>array('header','footer','top') ),
        array( 'key'=>'blog','title'=>wp_theme_demo_page_label('blog',$profile),'type'=>'posts_page','locations'=>array('header','footer') ),
        array( 'key'=>'contact','title'=>wp_theme_demo_page_label('contact',$profile),'slug'=>'contact','locations'=>array('header','footer','top') ),
    );
    if ( wp_theme_demo_commerce_enabled( $profile ) ) {
        array_splice( $items, 1, 0, array( array( 'key'=>'shop','title'=>__( 'Shop','wp-theme' ),'type'=>'post_type_archive','object'=>'product','locations'=>array('header','footer') ) ) );
    }
    $items = apply_filters( 'wp_theme_demo_navigation_items', $items, $profile );
    // Final deterministic de-duplication: child/commerce filters can otherwise
    // add the same Shop or destination twice on a refreshed demo import.
    $seen = array();
    $seen_titles = array();
    $clean = array();
    foreach ( (array) $items as $item ) {
        $title_key = strtolower( trim( wp_strip_all_tags( (string) ( $item['title'] ?? '' ) ) ) );
        $destination = strtolower( trim( (string) ( $item['slug'] ?? $item['object'] ?? $item['url'] ?? $item['key'] ?? '' ) ) );
        $signature = $title_key . '|' . $destination;
        if ( isset( $seen[ $signature ] ) || ( $title_key && isset( $seen_titles[ $title_key ] ) ) ) continue;
        $seen[ $signature ] = true;
        if ( $title_key ) $seen_titles[ $title_key ] = true;
        $clean[] = $item;
    }
    return $clean;
}

function wp_theme_demo_menu_item_args( $item, $homepage_id ) {
    $args = array( 'menu-item-title'=>$item['title'] ?? '','menu-item-status'=>'publish','menu-item-type'=>'custom','menu-item-url'=>'#' );
    $type = $item['type'] ?? 'page';
    if ( 'homepage' === $type ) { $args['menu-item-type']='post_type'; $args['menu-item-object']='page'; $args['menu-item-object-id']=$homepage_id; }
    elseif ( 'posts_page' === $type ) {
        $page_id = absint( get_option( 'page_for_posts' ) );
        if ( $page_id ) { $args['menu-item-type']='post_type'; $args['menu-item-object']='page'; $args['menu-item-object-id']=$page_id; }
        else $args['menu-item-url'] = home_url( '/blog/' );
    } elseif ( 'post_type_archive' === $type ) {
        $object = sanitize_key( $item['object'] ?? '' );
        $url = $object ? get_post_type_archive_link( $object ) : '';
        if ( 'product' === $object && function_exists( 'wc_get_page_permalink' ) ) $url = wc_get_page_permalink( 'shop' );
        $args['menu-item-url'] = $url ?: home_url( '/' . $object . '/' );
    } elseif ( ! empty( $item['slug'] ) ) {
        $page = get_page_by_path( sanitize_title( $item['slug'] ) );
        if ( $page ) { $args['menu-item-type']='post_type'; $args['menu-item-object']='page'; $args['menu-item-object-id']=$page->ID; }
        else $args['menu-item-url'] = wp_theme_demo_page_url( $item['slug'] );
    } elseif ( ! empty( $item['url'] ) ) $args['menu-item-url'] = $item['url'];
    return $args;
}

function wp_theme_demo_mega_menu_definitions( $profile = null ) {
    $p = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    $service_links = array();
    foreach ( array_slice( (array) ( $p['services'] ?? array() ), 0, 4 ) as $r ) {
        if ( is_array( $r ) ) {
            $label = sanitize_text_field( (string) ( $r[0] ?? '' ) );
            $desc  = sanitize_text_field( (string) ( $r[1] ?? '' ) );
            $url   = isset( $r[2] ) ? esc_url_raw( (string) $r[2] ) : '';
        } else {
            $label = sanitize_text_field( (string) $r );
            $desc  = $label ? sprintf( __( 'Explore %s services and practical information.', 'wp-theme' ), $label ) : '';
            $url   = '';
        }
        if ( $label ) $service_links[] = array( $label, $desc, $url );
    }
    $industry_links = array();
    foreach ( array_slice( (array) ( $p['industries'] ?? array() ), 0, 4 ) as $r ) {
        if ( is_array( $r ) ) {
            $label = sanitize_text_field( (string) ( $r[0] ?? '' ) );
            $desc  = sanitize_text_field( (string) ( $r[1] ?? '' ) );
            $url   = isset( $r[2] ) ? esc_url_raw( (string) $r[2] ) : '';
        } else {
            $label = sanitize_text_field( (string) $r );
            $desc  = $label ? sprintf( __( 'See how the website supports %s.', 'wp-theme' ), $label ) : '';
            $url   = '';
        }
        if ( $label ) $industry_links[] = array( $label, $desc, $url );
    }
    $defs = array(
        'services' => array(
            'title' => sprintf( __( '%s — Services navigation','wp-theme' ), $p['name'] ?? __( 'Website','wp-theme' ) ),
            'target_key' => 'services',
            'eyebrow' => __( 'Explore','wp-theme' ),
            'heading' => wp_theme_demo_page_label( 'services', $p ),
            'intro' => __( 'Move directly to the part of the offer that matters to you.','wp-theme' ),
            'columns' => array(
                array( 'title'=>__( 'Services','wp-theme' ),'links'=>$service_links ),
                array( 'title'=>__( 'Who we help','wp-theme' ),'links'=>$industry_links ),
                array( 'title'=>__( 'Useful links','wp-theme' ),'links'=>array(
                    array( wp_theme_demo_page_label( 'about',$p ), __( 'Meet the team and understand the approach.','wp-theme' ), wp_theme_demo_page_url( 'about' ) ),
                    array( wp_theme_demo_page_label( 'blog',$p ), __( 'Read useful news, ideas and guides.','wp-theme' ), get_permalink( get_option('page_for_posts') ) ?: home_url('/blog/') ),
                    array( wp_theme_demo_page_label( 'contact',$p ), __( 'Start a conversation.','wp-theme' ), wp_theme_demo_page_url( 'contact' ) ),
                ) ),
            ),
        ),
    );
    if ( wp_theme_demo_commerce_enabled( $p ) ) {
        $defs['shop'] = array(
            'title' => sprintf( __( '%s — Shop navigation','wp-theme' ), $p['name'] ?? __( 'Store','wp-theme' ) ),
            'target_key' => 'shop',
            'eyebrow' => __( 'Shop','wp-theme' ),
            'heading' => __( 'Find products faster.','wp-theme' ),
            'intro' => __( 'Use categories, search and filters to move quickly from browsing to the right product.','wp-theme' ),
            'columns' => array(
                array( 'title'=>__( 'Browse','wp-theme' ),'links'=>array(
                    array( __( 'All products','wp-theme' ), __( 'Browse the complete catalogue.','wp-theme' ), function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/') ),
                    array( __( 'On sale','wp-theme' ), __( 'See current offers and promotions.','wp-theme' ), add_query_arg('on_sale','1',function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')) ),
                    array( __( 'In stock','wp-theme' ), __( 'Show products ready to order.','wp-theme' ), add_query_arg('in_stock','1',function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')) ),
                ) ),
                array( 'title'=>__( 'Help me choose','wp-theme' ),'links'=>$service_links ),
                array( 'title'=>__( 'Account','wp-theme' ),'links'=>array(
                    array( __( 'My account','wp-theme' ), __( 'Orders, addresses and account details.','wp-theme' ), function_exists('wp_theme_header_account_url') ? wp_theme_header_account_url() : ( function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url() ) ),
                    array( __( 'Cart','wp-theme' ), __( 'Review the products you selected.','wp-theme' ), function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/') ),
                    array( __( 'Contact','wp-theme' ), __( 'Ask the team for product advice.','wp-theme' ), wp_theme_demo_page_url('contact') ),
                ) ),
            ),
        );
    }
    return apply_filters( 'wp_theme_demo_mega_menu_definitions', $defs, $p );
}

function wp_theme_demo_mega_content( $definition ) {
    $out = '<!-- wp:wpbb/row {"containerClass":"container-fluid","gutterX":"gx-4","gutterY":"gy-4","customClasses":"wp-theme-mega-layout"} -->';
    $out .= '<!-- wp:wpbb/column {"xs":12,"lg":3} -->' . wp_theme_demo_p( esc_html( $definition['eyebrow'] ?? '' ), 'wp-theme-sector-eyebrow' ) . wp_theme_demo_h( $definition['heading'] ?? '', 2, 'wp-theme-mega-title' ) . wp_theme_demo_p( esc_html( $definition['intro'] ?? '' ), 'wp-theme-mega-intro' ) . '<!-- /wp:wpbb/column -->';
    $columns = array_slice( (array) ( $definition['columns'] ?? array() ), 0, 3 );
    foreach ( $columns as $column ) {
        $out .= '<!-- wp:wpbb/column {"xs":12,"md":4,"lg":3} -->' . wp_theme_demo_h( $column['title'] ?? '', 3, 'wp-theme-mega-heading' ) . '<!-- wp:group {"className":"wp-theme-mega-links","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-mega-links">';
        foreach ( (array) ( $column['links'] ?? array() ) as $link ) {
            $url = $link[2] ?? '#';
            $out .= '<!-- wp:group {"className":"wp-theme-mega-link-card","layout":{"type":"constrained"}} --><div class="wp-block-group wp-theme-mega-link-card">' . wp_theme_demo_h( $link[0] ?? '', 4 ) . wp_theme_demo_p( esc_html( $link[1] ?? '' ) );
            if ( $url && '#' !== $url ) $out .= wp_theme_demo_p( '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Explore','wp-theme' ) . ' →</a>', 'wp-theme-mega-link-action' );
            $out .= '</div><!-- /wp:group -->';
        }
        $out .= '</div><!-- /wp:group --><!-- /wp:wpbb/column -->';
    }
    return $out . '<!-- /wp:wpbb/row -->';
}

function wp_theme_seed_demo_mega_menus( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();

    // Remove exact v3 starter titles that contained invalid generated block markup.
    // User-created mega menus are never touched.
    $legacy_titles = array_filter( array(
        isset( $profile['name'] ) ? $profile['name'] . ' navigation' : '',
        isset( $profile['name'] ) ? $profile['name'] . ' navigation · Mega Menu' : '',
    ) );
    foreach ( $legacy_titles as $legacy_title ) {
        $legacy = get_page_by_title( $legacy_title, OBJECT, 'megamenu' );
        if ( $legacy instanceof WP_Post ) {
            wp_delete_post( $legacy->ID, true );
        }
    }

    $ids = array();
    foreach ( wp_theme_demo_mega_menu_definitions( $profile ) as $key => $definition ) {
        $title = $definition['title'] ?? ucfirst( $key ) . ' navigation';
        $existing = get_page_by_title( $title, OBJECT, 'megamenu' );
        $args = array( 'post_title'=>$title,'post_status'=>'publish','post_type'=>'megamenu','post_content'=>wp_theme_demo_mega_content( $definition ) );
        $id = 0;
        if ( $existing instanceof WP_Post ) { $args['ID']=$existing->ID; $id=wp_update_post($args); } else $id=wp_insert_post($args);
        if ( $id && ! is_wp_error( $id ) ) {
            update_post_meta( $id, '_wp_theme_demo_generated', 1 );
            update_post_meta( $id, '_wp_theme_demo_profile', sanitize_key( $profile['id'] ?? 'business' ) );
            $ids[ $key ] = array( 'id'=>(int)$id,'target_key'=>$definition['target_key'] ?? $key );
            if ( taxonomy_exists( 'megamenu-cat' ) ) wp_set_object_terms( $id, sanitize_title( $profile['name'] ?? 'starter' ), 'megamenu-cat' );
        }
    }
    return $ids;
}

/** Backward compatible single mega-menu helper. */
function wp_theme_seed_demo_mega_menu( $profile = null ) {
    $all = wp_theme_seed_demo_mega_menus( $profile );
    $first = reset( $all );
    return is_array( $first ) ? (int) $first['id'] : 0;
}

function wp_theme_create_demo_menus( $homepage_id, $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    $items = wp_theme_demo_navigation_items( $profile );
    $locations = get_nav_menu_locations();
    $profile_id = sanitize_key( $profile['id'] ?? 'business' );
    $profile_name = sanitize_text_field( $profile['name'] ?? ucfirst( $profile_id ) );
    $menus = array(
        'header' => array( 'name'=>$profile_name . ' — Header','location'=>'wp-header-menu' ),
        'top' => array( 'name'=>$profile_name . ' — Utility','location'=>'wp-header-top-menu' ),
        'footer' => array( 'name'=>$profile_name . ' — Footer','location'=>'wp-footer-menu' ),
    );
    foreach ( $menus as $type => $config ) {
        $menu = wp_get_nav_menu_object( $config['name'] );
        $id = $menu ? (int) $menu->term_id : wp_create_nav_menu( $config['name'] );
        if ( is_wp_error( $id ) ) continue;
        update_term_meta( $id, '_wp_theme_demo_profile', $profile_id );
        update_term_meta( $id, '_wp_theme_demo_managed', 1 );
        foreach ( wp_get_nav_menu_items( $id ) ?: array() as $old ) wp_delete_post( $old->ID, true );
        $key_to_id = array();
        foreach ( $items as $item ) {
            if ( empty( $item['locations'] ) || ! in_array( $type, $item['locations'], true ) ) continue;
            $args = wp_theme_demo_menu_item_args( $item, $homepage_id );
            if ( ! empty( $item['parent_key'] ) && ! empty( $key_to_id[ $item['parent_key'] ] ) ) $args['menu-item-parent-id'] = $key_to_id[ $item['parent_key'] ];
            $item_id = wp_update_nav_menu_item( $id, 0, $args );
            if ( ! is_wp_error( $item_id ) && ! empty( $item['key'] ) ) $key_to_id[ $item['key'] ] = (int) $item_id;
        }
        $locations[ $config['location'] ] = $id;
        if ( 'header' === $type ) {
            $settings = apply_filters( 'wp_theme_demo_menu_settings', array(
                'search_bar'=>true,
                'customer_account'=>wp_theme_demo_commerce_enabled($profile),
                'mini_cart'=>wp_theme_demo_commerce_enabled($profile),
                'wishlist'=>false,'mega_menu'=>true,'last_button'=>!wp_theme_demo_commerce_enabled($profile),'light_dark'=>true,'language_bar'=>true,'sticky_header'=>true,
            ), $profile );
            if ( function_exists( 'wp_theme_update_nav_menu_setting' ) ) foreach ( $settings as $setting=>$value ) wp_theme_update_nav_menu_setting( $id, $setting, $value );
            if ( ! empty( $settings['mega_menu'] ) ) {
                foreach ( wp_theme_seed_demo_mega_menus( $profile ) as $mega ) {
                    $target = $mega['target_key'] ?? '';
                    if ( $target && ! empty( $key_to_id[ $target ] ) ) {
                        update_post_meta( $key_to_id[ $target ], '_wp_theme_mega_post_id', (int) $mega['id'] );
                        update_post_meta( $key_to_id[ $target ], 'mega_post_id', (int) $mega['id'] );
                        if ( function_exists( 'update_field' ) ) update_field( 'mega_post_id', (int) $mega['id'], $key_to_id[ $target ] );
                    }
                }
            }
        }
    }
    set_theme_mod( 'nav_menu_locations', $locations );
    update_option( 'wp_theme_demo_menu_profile', $profile_id );
}

function wp_theme_demo_import_message( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    return apply_filters( 'wp_theme_demo_import_message', sprintf( __( '%s starter refreshed: Gutenberg content, translated Polylang copies, language-specific Header/Top/Footer menus, translated taxonomy links, the language bar and editable mega-menu layouts are assigned.','wp-theme' ), $profile['name'] ), $profile );
}

function wp_theme_apply_demo_palette( $profile = null ) {
    $profile = is_array( $profile ) ? $profile : wp_theme_get_demo_profile();
    if ( empty( $profile['palette'] ) || ! is_array( $profile['palette'] ) ) return;
    foreach ( $profile['palette'] as $key=>$value ) {
        if ( function_exists( 'update_field' ) ) update_field( $key, $value, 'option' );
        update_option( 'wp_theme_demo_' . sanitize_key( $key ), $value, false );
    }
}

/**
 * Repair legacy starter content that was saved with escaped Unicode punctuation
 * rendered as literal u201c/u201d text by older BBuilder serialisation.
 */
function wp_theme_normalize_legacy_unicode_punctuation( $content ) {
    if ( is_admin() || ! is_string( $content ) || false === strpos( $content, 'u20' ) ) return $content;
    return strtr( $content, array(
        '\\u2018' => '‘', '\\u2019' => '’', '\\u201c' => '“', '\\u201d' => '”', '\\u2013' => '–', '\\u2014' => '—', '\\u2026' => '…',
        'u2018' => '‘', 'u2019' => '’', 'u201c' => '“', 'u201d' => '”', 'u2013' => '–', 'u2014' => '—', 'u2026' => '…',
    ) );
}
add_filter( 'the_content', 'wp_theme_normalize_legacy_unicode_punctuation', 120 );
