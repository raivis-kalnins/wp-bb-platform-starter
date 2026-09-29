<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP BBuilder Sector Finder
 *
 * A small dynamic block owned by the parent so child themes can expose rich
 * search / finder experiences without falling back to shortcode blocks.
 * Child themes render their UI through the wp_theme_sector_finder_render filter.
 */
function wp_theme_register_sector_finder_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    register_block_type( 'wpbb/sector-finder', array(
        'api_version'     => 3,
        'title'           => __( 'Sector Finder', 'wp-theme' ),
        'category'        => 'widgets',
        'icon'            => 'filter',
        'description'     => __( 'Search and filter block supplied by the active WP BBTheme child theme.', 'wp-theme' ),
        'attributes'      => array(
            'context' => array( 'type' => 'string', 'default' => '' ),
            'title'   => array( 'type' => 'string', 'default' => '' ),
            'limit'   => array( 'type' => 'number', 'default' => 8 ),
            'className' => array( 'type' => 'string', 'default' => '' ),
        ),
        'render_callback' => 'wp_theme_render_sector_finder_block',
    ) );
}
add_action( 'init', 'wp_theme_register_sector_finder_block', 30 );

function wp_theme_render_sector_finder_block( $attributes = array(), $content = '', $block = null ) {
    $attributes = wp_parse_args( is_array( $attributes ) ? $attributes : array(), array(
        'context' => '',
        'title'   => '',
        'limit'   => 8,
        'className' => '',
    ) );
    $context = sanitize_key( $attributes['context'] );
    $attributes['limit'] = max( 1, min( 48, absint( $attributes['limit'] ) ) );

    $rendered = apply_filters( 'wp_theme_sector_finder_render', '', $context, $attributes, $block );
    if ( '' !== trim( (string) $rendered ) ) {
        return $rendered;
    }

    if ( current_user_can( 'edit_posts' ) ) {
        return '<div class="wp-theme-sector-finder-empty"><strong>' . esc_html__( 'Sector Finder', 'wp-theme' ) . '</strong><p>' . esc_html__( 'Choose a context supported by the active child theme.', 'wp-theme' ) . '</p></div>';
    }
    return '';
}

function wp_theme_sector_finder_editor_assets() {
    if ( ! function_exists( 'wp_add_inline_script' ) ) {
        return;
    }
    wp_enqueue_script( 'wp-server-side-render' );
    wp_enqueue_script( 'wp-block-editor' );
    wp_enqueue_script( 'wp-components' );
    $handle = 'wp-server-side-render';
    $script = <<<'JS'
(function(wp){
    if(!wp || !wp.blocks || !wp.element || !wp.components || !wp.blockEditor || !wp.serverSideRender){return;}
    var el=wp.element.createElement;
    var InspectorControls=wp.blockEditor.InspectorControls;
    var PanelBody=wp.components.PanelBody;
    var TextControl=wp.components.TextControl;
    var RangeControl=wp.components.RangeControl;
    var ServerSideRender=wp.serverSideRender;
    if(wp.blocks.getBlockType('wpbb/sector-finder')){return;}
    wp.blocks.registerBlockType('wpbb/sector-finder',{
        apiVersion:3,
        title:'Sector Finder',
        icon:'filter',
        category:'widgets',
        attributes:{context:{type:'string',default:''},title:{type:'string',default:''},limit:{type:'number',default:8}},
        edit:function(props){
            return el(wp.element.Fragment,{},
                el(InspectorControls,{},el(PanelBody,{title:'Finder settings',initialOpen:true},
                    el(TextControl,{label:'Context',help:'Examples: travel, hotel, cars, insurance, logistics, restaurant, building, courses',value:props.attributes.context||'',onChange:function(v){props.setAttributes({context:v});}}),
                    el(TextControl,{label:'Title override',value:props.attributes.title||'',onChange:function(v){props.setAttributes({title:v});}}),
                    el(RangeControl,{label:'Results',min:1,max:24,value:props.attributes.limit||8,onChange:function(v){props.setAttributes({limit:v});}})
                )),
                el(ServerSideRender,{block:'wpbb/sector-finder',attributes:props.attributes})
            );
        },
        save:function(){return null;}
    });
})(window.wp);
JS;
    wp_add_inline_script( $handle, $script, 'after' );
}
add_action( 'enqueue_block_editor_assets', 'wp_theme_sector_finder_editor_assets', 30 );
