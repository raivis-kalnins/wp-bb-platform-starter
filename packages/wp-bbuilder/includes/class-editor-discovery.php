<?php
/**
 * Block discovery guide for the WordPress editor.
 *
 * This class only enriches editor metadata and adds an optional guide sidebar.
 * It never changes block names, attributes, serialization or front-end output.
 */
if (!defined('ABSPATH')) exit;

final class WPBB_Editor_Discovery {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_assets'], 5);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_assets'], 5);
    }

    public function register_assets() {
        wp_register_script(
            'wpbb-editor-discovery',
            WPBB_PLUGIN_URL . 'assets/editor-discovery.js',
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-plugins', 'wp-edit-post'],
            WPBB_VERSION,
            true
        );
        wp_register_style(
            'wpbb-editor-discovery',
            WPBB_PLUGIN_URL . 'assets/editor-discovery.css',
            ['wp-components'],
            WPBB_VERSION
        );
    }

    public function enqueue_assets() {
        if (!wp_script_is('wpbb-editor-discovery', 'registered')) $this->register_assets();

        $catalog = [];
        foreach ($this->catalog() as $slug => $item) {
            if (!function_exists('wpbb_is_block_enabled') || wpbb_is_block_enabled($slug)) {
                $item['name'] = 'wpbb/' . $slug;
                $item['slug'] = $slug;
                $catalog[] = $item;
            }
        }

        wp_add_inline_script(
            'wpbb-editor-discovery',
            'window.wpbbEditorDiscovery=' . wp_json_encode([
                'title'       => __('BBuilder Guide', 'wp-bbuilder'),
                'intro'       => __('Find a block by purpose, review what it does, and insert it without changing existing content.', 'wp-bbuilder'),
                'searchLabel' => __('Search BBuilder blocks', 'wp-bbuilder'),
                'insertLabel' => __('Insert block', 'wp-bbuilder'),
                'emptyLabel'  => __('No enabled BBuilder blocks match this search.', 'wp-bbuilder'),
                'items'       => array_values($catalog),
            ]),
            'before'
        );
        wp_enqueue_style('wpbb-editor-discovery');
        wp_enqueue_script('wpbb-editor-discovery');
    }

    private function item($title, $description, $group, array $keywords, $best_for = '') {
        return [
            'title'       => $title,
            'description' => $description,
            'group'       => $group,
            'keywords'    => array_values($keywords),
            'bestFor'     => $best_for,
        ];
    }

    private function catalog() {
        $layout = __('Layout', 'wp-bbuilder');
        $content = __('Content', 'wp-bbuilder');
        $media = __('Media', 'wp-bbuilder');
        $forms = __('Forms & conversion', 'wp-bbuilder');
        $dynamic = __('Dynamic content', 'wp-bbuilder');
        $navigation = __('Navigation', 'wp-bbuilder');
        $data = __('Data & utilities', 'wp-bbuilder');

        return [
            'row' => $this->item(__('Row', 'wp-bbuilder'), __('Responsive Bootstrap row with gutters, alignment, spacing, background and custom-class controls.', 'wp-bbuilder'), $layout, ['grid','columns','bootstrap'], __('Page sections and multi-column layouts', 'wp-bbuilder')),
            'column' => $this->item(__('Column', 'wp-bbuilder'), __('Responsive 12-column child container with per-breakpoint width, order, alignment and spacing.', 'wp-bbuilder'), $layout, ['grid','responsive','container'], __('Content inside a Row', 'wp-bbuilder')),
            'section' => $this->item(__('Section', 'wp-bbuilder'), __('Semantic full-width section wrapper with container, spacing and visual treatment controls.', 'wp-bbuilder'), $layout, ['wrapper','container','spacing'], __('Consistent vertical page structure', 'wp-bbuilder')),
            'bootstrap-div' => $this->item(__('Bootstrap Div', 'wp-bbuilder'), __('Flexible div wrapper for Bootstrap utility classes and nested blocks.', 'wp-bbuilder'), $layout, ['wrapper','utilities','classes'], __('Advanced utility-class layouts', 'wp-bbuilder')),
            'cards' => $this->item(__('Cards', 'wp-bbuilder'), __('Container for a consistent collection of CTA cards.', 'wp-bbuilder'), $layout, ['grid','cards','collection'], __('Service, feature and offer grids', 'wp-bbuilder')),
            'tabs' => $this->item(__('Tabs', 'wp-bbuilder'), __('Accessible tab container for grouped Tab Item blocks.', 'wp-bbuilder'), $layout, ['tabs','organize','interactive'], __('Compact grouped information', 'wp-bbuilder')),
            'tab-item' => $this->item(__('Tab Item', 'wp-bbuilder'), __('One labeled content panel used inside a Tabs block.', 'wp-bbuilder'), $layout, ['tab','panel','content'], __('A child of Tabs', 'wp-bbuilder')),
            'accordion' => $this->item(__('Accordion', 'wp-bbuilder'), __('Accessible collapsible container for Accordion Item blocks.', 'wp-bbuilder'), $layout, ['faq','collapse','toggle'], __('FAQs and long supporting content', 'wp-bbuilder')),
            'accordion-item' => $this->item(__('Accordion Item', 'wp-bbuilder'), __('One collapsible heading-and-content panel used inside an Accordion.', 'wp-bbuilder'), $layout, ['faq','panel','collapse'], __('A child of Accordion', 'wp-bbuilder')),

            'button' => $this->item(__('Button', 'wp-bbuilder'), __('Bootstrap-style action link with size, style, icon, target and rel controls.', 'wp-bbuilder'), $content, ['cta','link','action'], __('Primary and secondary actions', 'wp-bbuilder')),
            'card' => $this->item(__('Card', 'wp-bbuilder'), __('Single image, heading, text and action card with Bootstrap-compatible styling.', 'wp-bbuilder'), $content, ['content','image','cta'], __('Services, products and editorial previews', 'wp-bbuilder')),
            'cta-card' => $this->item(__('CTA Card', 'wp-bbuilder'), __('Focused conversion card with heading, supporting copy and action.', 'wp-bbuilder'), $forms, ['cta','conversion','offer'], __('Prominent next-step prompts', 'wp-bbuilder')),
            'cta-section' => $this->item(__('CTA Section', 'wp-bbuilder'), __('Full-width call-to-action section with controlled content hierarchy.', 'wp-bbuilder'), $forms, ['cta','banner','conversion'], __('End-of-page conversion sections', 'wp-bbuilder')),
            'feature-list' => $this->item(__('Feature List', 'wp-bbuilder'), __('Structured list of benefits or capabilities with optional icons.', 'wp-bbuilder'), $content, ['features','benefits','icons'], __('Benefits and comparison points', 'wp-bbuilder')),
            'timeline' => $this->item(__('Timeline', 'wp-bbuilder'), __('Chronological milestones with dates, headings and descriptions.', 'wp-bbuilder'), $content, ['history','steps','milestones'], __('Processes, roadmaps and company history', 'wp-bbuilder')),
            'testimonials' => $this->item(__('Testimonials', 'wp-bbuilder'), __('Customer quotation cards with names, roles and presentation controls.', 'wp-bbuilder'), $content, ['reviews','quotes','social proof'], __('Trust and social-proof sections', 'wp-bbuilder')),
            'fun-fact' => $this->item(__('Fun Fact', 'wp-bbuilder'), __('Compact statistic with number, label and optional icon.', 'wp-bbuilder'), $content, ['statistic','number','metric'], __('KPI and proof-point rows', 'wp-bbuilder')),
            'alert' => $this->item(__('Alert', 'wp-bbuilder'), __('Contextual Bootstrap notice for information, success, warning or error messages.', 'wp-bbuilder'), $content, ['notice','message','status'], __('Important inline messages', 'wp-bbuilder')),
            'badge' => $this->item(__('Badge', 'wp-bbuilder'), __('Small label for statuses, categories or short emphasis.', 'wp-bbuilder'), $content, ['label','status','tag'], __('Compact metadata labels', 'wp-bbuilder')),
            'list-group' => $this->item(__('List Group', 'wp-bbuilder'), __('Styled list of linked or static items using Bootstrap list-group structure.', 'wp-bbuilder'), $content, ['list','links','items'], __('Menus, resources and compact lists', 'wp-bbuilder')),
            'progress' => $this->item(__('Progress', 'wp-bbuilder'), __('Labeled progress indicator with percentage and Bootstrap variants.', 'wp-bbuilder'), $content, ['percentage','status','bar'], __('Skills, completion and targets', 'wp-bbuilder')),
            'pricecards' => $this->item(__('Pricing Cards', 'wp-bbuilder'), __('Editable pricing-plan cards with currency, featured plan and action text.', 'wp-bbuilder'), $forms, ['pricing','plans','packages'], __('Service plans and product tiers', 'wp-bbuilder')),

            'swiper' => $this->item(__('Swiper', 'wp-bbuilder'), __('Responsive slider for editorial, image or video slides with navigation and autoplay controls.', 'wp-bbuilder'), $media, ['slider','carousel','gallery'], __('Hero sliders and visual collections', 'wp-bbuilder')),
            'video' => $this->item(__('Video', 'wp-bbuilder'), __('Responsive video or embed presentation with accessible settings.', 'wp-bbuilder'), $media, ['media','embed','youtube'], __('Lessons, demos and featured media', 'wp-bbuilder')),
            'file' => $this->item(__('File', 'wp-bbuilder'), __('Download link for a selected Media Library file with readable label.', 'wp-bbuilder'), $media, ['download','pdf','document'], __('Guides, PDFs and resources', 'wp-bbuilder')),
            'inline-svg' => $this->item(__('Inline SVG', 'wp-bbuilder'), __('Controlled inline SVG output for lightweight icons and illustrations.', 'wp-bbuilder'), $media, ['icon','vector','svg'], __('Scalable decorative graphics', 'wp-bbuilder')),
            'google-map' => $this->item(__('Google Map', 'wp-bbuilder'), __('Location map with address and display controls.', 'wp-bbuilder'), $media, ['map','location','address'], __('Contact and venue locations', 'wp-bbuilder')),
            'custom-embed' => $this->item(__('Custom Embed', 'wp-bbuilder'), __('Embed an approved external resource or custom markup where standard blocks are insufficient.', 'wp-bbuilder'), $media, ['iframe','embed','external'], __('Special third-party embeds', 'wp-bbuilder')),

            'dynamic-form' => $this->item(__('Dynamic Form', 'wp-bbuilder'), __('Configurable contact or enquiry form with validation, recipients, spam protection and optional saved entries.', 'wp-bbuilder'), $forms, ['form','contact','lead'], __('Contact, quote and enquiry workflows', 'wp-bbuilder')),
            'booking-calendar' => $this->item(__('Booking Calendar', 'wp-bbuilder'), __('Availability-oriented booking form with date range, guests and booking entry support.', 'wp-bbuilder'), $forms, ['booking','calendar','availability'], __('Appointments, stays and reservations', 'wp-bbuilder')),
            'mailchimp' => $this->item(__('Mailchimp', 'wp-bbuilder'), __('Newsletter signup form prepared for a Mailchimp audience connection.', 'wp-bbuilder'), $forms, ['newsletter','email','subscribe'], __('Email-list growth', 'wp-bbuilder')),
            'login-register' => $this->item(__('Login / Register', 'wp-bbuilder'), __('Front-end account access form using WordPress authentication.', 'wp-bbuilder'), $forms, ['account','login','register'], __('Member and learner access', 'wp-bbuilder')),
            'contact-links' => $this->item(__('Contact Links', 'wp-bbuilder'), __('Compact telephone, email, address and messaging links.', 'wp-bbuilder'), $forms, ['contact','phone','email'], __('Fast contact actions', 'wp-bbuilder')),

            'ajax-search' => $this->item(__('Ajax Search', 'wp-bbuilder'), __('Live search across configured post types with optional excerpts, prices and result links.', 'wp-bbuilder'), $dynamic, ['search','filter','results'], __('Directories, shops and large sites', 'wp-bbuilder')),
            'catalogue' => $this->item(__('Catalogue', 'wp-bbuilder'), __('Server-rendered content catalogue filtered by post type, taxonomy, order and item count.', 'wp-bbuilder'), $dynamic, ['posts','grid','directory'], __('Reusable post and portfolio listings', 'wp-bbuilder')),
            'blog-filter' => $this->item(__('Blog Filter', 'wp-bbuilder'), __('Filterable editorial card grid with category and paging support.', 'wp-bbuilder'), $dynamic, ['blog','category','articles'], __('Insights and news archives', 'wp-bbuilder')),
            'load-more' => $this->item(__('Load More', 'wp-bbuilder'), __('AJAX paging control for progressively loading post collections.', 'wp-bbuilder'), $dynamic, ['pagination','ajax','posts'], __('Long content archives', 'wp-bbuilder')),
            'events' => $this->item(__('Events', 'wp-bbuilder'), __('Server-rendered event list for upcoming dates, locations and event content.', 'wp-bbuilder'), $dynamic, ['calendar','schedule','tickets'], __('Event landing pages and schedules', 'wp-bbuilder')),
            'social-feeds' => $this->item(__('Social Feeds', 'wp-bbuilder'), __('Container for configured social-feed sources.', 'wp-bbuilder'), $dynamic, ['social','feed','network'], __('Multi-network social sections', 'wp-bbuilder')),
            'soc-feed' => $this->item(__('Social Feed', 'wp-bbuilder'), __('One configured social network feed source.', 'wp-bbuilder'), $dynamic, ['social','feed','posts'], __('A child of Social Feeds', 'wp-bbuilder')),
            'weather' => $this->item(__('Weather', 'wp-bbuilder'), __('Live or fallback weather card for a configured location.', 'wp-bbuilder'), $dynamic, ['forecast','temperature','location'], __('Travel, venue and destination pages', 'wp-bbuilder')),
            'varda-dienas' => $this->item(__('Name Days', 'wp-bbuilder'), __('Date-aware Latvian name-day display with local fallback data.', 'wp-bbuilder'), $dynamic, ['latvia','calendar','names'], __('Localized Latvian content', 'wp-bbuilder')),
            'ai-content' => $this->item(__('AI Content', 'wp-bbuilder'), __('Editorial drafting helper block; review all generated copy before publishing.', 'wp-bbuilder'), $dynamic, ['draft','writing','assistant'], __('Early content drafting', 'wp-bbuilder')),

            'navbar' => $this->item(__('Navbar', 'wp-bbuilder'), __('Bootstrap-compatible navigation bar for structured links and responsive presentation.', 'wp-bbuilder'), $navigation, ['menu','header','navigation'], __('Custom in-content navigation', 'wp-bbuilder')),
            'menu-option' => $this->item(__('Menu Option', 'wp-bbuilder'), __('Single navigational item used by compatible BBuilder menu layouts.', 'wp-bbuilder'), $navigation, ['menu','link','item'], __('A child navigation item', 'wp-bbuilder')),
            'breadcrumb' => $this->item(__('Breadcrumb', 'wp-bbuilder'), __('Hierarchical navigation trail for page context.', 'wp-bbuilder'), $navigation, ['trail','hierarchy','navigation'], __('Interior pages and archives', 'wp-bbuilder')),
            'sitemap' => $this->item(__('Sitemap', 'wp-bbuilder'), __('Structured list of public site content for human visitors.', 'wp-bbuilder'), $navigation, ['pages','links','index'], __('HTML sitemap pages', 'wp-bbuilder')),
            'soc-follow-block' => $this->item(__('Social Follow', 'wp-bbuilder'), __('Links to the organisation’s social profiles with accessible labels.', 'wp-bbuilder'), $navigation, ['social','profiles','links'], __('Header, footer and contact areas', 'wp-bbuilder')),
            'soc-share' => $this->item(__('Social Share', 'wp-bbuilder'), __('Share links for the current public page or post.', 'wp-bbuilder'), $navigation, ['share','social','article'], __('Articles and campaign pages', 'wp-bbuilder')),

            'table' => $this->item(__('Table', 'wp-bbuilder'), __('Responsive Bootstrap table with optional search, sorting and paging.', 'wp-bbuilder'), $data, ['data','rows','comparison'], __('Structured comparisons and records', 'wp-bbuilder')),
            'chart' => $this->item(__('Chart', 'wp-bbuilder'), __('Visual chart configured from structured labels and values.', 'wp-bbuilder'), $data, ['graph','visualization','statistics'], __('Simple data stories', 'wp-bbuilder')),
            'countdown-timer' => $this->item(__('Countdown Timer', 'wp-bbuilder'), __('Live countdown to a configured date and time.', 'wp-bbuilder'), $data, ['deadline','event','timer'], __('Launches, offers and events', 'wp-bbuilder')),
            'code-display' => $this->item(__('Code Display', 'wp-bbuilder'), __('Readable code sample with language labeling.', 'wp-bbuilder'), $data, ['code','snippet','developer'], __('Documentation and tutorials', 'wp-bbuilder')),
            'spinner' => $this->item(__('Spinner', 'wp-bbuilder'), __('Bootstrap loading indicator for interface previews and custom workflows.', 'wp-bbuilder'), $data, ['loading','status','indicator'], __('Async-state placeholders', 'wp-bbuilder')),
        ];
    }
}
