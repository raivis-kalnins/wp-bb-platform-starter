<?php
/** Shared form demo page and ready-to-insert BBuilder form patterns. */
defined( 'ABSPATH' ) || exit;

function wp_theme_form_library_definitions() {
    return array(
        'contact' => array(
            'title' => __( 'Contact / general enquiry', 'wp-theme' ),
            'intro' => __( 'A clean two-column contact form for most service websites.', 'wp-theme' ),
            'submit' => __( 'Send enquiry', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Optional','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'topic','label'=>__('Enquiry type','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Choose one','wp-theme'),'options'=>"General enquiry\nNew project\nSupport\nPartnership\nOther",'step'=>1),
                array('type'=>'textarea','name'=>'message','label'=>__('Message','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('How can we help?','wp-theme'),'step'=>1),
            ),
        ),
        'quote' => array(
            'title' => __( 'Request a quote', 'wp-theme' ),
            'intro' => __( 'Useful for trade, logistics, automotive, insurance and project enquiries.', 'wp-theme' ),
            'submit' => __( 'Request quote', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
                array('type'=>'text','name'=>'company','label'=>__('Company','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Company / organisation','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Optional','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'budget','label'=>__('Budget range','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Select range','wp-theme'),'options'=>"Under £1,000\n£1,000–£5,000\n£5,000–£20,000\n£20,000+\nNot sure yet",'step'=>1),
                array('type'=>'date','name'=>'target_date','label'=>__('Preferred date','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>'','step'=>1),
                array('type'=>'textarea','name'=>'requirements','label'=>__('Requirements','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Tell us what you need, quantities, location or important details.','wp-theme'),'step'=>1),
            ),
        ),
        'booking' => array(
            'title' => __( 'Booking / reservation request', 'wp-theme' ),
            'intro' => __( 'A compact reservation form for hotels, restaurants, clinics and appointments.', 'wp-theme' ),
            'submit' => __( 'Request booking', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'date','name'=>'date','label'=>__('Date','wp-theme'),'required'=>true,'width'=>4,'breakpoint'=>'md','placeholder'=>'','step'=>1),
                array('type'=>'time','name'=>'time','label'=>__('Time','wp-theme'),'required'=>false,'width'=>4,'breakpoint'=>'md','placeholder'=>'','step'=>1),
                array('type'=>'number','name'=>'people','label'=>__('People','wp-theme'),'required'=>true,'width'=>4,'breakpoint'=>'md','placeholder'=>__('2','wp-theme'),'step'=>1),
                array('type'=>'textarea','name'=>'notes','label'=>__('Notes','wp-theme'),'required'=>false,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Accessibility, dietary or other requirements','wp-theme'),'step'=>1),
            ),
        ),
        'callback' => array(
            'title' => __( 'Quick callback', 'wp-theme' ),
            'intro' => __( 'Short conversion form for hero, CTA and sidebar placements.', 'wp-theme' ),
            'submit' => __( 'Request callback', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>4,'breakpoint'=>'md','placeholder'=>__('Name','wp-theme'),'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>true,'width'=>4,'breakpoint'=>'md','placeholder'=>__('Phone number','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'when','label'=>__('Best time','wp-theme'),'required'=>false,'width'=>4,'breakpoint'=>'md','placeholder'=>__('Any time','wp-theme'),'options'=>"Morning\nAfternoon\nEvening\nAny time",'step'=>1),
            ),
        ),
        'application' => array(
            'title' => __( 'Application / detailed enquiry', 'wp-theme' ),
            'intro' => __( 'A longer form for courses, recruitment, onboarding and structured requests.', 'wp-theme' ),
            'submit' => __( 'Submit application', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Full name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Full name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'interest','label'=>__('Area of interest','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Select one','wp-theme'),'options'=>"Course / training\nCareer / role\nPartnership\nSupplier onboarding\nOther",'step'=>1),
                array('type'=>'text','name'=>'organisation','label'=>__('Organisation','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Organisation','wp-theme'),'step'=>1),
                array('type'=>'textarea','name'=>'background','label'=>__('Background and goals','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Tell us about your experience and what you want to achieve.','wp-theme'),'step'=>1),
                array('type'=>'file','name'=>'attachment','label'=>__('Supporting file','wp-theme'),'required'=>false,'width'=>12,'breakpoint'=>'md','placeholder'=>'','accept'=>'.pdf,.doc,.docx,.jpg,.png','step'=>1),
            ),
        ),
        'service' => array(
            'title' => __( 'Service / maintenance request', 'wp-theme' ),
            'intro' => __( 'Practical request form for building trades, automotive, property, logistics and aftercare teams.', 'wp-theme' ),
            'submit' => __( 'Send service request', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Phone number','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'service','label'=>__('Service required','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Choose service','wp-theme'),'options'=>"Inspection / assessment
Repair / maintenance
Installation
Emergency help
Other",'step'=>1),
                array('type'=>'text','name'=>'location','label'=>__('Location / postcode','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Where is the work needed?','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'urgency','label'=>__('Urgency','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Choose priority','wp-theme'),'options'=>"As soon as possible
Within a week
Within a month
Planning ahead",'step'=>1),
                array('type'=>'textarea','name'=>'details','label'=>__('What needs attention?','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Describe the issue, asset, access requirements or useful measurements.','wp-theme'),'step'=>1),
            ),
        ),
        'registration' => array(
            'title' => __( 'Event / course registration', 'wp-theme' ),
            'intro' => __( 'A reusable registration form for courses, events, webinars, viewings and organised sessions.', 'wp-theme' ),
            'submit' => __( 'Register interest', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Full name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Full name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Optional','wp-theme'),'step'=>1),
                array('type'=>'text','name'=>'session','label'=>__('Course / event / session','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('What would you like to attend?','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'attendance','label'=>__('Attendance preference','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Select preference','wp-theme'),'options'=>"In person
Online
Either",'step'=>1),
                array('type'=>'number','name'=>'places','label'=>__('Places','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('1','wp-theme'),'step'=>1),
                array('type'=>'textarea','name'=>'notes','label'=>__('Notes','wp-theme'),'required'=>false,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Accessibility, learning, scheduling or other requirements.','wp-theme'),'step'=>1),
            ),
        ),
        'product' => array(
            'title' => __( 'Product / stock enquiry', 'wp-theme' ),
            'intro' => __( 'Useful beside catalogues when a customer needs stock, specification, delivery or compatibility advice before purchasing.', 'wp-theme' ),
            'submit' => __( 'Send product enquiry', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Your name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('you@example.com','wp-theme'),'step'=>1),
                array('type'=>'text','name'=>'product','label'=>__('Product / reference','wp-theme'),'required'=>true,'width'=>8,'breakpoint'=>'md','placeholder'=>__('Product name, SKU, vehicle or package','wp-theme'),'step'=>1),
                array('type'=>'number','name'=>'quantity','label'=>__('Quantity','wp-theme'),'required'=>false,'width'=>4,'breakpoint'=>'md','placeholder'=>__('1','wp-theme'),'step'=>1),
                array('type'=>'select','name'=>'question','label'=>__('What do you need to know?','wp-theme'),'required'=>true,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Choose one','wp-theme'),'options'=>"Stock / availability
Compatibility / specification
Delivery / lead time
Bulk pricing
Custom requirement
Other",'step'=>1),
                array('type'=>'phone','name'=>'phone','label'=>__('Phone','wp-theme'),'required'=>false,'width'=>6,'breakpoint'=>'md','placeholder'=>__('Optional','wp-theme'),'step'=>1),
                array('type'=>'textarea','name'=>'message','label'=>__('Details','wp-theme'),'required'=>true,'width'=>12,'breakpoint'=>'md','placeholder'=>__('Add dimensions, fitment details, delivery location or any other useful information.','wp-theme'),'step'=>1),
            ),
        ),
        'newsletter' => array(
            'title' => __( 'Newsletter signup', 'wp-theme' ),
            'intro' => __( 'Simple inline signup block for content and footer areas.', 'wp-theme' ),
            'submit' => __( 'Subscribe', 'wp-theme' ),
            'fields' => array(
                array('type'=>'text','name'=>'name','label'=>__('Name','wp-theme'),'required'=>false,'width'=>4,'breakpoint'=>'md','placeholder'=>__('Name','wp-theme'),'step'=>1),
                array('type'=>'email','name'=>'email','label'=>__('Email','wp-theme'),'required'=>true,'width'=>8,'breakpoint'=>'md','placeholder'=>__('Email address','wp-theme'),'step'=>1),
            ),
        ),
    );
}

function wp_theme_form_library_block_markup( $key, $extra_class = '' ) {
    $defs = wp_theme_form_library_definitions();
    if ( empty( $defs[ $key ] ) ) return '';
    $def = $defs[ $key ];
    $attrs = array(
        'showTitle' => true,
        'formTitle' => $def['title'],
        'emailSubject' => sprintf( __( '%s — %s submission', 'wp-theme' ), get_bloginfo( 'name' ) ?: 'Website', $def['title'] ),
        'successMessage' => __( 'Thanks. Your details have been received.', 'wp-theme' ),
        'submitText' => $def['submit'],
        'stylePreset' => 'soft',
        'labelPosition' => 'top',
        'gap' => 3,
        'fields' => $def['fields'],
        'className' => trim( 'wp-theme-form-pattern wp-theme-form-pattern--' . sanitize_html_class( $key ) . ' ' . $extra_class ),
    );
    return '<!-- wp:wpbb/dynamic-form ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
}

function wp_theme_form_library_pattern_content( $key ) {
    $defs = wp_theme_form_library_definitions();
    if ( empty( $defs[ $key ] ) ) return '';
    $def = $defs[ $key ];
    return '<!-- wp:group {"className":"wp-theme-section-shell wp-theme-form-library-section","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-section-shell wp-theme-form-library-section"><!-- wp:wpbb/row {"containerClass":"container","gutterX":"gx-5","gutterY":"gy-4","customClasses":"align-items-start"} --><!-- wp:wpbb/column {"xs":12,"lg":4} --><p class="wp-theme-sector-eyebrow">' . esc_html__( 'Ready form pattern', 'wp-theme' ) . '</p><h2>' . esc_html( $def['title'] ) . '</h2><p class="wp-theme-sector-lead">' . esc_html( $def['intro'] ) . '</p><!-- /wp:wpbb/column --><!-- wp:wpbb/column {"xs":12,"lg":8} -->' . wp_theme_form_library_block_markup( $key ) . '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
}

function wp_theme_forms_demo_page_content() {
    $content = '<!-- wp:group {"className":"wp-theme-inner-hero wp-theme-forms-hero","layout":{"type":"default"}} --><div class="wp-block-group wp-theme-inner-hero wp-theme-forms-hero"><!-- wp:wpbb/row {"containerClass":"container"} --><!-- wp:wpbb/column {"xs":12} --><p class="wp-theme-sector-eyebrow">' . esc_html__( 'Form library', 'wp-theme' ) . '</p><h1>' . esc_html__( 'Ready forms for common website journeys', 'wp-theme' ) . '</h1><p class="wp-theme-sector-lead">' . esc_html__( 'Use these as working starting points, then edit fields, recipients and copy in BBuilder. Styling, spacing, focus states and privacy links are already integrated.', 'wp-theme' ) . '</p><!-- /wp:wpbb/column --><!-- /wp:wpbb/row --></div><!-- /wp:group -->';
    foreach ( array( 'contact','quote','booking','callback','service','product','registration','application','newsletter' ) as $key ) $content .= wp_theme_form_library_pattern_content( $key );
    return $content;
}

function wp_theme_seed_forms_demo_page() {
    if ( ! current_user_can( 'edit_pages' ) ) return;
    $page = get_page_by_path( 'forms' );
    $args = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => __( 'Forms', 'wp-theme' ),
        'post_name' => 'forms',
        'post_content' => wp_theme_forms_demo_page_content(),
    );
    if ( $page instanceof WP_Post ) {
        if ( get_post_meta( $page->ID, '_wp_theme_forms_generated', true ) ) {
            $args['ID'] = $page->ID;
            wp_update_post( $args );
        }
        return;
    }
    $id = wp_insert_post( $args );
    if ( $id && ! is_wp_error( $id ) ) update_post_meta( $id, '_wp_theme_forms_generated', 1 );
}
add_action( 'after_switch_theme', 'wp_theme_seed_forms_demo_page', 28 );

function wp_theme_maybe_seed_forms_demo_page() {
    if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'edit_pages' ) ) return;
    $version = '3.8.1';
    if ( get_option( 'wp_theme_forms_library_version' ) === $version ) return;
    wp_theme_seed_forms_demo_page();
    update_option( 'wp_theme_forms_library_version', $version, false );
}
add_action( 'admin_init', 'wp_theme_maybe_seed_forms_demo_page', 24 );

function wp_theme_forms_library_assets() {
    // Presentation is owned by the active child theme.
}
add_action( 'wp_enqueue_scripts', 'wp_theme_forms_library_assets', 57 );
