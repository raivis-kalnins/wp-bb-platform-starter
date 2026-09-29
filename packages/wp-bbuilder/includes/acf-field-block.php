<?php
/**
 * Generic, read-only ACF field block support.
 *
 * @package wp_bbuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the dynamic ACF Field block is visible in the block inserter.
 *
 * @return bool
 */
function wpbb_acf_field_block_enabled() {
    return (bool) wpbb_get_option( 'acf_field_block_enabled', 1 );
}

/**
 * Whether administrators may expose fields from ACF Options pages.
 *
 * @return bool
 */
function wpbb_acf_options_source_enabled() {
    return (bool) wpbb_get_option( 'acf_field_allow_options', 1 );
}

/**
 * Whether the ACF APIs needed by the field block are available.
 *
 * The function checks capabilities rather than a plugin constant so it also
 * works with compatible ACF distributions that expose the same public APIs.
 *
 * @return bool
 */
function wpbb_acf_field_support_available() {
    if ( defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, '6.1', '<' ) ) {
        return false;
    }

    return function_exists( 'acf_get_field_groups' )
        && function_exists( 'acf_get_fields' )
        && function_exists( 'get_field_object' );
}

/**
 * Register the field picker endpoint used by the block editor.
 *
 * Values are not returned by this endpoint. Saved values are rendered through
 * WordPress' authenticated block-renderer endpoint and checked again there.
 *
 * @return void
 */
function wpbb_register_acf_field_rest_route() {
    // Keep the endpoint available for blocks already saved in content. The
    // dashboard setting only hides the block from the inserter.
    register_rest_route(
        'wp-bbuilder/v1',
        '/acf-fields',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'wpbb_rest_get_acf_fields',
            'permission_callback' => 'wpbb_rest_can_browse_acf_fields',
            'args'                => array(
                'source'    => array(
                    'default'           => 'current_post',
                    'sanitize_callback' => 'sanitize_key',
                ),
                'post_id'   => array(
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ),
                'post_type' => array(
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_key',
                ),
                'taxonomy'  => array(
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_key',
                ),
                'term_id'   => array(
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ),
                'user_id'   => array(
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'wpbb_register_acf_field_rest_route' );

/**
 * Require an authenticated editor before exposing field definitions.
 *
 * @return bool|WP_Error
 */
function wpbb_rest_can_browse_acf_fields() {
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'wpbb_acf_fields_login_required',
            __( 'You must be signed in to browse ACF fields.', 'wp-bbuilder' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    if (
        current_user_can( 'edit_posts' )
        || current_user_can( 'edit_pages' )
        || current_user_can( 'edit_theme_options' )
        || current_user_can( 'edit_users' )
    ) {
        return true;
    }

    return new WP_Error(
        'wpbb_acf_fields_forbidden',
        __( 'You are not allowed to browse ACF fields.', 'wp-bbuilder' ),
        array( 'status' => rest_authorization_required_code() )
    );
}

/**
 * Return the field groups relevant to the current editor context.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function wpbb_rest_get_acf_fields( WP_REST_Request $request ) {
    if ( ! wpbb_acf_field_support_available() ) {
        return rest_ensure_response(
            array(
                'available'  => false,
                'restricted' => false,
                'message'    => __( 'Advanced Custom Fields 6.1 or newer must be active to use this block.', 'wp-bbuilder' ),
                'groups'     => array(),
            )
        );
    }

    $source = wpbb_acf_normalize_source( $request->get_param( 'source' ) );
    $context = array(
        'source'    => $source,
        'post_id'   => absint( $request->get_param( 'post_id' ) ),
        'post_type' => sanitize_key( (string) $request->get_param( 'post_type' ) ),
        'taxonomy'  => sanitize_key( (string) $request->get_param( 'taxonomy' ) ),
        'term_id'   => absint( $request->get_param( 'term_id' ) ),
        'user_id'   => absint( $request->get_param( 'user_id' ) ),
    );

    if ( ! $context['post_type'] && $context['post_id'] ) {
        $context['post_type'] = (string) get_post_type( $context['post_id'] );
    }

    if ( ! $context['taxonomy'] && $context['term_id'] ) {
        $term = get_term( $context['term_id'] );
        if ( $term && ! is_wp_error( $term ) ) {
            $context['taxonomy'] = $term->taxonomy;
        }
    }

    if ( ! wpbb_acf_can_browse_source_context( $context ) ) {
        return rest_ensure_response(
            array(
                'available'  => true,
                'restricted' => true,
                'message'    => __( 'You are not allowed to browse fields for this source.', 'wp-bbuilder' ),
                'groups'     => array(),
            )
        );
    }

    $groups = wpbb_acf_prepare_picker_groups( $context );
    $field_count = 0;

    foreach ( $groups as $group ) {
        $field_count += isset( $group['fields'] ) && is_array( $group['fields'] ) ? count( $group['fields'] ) : 0;
    }

    $message = '';
    if ( 0 === $field_count ) {
        $message = __( 'No ACF fields were found for this source and template context.', 'wp-bbuilder' );
    }

    return rest_ensure_response(
        array(
            'available'  => true,
            'restricted' => false,
            'message'    => $message,
            'groups'     => $groups,
        )
    );
}

/**
 * Check access to the object whose field definitions are being browsed.
 *
 * @param array $context Picker context.
 * @return bool
 */
function wpbb_acf_can_browse_source_context( $context ) {
    $source = isset( $context['source'] ) ? $context['source'] : 'current_post';

    switch ( $source ) {
        case 'option':
            return wpbb_acf_options_source_enabled()
                && ( current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_options' ) );

        case 'current_user':
            $user_id = isset( $context['user_id'] ) ? absint( $context['user_id'] ) : 0;
            return ! $user_id
                || get_current_user_id() === $user_id
                || current_user_can( 'edit_user', $user_id );

        case 'current_term':
            $term_id = isset( $context['term_id'] ) ? absint( $context['term_id'] ) : 0;
            if ( ! $term_id ) {
                return current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );
            }

            $term = get_term( $term_id );
            if ( ! $term || is_wp_error( $term ) ) {
                return false;
            }

            return current_user_can( 'edit_term', $term_id )
                || current_user_can( 'manage_categories' )
                || current_user_can( 'edit_posts' );

        case 'current_post':
        default:
            $post_id = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : 0;
            return ! $post_id || current_user_can( 'edit_post', $post_id );
    }
}

/**
 * Normalize a field source attribute.
 *
 * @param mixed $source Source value.
 * @return string
 */
function wpbb_acf_normalize_source( $source ) {
    $source = sanitize_key( (string) $source );
    $allowed = array( 'current_post', 'option', 'current_term', 'current_user' );

    return in_array( $source, $allowed, true ) ? $source : 'current_post';
}

/**
 * Prepare ACF field groups for the editor picker.
 *
 * @param array $context Picker context.
 * @return array
 */
function wpbb_acf_prepare_picker_groups( $context ) {
    $raw_groups = acf_get_field_groups();
    $groups = array();

    if ( empty( $raw_groups ) || ! is_array( $raw_groups ) ) {
        return $groups;
    }

    foreach ( $raw_groups as $field_group ) {
        if ( ! is_array( $field_group ) || empty( $field_group['key'] ) ) {
            continue;
        }

        if ( isset( $field_group['active'] ) && ! $field_group['active'] ) {
            continue;
        }

        if ( ! wpbb_acf_field_group_matches_context( $field_group, $context ) ) {
            continue;
        }

        $raw_fields = acf_get_fields( $field_group );
        if ( empty( $raw_fields ) || ! is_array( $raw_fields ) ) {
            continue;
        }

        $fields = array();
        foreach ( $raw_fields as $field ) {
            $prepared = wpbb_acf_prepare_picker_field( $field );
            if ( $prepared ) {
                $fields[] = $prepared;
            }
        }

        if ( empty( $fields ) ) {
            continue;
        }

        $groups[] = array(
            'key'    => sanitize_key( $field_group['key'] ),
            'title'  => isset( $field_group['title'] ) ? wp_strip_all_tags( $field_group['title'] ) : __( 'ACF field group', 'wp-bbuilder' ),
            'fields' => $fields,
        );
    }

    /**
     * Filter the field groups exposed to the ACF Field block picker.
     *
     * No values are present in this data; only field definitions are included.
     *
     * @param array $groups  Prepared groups.
     * @param array $context Picker context.
     */
    return apply_filters( 'wpbb_acf_field_picker_groups', $groups, $context );
}

/**
 * Decide whether an ACF field group applies to the selected source/context.
 *
 * ACF's full location evaluator is used for a concrete post when available.
 * Other contexts are matched by the object type represented by each location
 * rule. Unknown custom location rules are kept for post contexts so custom ACF
 * integrations do not disappear from the picker.
 *
 * @param array $field_group ACF field group.
 * @param array $context     Picker context.
 * @return bool
 */
function wpbb_acf_field_group_matches_context( $field_group, $context ) {
    $source = isset( $context['source'] ) ? $context['source'] : 'current_post';
    $location_groups = isset( $field_group['location'] ) && is_array( $field_group['location'] )
        ? $field_group['location']
        : array();

    if (
        'current_post' === $source
        && ! empty( $context['post_id'] )
        && function_exists( 'acf_get_field_group_visibility' )
    ) {
        $screen = array(
            'post_id'   => absint( $context['post_id'] ),
            'post_type' => ! empty( $context['post_type'] ) ? $context['post_type'] : get_post_type( $context['post_id'] ),
        );

        if ( ! acf_get_field_group_visibility( $field_group, $screen ) ) {
            return false;
        }
    }

    if ( empty( $location_groups ) ) {
        return true;
    }

    $saw_known_source = false;

    foreach ( $location_groups as $rules ) {
        if ( ! is_array( $rules ) ) {
            continue;
        }

        $or_group_has_source = false;
        $or_group_matches = true;

        foreach ( $rules as $rule ) {
            if ( ! is_array( $rule ) || empty( $rule['param'] ) ) {
                continue;
            }

            $rule_source = wpbb_acf_location_rule_source( $rule );
            if ( ! $rule_source ) {
                continue;
            }

            $saw_known_source = true;
            $or_group_has_source = true;

            if ( $source !== $rule_source || ! wpbb_acf_location_rule_matches_context( $rule, $context ) ) {
                $or_group_matches = false;
                break;
            }
        }

        if ( $or_group_has_source && $or_group_matches ) {
            return true;
        }
    }

    if ( ! $saw_known_source ) {
        return 'current_post' === $source;
    }

    return false;
}

/**
 * Map an ACF location rule to one of the block's supported source types.
 *
 * @param array $rule ACF location rule.
 * @return string
 */
function wpbb_acf_location_rule_source( $rule ) {
    $param = isset( $rule['param'] ) ? sanitize_key( $rule['param'] ) : '';

    $post_params = array(
        'attachment',
        'page',
        'page_parent',
        'page_template',
        'page_type',
        'post',
        'post_category',
        'post_format',
        'post_status',
        'post_taxonomy',
        'post_type',
    );
    $term_params = array( 'taxonomy', 'term' );
    // user_form and user_role attach fields to user objects. Current-user
    // rules are often conditions on another object type, so let ACF's
    // location class identify those instead of assuming a user source.
    $user_params = array( 'user_form', 'user_role' );

    if ( in_array( $param, $post_params, true ) ) {
        return 'current_post';
    }

    if ( in_array( $param, $term_params, true ) ) {
        return 'current_term';
    }

    if ( in_array( $param, $user_params, true ) ) {
        return 'current_user';
    }

    if ( 'options_page' === $param ) {
        return 'option';
    }

    if ( in_array( $param, array( 'block', 'comment', 'widget', 'nav_menu_item' ), true ) ) {
        return 'unsupported';
    }

    if ( function_exists( 'acf_get_location_rule' ) ) {
        $location = acf_get_location_rule( $param );
        if ( $location && method_exists( $location, 'get_object_type' ) ) {
            $object_type = sanitize_key( (string) $location->get_object_type( $rule ) );

            if ( 'post' === $object_type ) {
                return 'current_post';
            }

            if ( 'term' === $object_type ) {
                return 'current_term';
            }

            if ( 'user' === $object_type ) {
                return 'current_user';
            }

            if ( in_array( $object_type, array( 'option', 'options_page' ), true ) ) {
                return 'option';
            }

            if ( $object_type ) {
                return 'unsupported';
            }
        }
    }

    return '';
}

/**
 * Compare a location rule with the limited context known in the editor.
 *
 * Rules such as post status or user role cannot always be evaluated in a Site
 * Editor template. Those rules remain visible and are resolved by ACF when the
 * block renders against a real object.
 *
 * @param array $rule    ACF location rule.
 * @param array $context Picker context.
 * @return bool
 */
function wpbb_acf_location_rule_matches_context( $rule, $context ) {
    $param = isset( $rule['param'] ) ? sanitize_key( $rule['param'] ) : '';
    $operator = isset( $rule['operator'] ) ? (string) $rule['operator'] : '==';
    $expected = isset( $rule['value'] ) ? $rule['value'] : '';
    $actual = null;

    switch ( $param ) {
        case 'post_type':
            $actual = isset( $context['post_type'] ) ? $context['post_type'] : '';
            break;
        case 'post':
        case 'page':
            $actual = isset( $context['post_id'] ) ? (string) absint( $context['post_id'] ) : '';
            break;
        case 'taxonomy':
            $actual = isset( $context['taxonomy'] ) ? $context['taxonomy'] : '';
            break;
        case 'term':
            $actual = isset( $context['term_id'] ) ? (string) absint( $context['term_id'] ) : '';
            break;
        default:
            return true;
    }

    if ( '' === (string) $actual || 'all' === (string) $expected ) {
        return true;
    }

    $expected_values = is_array( $expected ) ? array_map( 'strval', $expected ) : array( (string) $expected );
    $matches = in_array( (string) $actual, $expected_values, true );

    if ( '!=' === $operator ) {
        return ! $matches;
    }

    return $matches;
}

/**
 * Prepare one field definition for the picker.
 *
 * @param array $field ACF field definition.
 * @return array|null
 */
function wpbb_acf_prepare_picker_field( $field ) {
    if ( ! is_array( $field ) || empty( $field['key'] ) || empty( $field['type'] ) ) {
        return null;
    }

    $type = sanitize_key( $field['type'] );
    $supported = wpbb_acf_field_type_is_supported( $type );
    $multiple = wpbb_acf_field_has_multiple_values( $field );

    return array(
        'key'          => sanitize_key( $field['key'] ),
        'name'         => isset( $field['name'] ) ? sanitize_text_field( $field['name'] ) : '',
        'label'        => isset( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : $field['key'],
        'type'         => $type,
        'multiple'     => $multiple,
        'supported'    => $supported,
        'displayModes' => $supported ? wpbb_acf_field_display_modes( $type, $multiple ) : array(),
    );
}

/**
 * Determine whether a field type has an unambiguous read-only representation.
 *
 * Layout/container fields are intentionally disabled. Their values require a
 * schema-specific template rather than a generic text renderer.
 *
 * @param string $type ACF field type.
 * @return bool
 */
function wpbb_acf_field_type_is_supported( $type ) {
    $unsupported = array(
        'accordion',
        'clone',
        'flexible_content',
        'gallery',
        'group',
        'message',
        'password',
        'repeater',
        'tab',
    );

    return ! in_array( sanitize_key( $type ), $unsupported, true );
}

/**
 * Determine whether an ACF field can return multiple values.
 *
 * @param array $field ACF field definition.
 * @return bool
 */
function wpbb_acf_field_has_multiple_values( $field ) {
    if ( ! empty( $field['multiple'] ) ) {
        return true;
    }

    $type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';
    if ( in_array( $type, array( 'checkbox', 'relationship' ), true ) ) {
        return true;
    }

    if ( 'taxonomy' === $type ) {
        $field_type = isset( $field['field_type'] ) ? sanitize_key( $field['field_type'] ) : '';
        return in_array( $field_type, array( 'checkbox', 'multi_select' ), true );
    }

    return false;
}

/**
 * Return the valid render modes for a field definition.
 *
 * @param string $type     ACF field type.
 * @param bool   $multiple Whether the field returns multiple values.
 * @return array
 */
function wpbb_acf_field_display_modes( $type, $multiple = false ) {
    $type = sanitize_key( $type );
    $modes = array( 'auto' );

    switch ( $type ) {
        case 'image':
            return array( 'auto', 'image', 'text' );
        case 'oembed':
            return array( 'auto', 'embed', 'text' );
        case 'icon_picker':
            return array( 'auto', 'icon', 'text' );
        case 'url':
            return array( 'auto', 'text', 'button', 'embed' );
        case 'email':
        case 'file':
        case 'link':
        case 'page_link':
            return array( 'auto', 'text', 'button' );
        case 'post_object':
        case 'relationship':
        case 'taxonomy':
        case 'user':
            $modes[] = 'text';
            if ( ! $multiple ) {
                $modes[] = 'button';
            }
            return $modes;
        default:
            $modes[] = 'text';
            return $modes;
    }
}

/**
 * Render the dynamic ACF Field block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Parsed block instance.
 * @return string
 */
function wpbb_render_acf_field_block( $attributes, $block = null ) {
    if ( ! wpbb_acf_field_support_available() ) {
        return wpbb_acf_editor_message(
            __( 'Advanced Custom Fields 6.1 or newer must be active to render this block.', 'wp-bbuilder' ),
            'warning'
        );
    }

    $field_key = isset( $attributes['fieldKey'] ) ? sanitize_key( $attributes['fieldKey'] ) : '';
    $field_name = isset( $attributes['fieldName'] ) ? sanitize_text_field( $attributes['fieldName'] ) : '';

    if ( ! $field_key && ! $field_name ) {
        return wpbb_acf_editor_message( __( 'Choose an ACF field in the block settings.', 'wp-bbuilder' ) );
    }

    $source = isset( $attributes['fieldSource'] ) ? wpbb_acf_normalize_source( $attributes['fieldSource'] ) : 'current_post';
    $resolved = wpbb_acf_resolve_source( $source, $block );

    if ( empty( $resolved['acf_id'] ) ) {
        return wpbb_acf_empty_or_editor_output(
            $attributes,
            __( 'No object is available for the selected ACF field source in this context.', 'wp-bbuilder' )
        );
    }

    if ( wpbb_acf_is_rest_request() && ! wpbb_acf_current_user_can_read_source( $resolved ) ) {
        return wpbb_acf_editor_message(
            __( 'You are not allowed to preview ACF values from this object.', 'wp-bbuilder' ),
            'error'
        );
    }

    $selector = $field_key ? $field_key : $field_name;
    $raw_field = get_field_object( $selector, $resolved['acf_id'], false, true );
    $formatted_field = get_field_object( $selector, $resolved['acf_id'], true, true );

    if ( ! $raw_field && $field_key && $field_name ) {
        $raw_field = get_field_object( $field_name, $resolved['acf_id'], false, true );
        $formatted_field = get_field_object( $field_name, $resolved['acf_id'], true, true );
    }

    $field = is_array( $raw_field ) ? $raw_field : $formatted_field;

    if ( ! is_array( $field ) || empty( $field['type'] ) ) {
        return wpbb_acf_empty_or_editor_output(
            $attributes,
            __( 'The selected ACF field is not available for this object.', 'wp-bbuilder' )
        );
    }

    $raw_value = is_array( $raw_field ) && array_key_exists( 'value', $raw_field ) ? $raw_field['value'] : null;
    $formatted_value = is_array( $formatted_field ) && array_key_exists( 'value', $formatted_field )
        ? $formatted_field['value']
        : $raw_value;

    $field['raw_value'] = $raw_value;
    $field['formatted_value'] = $formatted_value;

    $type = sanitize_key( $field['type'] );
    if ( ! wpbb_acf_field_type_is_supported( $type ) ) {
        return wpbb_acf_editor_message(
            sprintf(
                /* translators: %s: ACF field type. */
                __( 'The %s field type needs a custom template and cannot be rendered by the generic ACF Field block.', 'wp-bbuilder' ),
                esc_html( $type )
            ),
            'warning'
        );
    }

    // ACF formats oEmbed fields to HTML. The embed renderer needs the saved
    // URL so it can ask WordPress to generate and sanitize provider markup.
    $value = ( 'oembed' === $type && null !== $raw_value ) ? $raw_value : $formatted_value;
    $field['value'] = $value;

    /**
     * Filter a loaded ACF field value before the block formats it.
     *
     * @param mixed $value      Formatted ACF value.
     * @param array $field      ACF field object.
     * @param array $attributes Block attributes.
     * @param array $resolved   Resolved source details.
     */
    $value = apply_filters( 'wpbb_acf_field_value', $value, $field, $attributes, $resolved );

    if ( wpbb_acf_value_is_empty( $value, $type ) ) {
        return wpbb_acf_empty_or_editor_output( $attributes );
    }

    $multiple = wpbb_acf_field_has_multiple_values( $field );
    $available_modes = wpbb_acf_field_display_modes( $type, $multiple );
    $display_as = isset( $attributes['displayAs'] ) ? sanitize_key( $attributes['displayAs'] ) : 'auto';

    if ( ! in_array( $display_as, $available_modes, true ) ) {
        $display_as = 'auto';
    }

    if ( 'auto' === $display_as ) {
        $display_as = wpbb_acf_automatic_display_mode( $type );
    }

    switch ( $display_as ) {
        case 'image':
            $output = wpbb_acf_render_image( $value, $field, $attributes );
            break;
        case 'button':
            $output = wpbb_acf_render_button( $value, $field, $attributes );
            break;
        case 'embed':
            $output = wpbb_acf_render_embed( $value, $field, $attributes );
            break;
        case 'icon':
            $output = wpbb_acf_render_icon( $value, $field, $attributes );
            break;
        case 'text':
        default:
            $output = wpbb_acf_render_text( $value, $field, $attributes );
            break;
    }

    if ( '' === $output ) {
        $output = wpbb_acf_empty_or_editor_output( $attributes );
    }

    /**
     * Filter the final ACF Field block markup.
     *
     * @param string $output     Sanitized block markup.
     * @param mixed  $value      Formatted ACF value.
     * @param array  $field      ACF field object.
     * @param array  $attributes Block attributes.
     * @param array  $resolved   Resolved source details.
     */
    return apply_filters( 'wpbb_acf_field_output', $output, $value, $field, $attributes, $resolved );
}

/**
 * Pick the automatic display mode for a field type.
 *
 * @param string $type ACF field type.
 * @return string
 */
function wpbb_acf_automatic_display_mode( $type ) {
    switch ( sanitize_key( $type ) ) {
        case 'image':
            return 'image';
        case 'oembed':
            return 'embed';
        case 'icon_picker':
            return 'icon';
        case 'file':
        case 'link':
            return 'button';
        default:
            return 'text';
    }
}

/**
 * Resolve an ACF source identifier from block context and the main query.
 *
 * @param string        $source Source attribute.
 * @param WP_Block|null $block  Parsed block instance.
 * @return array
 */
function wpbb_acf_resolve_source( $source, $block = null ) {
    $source = wpbb_acf_normalize_source( $source );
    $context = array();

    if ( is_object( $block ) && isset( $block->context ) && is_array( $block->context ) ) {
        $context = $block->context;
    }

    $resolved = array(
        'source' => $source,
        'type'   => '',
        'id'     => 0,
        'acf_id' => '',
        'object' => null,
    );

    switch ( $source ) {
        case 'option':
            if ( ! wpbb_acf_options_source_enabled() ) {
                break;
            }
            $resolved['type'] = 'option';
            $resolved['acf_id'] = 'option';
            break;

        case 'current_term':
            $term_id = ! empty( $context['termId'] ) ? absint( $context['termId'] ) : 0;
            $taxonomy = ! empty( $context['taxonomy'] ) ? sanitize_key( $context['taxonomy'] ) : '';
            $term = null;

            if ( $term_id ) {
                $term = get_term( $term_id, $taxonomy ? $taxonomy : '' );
            }

            if ( ! $term || is_wp_error( $term ) ) {
                $queried = get_queried_object();
                if ( $queried instanceof WP_Term ) {
                    $term = $queried;
                }
            }

            if ( $term && ! is_wp_error( $term ) ) {
                $resolved['type'] = 'term';
                $resolved['id'] = absint( $term->term_id );
                $resolved['object'] = $term;
                $resolved['acf_id'] = sanitize_key( $term->taxonomy ) . '_' . absint( $term->term_id );
            }
            break;

        case 'current_user':
            $user_id = ! empty( $context['userId'] ) ? absint( $context['userId'] ) : 0;
            $user = null;

            if ( $user_id ) {
                $user = get_userdata( $user_id );
            }

            if ( ! $user ) {
                $queried = get_queried_object();
                if ( $queried instanceof WP_User ) {
                    $user = $queried;
                }
            }

            if ( ! $user ) {
                $post_id = ! empty( $context['postId'] ) ? absint( $context['postId'] ) : get_the_ID();
                if ( $post_id ) {
                    $author_id = absint( get_post_field( 'post_author', $post_id ) );
                    if ( $author_id ) {
                        $user = get_userdata( $author_id );
                    }
                }
            }

            if ( ! $user && get_current_user_id() ) {
                $user = get_userdata( get_current_user_id() );
            }

            if ( $user ) {
                $resolved['type'] = 'user';
                $resolved['id'] = absint( $user->ID );
                $resolved['object'] = $user;
                $resolved['acf_id'] = 'user_' . absint( $user->ID );
            }
            break;

        case 'current_post':
        default:
            $post_id = ! empty( $context['postId'] ) ? absint( $context['postId'] ) : 0;
            if ( ! $post_id ) {
                $post_id = get_the_ID();
            }
            if ( ! $post_id ) {
                $queried = get_queried_object();
                if ( $queried instanceof WP_Post ) {
                    $post_id = absint( $queried->ID );
                }
            }

            if ( $post_id ) {
                $post = get_post( $post_id );
                $resolved['type'] = 'post';
                $resolved['id'] = $post_id;
                $resolved['object'] = $post;
                $resolved['acf_id'] = $post_id;
            }
            break;
    }

    return apply_filters( 'wpbb_acf_field_resolved_source', $resolved, $source, $block );
}

/**
 * Check whether the current user may read an object during a REST render.
 *
 * @param array $resolved Resolved source.
 * @return bool
 */
function wpbb_acf_current_user_can_read_source( $resolved ) {
    $type = isset( $resolved['type'] ) ? $resolved['type'] : '';
    $id = isset( $resolved['id'] ) ? absint( $resolved['id'] ) : 0;

    switch ( $type ) {
        case 'post':
            return $id && current_user_can( 'edit_post', $id );
        case 'option':
            return current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_options' );
        case 'term':
            return $id && (
                current_user_can( 'edit_term', $id )
                || current_user_can( 'manage_categories' )
                || current_user_can( 'edit_posts' )
            );
        case 'user':
            return $id && ( get_current_user_id() === $id || current_user_can( 'edit_user', $id ) );
        default:
            return false;
    }
}

/**
 * Determine whether code is rendering as part of a REST request.
 *
 * @return bool
 */
function wpbb_acf_is_rest_request() {
    return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Determine whether a field value is empty without treating zero as empty.
 *
 * False is a meaningful value for a true/false field.
 *
 * @param mixed  $value Field value.
 * @param string $type  Field type.
 * @return bool
 */
function wpbb_acf_value_is_empty( $value, $type = '' ) {
    if ( false === $value && 'true_false' === sanitize_key( $type ) ) {
        return false;
    }

    if ( null === $value || '' === $value || false === $value ) {
        return true;
    }

    if ( is_array( $value ) && empty( $value ) ) {
        return true;
    }

    return false;
}

/**
 * Return configured empty-state markup, or an editor-only diagnostic message.
 *
 * @param array  $attributes Block attributes.
 * @param string $editor_message Optional editor diagnostic.
 * @return string
 */
function wpbb_acf_empty_or_editor_output( $attributes, $editor_message = '' ) {
    $show_message = ! empty( $attributes['showEmptyMessage'] );
    $empty_message = isset( $attributes['emptyMessage'] ) ? trim( (string) $attributes['emptyMessage'] ) : '';

    if ( $show_message && '' !== $empty_message ) {
        $wrapper = wpbb_acf_wrapper_attributes( 'empty' );
        return '<div ' . $wrapper . '><span class="wpbb-acf-field__empty">' . esc_html( $empty_message ) . '</span></div>';
    }

    if ( $editor_message ) {
        return wpbb_acf_editor_message( $editor_message );
    }

    return '';
}

/**
 * Render a message only in the block editor/admin context.
 *
 * @param string $message Message.
 * @param string $status  info, warning, or error.
 * @return string
 */
function wpbb_acf_editor_message( $message, $status = 'info' ) {
    if ( ! is_admin() && ! wpbb_acf_is_rest_request() ) {
        return '';
    }

    $status = in_array( $status, array( 'warning', 'error' ), true ) ? $status : 'info';
    $class = 'wpbb-acf-field-editor-message';
    if ( 'info' !== $status ) {
        $class .= ' is-' . $status;
    }

    return '<div class="' . esc_attr( $class ) . '">' . esc_html( $message ) . '</div>';
}

/**
 * Build block wrapper attributes with a display-mode class.
 *
 * @param string $mode Display mode.
 * @param array  $extra Additional attributes.
 * @return string
 */
function wpbb_acf_wrapper_attributes( $mode, $extra = array() ) {
    $class = 'wpbb-acf-field wpbb-acf-field--' . sanitize_html_class( $mode );
    if ( ! empty( $extra['class'] ) ) {
        $class .= ' ' . $extra['class'];
    }
    $extra['class'] = trim( $class );

    if ( function_exists( 'get_block_wrapper_attributes' ) ) {
        return get_block_wrapper_attributes( $extra );
    }

    $parts = array();
    foreach ( $extra as $name => $value ) {
        $parts[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
    }

    return implode( ' ', $parts );
}

/**
 * Render a field as text or a list of text values.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return string
 */
function wpbb_acf_render_text( $value, $field, $attributes ) {
    $type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';
    $items = wpbb_acf_text_items( $value, $field, $attributes );

    if ( empty( $items ) ) {
        return '';
    }

    $tag = isset( $attributes['tagName'] ) ? strtolower( (string) $attributes['tagName'] ) : 'p';
    $allowed_tags = array( 'p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol' );
    if ( ! in_array( $tag, $allowed_tags, true ) ) {
        $tag = 'p';
    }

    if ( 'wysiwyg' === $type || ( 'textarea' === $type && ! empty( $field['new_lines'] ) ) ) {
        $tag = 'div';
    }

    $prefix = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
    $suffix = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '';
    $separator = isset( $attributes['separator'] ) ? (string) $attributes['separator'] : ', ';
    $is_list = in_array( $tag, array( 'ul', 'ol' ), true );
    $rich_content = 'wysiwyg' === $type || ( 'textarea' === $type && isset( $field['new_lines'] ) && 'wpautop' === $field['new_lines'] );
    $wrapper = wpbb_acf_wrapper_attributes( 'text' );
    $html = '<' . tag_escape( $tag ) . ' ' . $wrapper . '>';

    if ( $is_list ) {
        $last_index = count( $items ) - 1;
        foreach ( array_values( $items ) as $index => $item ) {
            $html .= '<li>';
            if ( 0 === $index && '' !== $prefix ) {
                $html .= '<span class="wpbb-acf-field__prefix">' . esc_html( $prefix ) . '</span>';
            }
            $value_tag = $rich_content ? 'div' : 'span';
            $html .= '<' . $value_tag . ' class="wpbb-acf-field__value">' . $item . '</' . $value_tag . '>';
            if ( $last_index === $index && '' !== $suffix ) {
                $html .= '<span class="wpbb-acf-field__suffix">' . esc_html( $suffix ) . '</span>';
            }
            $html .= '</li>';
        }
    } else {
        if ( '' !== $prefix ) {
            $html .= '<span class="wpbb-acf-field__prefix">' . esc_html( $prefix ) . '</span>';
        }

        foreach ( array_values( $items ) as $index => $item ) {
            if ( $index > 0 && '' !== $separator ) {
                $html .= '<span class="wpbb-acf-field__separator">' . esc_html( $separator ) . '</span>';
            }
            $value_tag = $rich_content ? 'div' : 'span';
            $html .= '<' . $value_tag . ' class="wpbb-acf-field__value">' . $item . '</' . $value_tag . '>';
        }

        if ( '' !== $suffix ) {
            $html .= '<span class="wpbb-acf-field__suffix">' . esc_html( $suffix ) . '</span>';
        }
    }

    $html .= '</' . tag_escape( $tag ) . '>';

    return $html;
}

/**
 * Convert an ACF value to sanitized text fragments.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return array
 */
function wpbb_acf_text_items( $value, $field, $attributes ) {
    $type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';

    if ( 'true_false' === $type ) {
        $text = $value
            ? ( isset( $attributes['trueText'] ) ? $attributes['trueText'] : __( 'Yes', 'wp-bbuilder' ) )
            : ( isset( $attributes['falseText'] ) ? $attributes['falseText'] : __( 'No', 'wp-bbuilder' ) );
        return array( esc_html( $text ) );
    }

    if ( 'wysiwyg' === $type ) {
        $content = apply_filters( 'acf_the_content', (string) $value );
        return '' !== trim( wp_strip_all_tags( $content ) ) ? array( wp_kses_post( $content ) ) : array();
    }

    if ( 'textarea' === $type ) {
        $content = (string) $value;
        $new_lines = isset( $field['new_lines'] ) ? $field['new_lines'] : '';
        if ( 'wpautop' === $new_lines ) {
            $content = wpautop( $content );
        } elseif ( 'br' === $new_lines ) {
            $content = nl2br( esc_html( $content ) );
        } else {
            $content = esc_html( $content );
        }
        return '' !== trim( wp_strip_all_tags( $content ) ) ? array( wp_kses_post( $content ) ) : array();
    }

    if ( 'google_map' === $type && is_array( $value ) ) {
        $map_value = isset( $value['address'] ) ? $value['address'] : '';
        if ( '' === (string) $map_value && isset( $value['lat'], $value['lng'] ) ) {
            $map_value = $value['lat'] . ', ' . $value['lng'];
        }
        return '' !== (string) $map_value ? array( esc_html( $map_value ) ) : array();
    }

    if ( 'link' === $type && is_array( $value ) ) {
        $text = ! empty( $value['title'] ) ? $value['title'] : ( isset( $value['url'] ) ? $value['url'] : '' );
        return '' !== (string) $text ? array( esc_html( $text ) ) : array();
    }

    if ( 'file' === $type && is_array( $value ) ) {
        $text = wpbb_acf_file_label_from_array( $value );
        return '' !== $text ? array( esc_html( $text ) ) : array();
    }

    if ( 'image' === $type && is_array( $value ) ) {
        $text = '';
        foreach ( array( 'alt', 'caption', 'title', 'filename', 'url' ) as $key ) {
            if ( ! empty( $value[ $key ] ) ) {
                $text = $value[ $key ];
                break;
            }
        }
        return '' !== (string) $text ? array( esc_html( $text ) ) : array();
    }

    $values = wpbb_acf_value_to_list( $value );
    $items = array();

    foreach ( $values as $item ) {
        $text = wpbb_acf_scalar_label( $item, $type, $field );
        if ( '' !== $text ) {
            $items[] = esc_html( $text );
        }
    }

    return $items;
}

/**
 * Convert a possibly singular value into a list without exploding associative
 * arrays such as ACF link, image, file, or user return values.
 *
 * @param mixed $value Value.
 * @return array
 */
function wpbb_acf_value_to_list( $value ) {
    if ( ! is_array( $value ) ) {
        return array( $value );
    }

    if ( wpbb_acf_is_list_array( $value ) ) {
        return $value;
    }

    if (
        isset( $value['ID'] )
        || isset( $value['id'] )
        || isset( $value['term_id'] )
        || isset( $value['user_email'] )
        || isset( $value['display_name'] )
        || isset( $value['value'] )
        || isset( $value['label'] )
    ) {
        return array( $value );
    }

    return array_values( $value );
}

/**
 * PHP 7.4-compatible array_is_list equivalent.
 *
 * @param array $array Array.
 * @return bool
 */
function wpbb_acf_is_list_array( $array ) {
    $expected = 0;
    foreach ( array_keys( $array ) as $key ) {
        if ( $key !== $expected ) {
            return false;
        }
        ++$expected;
    }
    return true;
}

/**
 * Produce a human-readable scalar label for an ACF value.
 *
 * @param mixed  $item  Item.
 * @param string $type  Field type.
 * @param array  $field Field definition.
 * @return string
 */
function wpbb_acf_scalar_label( $item, $type, $field ) {
    if ( $item instanceof WP_Post ) {
        return get_the_title( $item );
    }

    if ( $item instanceof WP_Term ) {
        return (string) $item->name;
    }

    if ( $item instanceof WP_User ) {
        return (string) $item->display_name;
    }

    if ( is_object( $item ) ) {
        if ( isset( $item->post_title ) ) {
            return (string) $item->post_title;
        }
        if ( isset( $item->name ) ) {
            return (string) $item->name;
        }
        if ( isset( $item->display_name ) ) {
            return (string) $item->display_name;
        }
        return '';
    }

    if ( is_array( $item ) ) {
        foreach ( array( 'label', 'title', 'display_name', 'name', 'value' ) as $key ) {
            if ( isset( $item[ $key ] ) && is_scalar( $item[ $key ] ) ) {
                return (string) $item[ $key ];
            }
        }

        if ( isset( $item['ID'] ) ) {
            $item = $item['ID'];
        } elseif ( isset( $item['id'] ) ) {
            $item = $item['id'];
        } elseif ( isset( $item['term_id'] ) ) {
            $item = $item['term_id'];
        } else {
            return '';
        }
    }

    if ( is_numeric( $item ) ) {
        $id = absint( $item );

        if ( in_array( $type, array( 'post_object', 'relationship', 'page_link' ), true ) ) {
            $post = get_post( $id );
            return $post ? get_the_title( $post ) : (string) $item;
        }

        if ( 'taxonomy' === $type ) {
            $taxonomy = isset( $field['taxonomy'] ) ? $field['taxonomy'] : '';
            $term = get_term( $id, $taxonomy );
            return $term && ! is_wp_error( $term ) ? (string) $term->name : (string) $item;
        }

        if ( 'user' === $type ) {
            $user = get_userdata( $id );
            return $user ? (string) $user->display_name : (string) $item;
        }
    }

    if ( is_bool( $item ) ) {
        return $item ? __( 'Yes', 'wp-bbuilder' ) : __( 'No', 'wp-bbuilder' );
    }

    return is_scalar( $item ) ? (string) $item : '';
}

/**
 * Render an ACF image field.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return string
 */
function wpbb_acf_render_image( $value, $field, $attributes ) {
    $image = wpbb_acf_resolve_image( $value );
    if ( empty( $image['id'] ) && empty( $image['url'] ) ) {
        return '';
    }

    $size = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'large';
    $allowed_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' );
    if ( ! in_array( $size, $allowed_sizes, true ) ) {
        $size = 'large';
    }

    $img = '';
    if ( ! empty( $image['id'] ) ) {
        $image_attributes = array();
        if ( ! empty( $image['alt'] ) ) {
            $image_attributes['alt'] = $image['alt'];
        }
        $img = wp_get_attachment_image( $image['id'], $size, false, $image_attributes );
    }

    if ( ! $img && ! empty( $image['url'] ) ) {
        $img = '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['alt'] ) . '" loading="lazy" />';
    }

    if ( ! $img ) {
        return '';
    }

    $caption = '';
    if ( ! empty( $attributes['showImageCaption'] ) ) {
        $caption = ! empty( $image['caption'] ) ? $image['caption'] : '';
        if ( ! $caption && ! empty( $image['id'] ) ) {
            $caption = wp_get_attachment_caption( $image['id'] );
        }
    }

    $wrapper = wpbb_acf_wrapper_attributes( 'image' );
    $html = '<figure ' . $wrapper . '>' . $img;
    if ( $caption ) {
        $html .= '<figcaption>' . wp_kses_post( $caption ) . '</figcaption>';
    }
    $html .= '</figure>';

    return $html;
}

/**
 * Normalize an ACF image/icon media return value.
 *
 * @param mixed $value Value.
 * @return array
 */
function wpbb_acf_resolve_image( $value ) {
    $image = array(
        'id'      => 0,
        'url'     => '',
        'alt'     => '',
        'caption' => '',
    );

    if ( is_numeric( $value ) ) {
        $image['id'] = absint( $value );
    } elseif ( is_string( $value ) ) {
        $image['url'] = esc_url_raw( $value );
    } elseif ( is_array( $value ) ) {
        $image['id'] = ! empty( $value['ID'] ) ? absint( $value['ID'] ) : ( ! empty( $value['id'] ) ? absint( $value['id'] ) : 0 );
        $image['url'] = ! empty( $value['url'] ) ? esc_url_raw( $value['url'] ) : '';
        $image['alt'] = ! empty( $value['alt'] ) ? (string) $value['alt'] : '';
        $image['caption'] = ! empty( $value['caption'] ) ? (string) $value['caption'] : '';
    }

    if ( $image['id'] ) {
        if ( ! $image['url'] ) {
            $image['url'] = (string) wp_get_attachment_url( $image['id'] );
        }
        if ( ! $image['alt'] ) {
            $image['alt'] = (string) get_post_meta( $image['id'], '_wp_attachment_image_alt', true );
        }
    }

    return $image;
}

/**
 * Render a link-capable field as a button.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return string
 */
function wpbb_acf_render_button( $value, $field, $attributes ) {
    $link = wpbb_acf_resolve_link( $value, $field );
    if ( empty( $link['url'] ) ) {
        return '';
    }

    $label = isset( $attributes['buttonLabel'] ) ? trim( (string) $attributes['buttonLabel'] ) : '';
    if ( '' === $label ) {
        $label = ! empty( $link['label'] ) ? $link['label'] : ( isset( $field['label'] ) ? $field['label'] : $link['url'] );
    }

    $new_tab = ! empty( $attributes['openInNewTab'] ) || '_blank' === $link['target'];
    $rel = array();
    if ( $new_tab ) {
        $rel[] = 'noopener';
        $rel[] = 'noreferrer';
    }
    if ( ! empty( $attributes['nofollow'] ) ) {
        $rel[] = 'nofollow';
    }

    $safe_url = esc_url( $link['url'] );
    if ( ! $safe_url ) {
        return '';
    }

    $anchor_attributes = array(
        'class="wpbb-acf-field__button wp-element-button"',
        'href="' . $safe_url . '"',
    );

    if ( $new_tab ) {
        $anchor_attributes[] = 'target="_blank"';
    }
    if ( ! empty( $rel ) ) {
        $anchor_attributes[] = 'rel="' . esc_attr( implode( ' ', array_unique( $rel ) ) ) . '"';
    }
    if ( ! empty( $attributes['downloadFile'] ) && 'file' === sanitize_key( $field['type'] ) ) {
        $anchor_attributes[] = 'download';
    }

    $wrapper = wpbb_acf_wrapper_attributes( 'button' );

    return '<div ' . $wrapper . '><a ' . implode( ' ', $anchor_attributes ) . '>' . esc_html( $label ) . '</a></div>';
}

/**
 * Resolve a URL and label from a link-capable ACF value.
 *
 * @param mixed $value Field value.
 * @param array $field ACF field object.
 * @return array
 */
function wpbb_acf_resolve_link( $value, $field ) {
    $type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';
    $result = array(
        'url'    => '',
        'label'  => '',
        'target' => '',
    );

    if ( is_array( $value ) && wpbb_acf_is_list_array( $value ) ) {
        $value = reset( $value );
    }

    if ( $value instanceof WP_Post ) {
        $result['url'] = get_permalink( $value );
        $result['label'] = get_the_title( $value );
        return $result;
    }

    if ( $value instanceof WP_Term ) {
        $term_link = get_term_link( $value );
        $result['url'] = is_wp_error( $term_link ) ? '' : $term_link;
        $result['label'] = $value->name;
        return $result;
    }

    if ( $value instanceof WP_User ) {
        $result['url'] = get_author_posts_url( $value->ID );
        $result['label'] = $value->display_name;
        return $result;
    }

    if ( is_array( $value ) ) {
        if ( ! empty( $value['url'] ) ) {
            $result['url'] = $value['url'];
            $result['label'] = ! empty( $value['title'] ) ? $value['title'] : wpbb_acf_file_label_from_array( $value );
            $result['target'] = ! empty( $value['target'] ) ? $value['target'] : '';
            return $result;
        }

        if ( isset( $value['ID'] ) || isset( $value['id'] ) ) {
            $value = isset( $value['ID'] ) ? $value['ID'] : $value['id'];
        } elseif ( isset( $value['term_id'] ) ) {
            $term_id = absint( $value['term_id'] );
            $taxonomy = ! empty( $value['taxonomy'] ) ? $value['taxonomy'] : ( isset( $field['taxonomy'] ) ? $field['taxonomy'] : '' );
            $term = get_term( $term_id, $taxonomy );
            if ( $term && ! is_wp_error( $term ) ) {
                $term_link = get_term_link( $term );
                $result['url'] = is_wp_error( $term_link ) ? '' : $term_link;
                $result['label'] = $term->name;
            }
            return $result;
        } else {
            return $result;
        }
    }

    if ( is_numeric( $value ) ) {
        $id = absint( $value );

        if ( 'file' === $type ) {
            $result['url'] = (string) wp_get_attachment_url( $id );
            $result['label'] = get_the_title( $id );
            if ( ! $result['label'] && $result['url'] ) {
                $result['label'] = wp_basename( wp_parse_url( $result['url'], PHP_URL_PATH ) );
            }
            return $result;
        }

        if ( in_array( $type, array( 'post_object', 'relationship', 'page_link' ), true ) ) {
            $post = get_post( $id );
            if ( $post ) {
                $result['url'] = get_permalink( $post );
                $result['label'] = get_the_title( $post );
            }
            return $result;
        }

        if ( 'taxonomy' === $type ) {
            $taxonomy = isset( $field['taxonomy'] ) ? $field['taxonomy'] : '';
            $term = get_term( $id, $taxonomy );
            if ( $term && ! is_wp_error( $term ) ) {
                $term_link = get_term_link( $term );
                $result['url'] = is_wp_error( $term_link ) ? '' : $term_link;
                $result['label'] = $term->name;
            }
            return $result;
        }

        if ( 'user' === $type ) {
            $user = get_userdata( $id );
            if ( $user ) {
                $result['url'] = get_author_posts_url( $user->ID );
                $result['label'] = $user->display_name;
            }
            return $result;
        }
    }

    if ( is_string( $value ) ) {
        $value = trim( $value );
        if ( 'email' === $type && is_email( $value ) ) {
            $result['url'] = 'mailto:' . sanitize_email( $value );
            $result['label'] = $value;
        } else {
            $result['url'] = $value;
            $result['label'] = $value;
        }
    }

    return $result;
}

/**
 * Derive a file/link label from an ACF array return value.
 *
 * @param array $value Value.
 * @return string
 */
function wpbb_acf_file_label_from_array( $value ) {
    foreach ( array( 'title', 'filename', 'name', 'alt' ) as $key ) {
        if ( ! empty( $value[ $key ] ) ) {
            return (string) $value[ $key ];
        }
    }

    if ( ! empty( $value['url'] ) ) {
        $path = wp_parse_url( $value['url'], PHP_URL_PATH );
        return $path ? wp_basename( $path ) : (string) $value['url'];
    }

    return '';
}

/**
 * Render a URL/oEmbed field.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return string
 */
function wpbb_acf_render_embed( $value, $field, $attributes ) {
    $link = wpbb_acf_resolve_link( $value, $field );
    $url = ! empty( $link['url'] ) ? $link['url'] : ( is_string( $value ) ? $value : '' );
    $url = esc_url_raw( $url );

    if ( ! $url ) {
        return '';
    }

    $embed = wp_oembed_get( $url );
    if ( ! $embed ) {
        return '';
    }

    $wrapper = wpbb_acf_wrapper_attributes( 'embed' );
    return '<div ' . $wrapper . '>' . wp_kses( $embed, wpbb_acf_embed_allowed_html() ) . '</div>';
}

/**
 * Allowed HTML for WordPress oEmbed output.
 *
 * @return array
 */
function wpbb_acf_embed_allowed_html() {
    $allowed = wp_kses_allowed_html( 'post' );
    $allowed['iframe'] = array(
        'allow'          => true,
        'allowfullscreen'=> true,
        'class'          => true,
        'frameborder'    => true,
        'height'         => true,
        'loading'        => true,
        'referrerpolicy' => true,
        'src'            => true,
        'style'          => true,
        'title'          => true,
        'width'          => true,
    );
    $allowed['video'] = array(
        'autoplay'    => true,
        'class'       => true,
        'controls'    => true,
        'height'      => true,
        'loop'        => true,
        'muted'       => true,
        'playsinline' => true,
        'poster'      => true,
        'preload'     => true,
        'src'         => true,
        'width'       => true,
    );
    $allowed['source'] = array(
        'src'  => true,
        'type' => true,
    );

    return $allowed;
}

/**
 * Render an ACF Icon Picker value.
 *
 * @param mixed $value      Field value.
 * @param array $field      ACF field object.
 * @param array $attributes Block attributes.
 * @return string
 */
function wpbb_acf_render_icon( $value, $field, $attributes ) {
    $size = isset( $attributes['iconSize'] ) ? absint( $attributes['iconSize'] ) : 32;
    $size = min( 160, max( 12, $size ) );
    $icon = wpbb_acf_icon_markup( $value, $size );

    if ( ! $icon ) {
        return '';
    }

    $wrapper = wpbb_acf_wrapper_attributes(
        'icon',
        array( 'style' => 'font-size:' . absint( $size ) . 'px' )
    );
    return '<span ' . $wrapper . '>' . $icon . '</span>';
}

/**
 * Normalize the possible ACF Icon Picker return formats.
 *
 * @param mixed $value Icon value.
 * @param int   $size  Pixel size.
 * @return string
 */
function wpbb_acf_icon_markup( $value, $size ) {
    if ( is_array( $value ) ) {
        $type = ! empty( $value['type'] ) ? sanitize_key( $value['type'] ) : '';
        $inner = array_key_exists( 'value', $value ) ? $value['value'] : null;

        if ( in_array( $type, array( 'dashicons', 'dashicon' ), true ) ) {
            return wpbb_acf_dashicon_markup( $inner );
        }

        if ( in_array( $type, array( 'media_library', 'media', 'image' ), true ) ) {
            return wpbb_acf_icon_image_markup( $inner, $size );
        }

        if ( null !== $inner ) {
            $nested = wpbb_acf_icon_markup( $inner, $size );
            if ( $nested ) {
                return $nested;
            }
        }

        if ( isset( $value['ID'] ) || isset( $value['id'] ) || isset( $value['url'] ) ) {
            return wpbb_acf_icon_image_markup( $value, $size );
        }

        return '';
    }

    if ( is_numeric( $value ) ) {
        return wpbb_acf_icon_image_markup( $value, $size );
    }

    if ( ! is_string( $value ) ) {
        return '';
    }

    $value = trim( $value );
    if ( '' === $value ) {
        return '';
    }

    if ( 0 === stripos( $value, '<svg' ) ) {
        return wp_kses( $value, wpbb_acf_svg_allowed_html() );
    }

    if ( false !== strpos( $value, 'dashicons-' ) ) {
        return wpbb_acf_dashicon_markup( $value );
    }

    if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
        return wpbb_acf_icon_image_markup( $value, $size );
    }

    return '';
}

/**
 * Render a Dashicons class string.
 *
 * @param mixed $value Class value.
 * @return string
 */
function wpbb_acf_dashicon_markup( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }

    $classes = preg_split( '/\s+/', trim( $value ) );
    $safe = array( 'dashicons' );

    foreach ( $classes as $class ) {
        $class = sanitize_html_class( $class );
        if ( 0 === strpos( $class, 'dashicons-' ) ) {
            $safe[] = $class;
        }
    }

    if ( 1 === count( $safe ) ) {
        return '';
    }

    wp_enqueue_style( 'dashicons' );
    return '<span class="' . esc_attr( implode( ' ', array_unique( $safe ) ) ) . '" aria-hidden="true"></span>';
}

/**
 * Render a media-library icon as an image.
 *
 * @param mixed $value Image value.
 * @param int   $size  Pixel size.
 * @return string
 */
function wpbb_acf_icon_image_markup( $value, $size ) {
    $image = wpbb_acf_resolve_image( $value );
    if ( ! empty( $image['id'] ) ) {
        $html = wp_get_attachment_image(
            $image['id'],
            'thumbnail',
            false,
            array(
                'alt'   => $image['alt'],
                'style' => 'width:' . absint( $size ) . 'px;height:' . absint( $size ) . 'px;object-fit:contain',
            )
        );
        if ( $html ) {
            return $html;
        }
    }

    if ( ! empty( $image['url'] ) ) {
        return '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['alt'] ) . '" style="width:' . absint( $size ) . 'px;height:' . absint( $size ) . 'px;object-fit:contain" loading="lazy" />';
    }

    return '';
}

/**
 * Allowed SVG markup for an ACF Icon Picker SVG value.
 *
 * @return array
 */
function wpbb_acf_svg_allowed_html() {
    $global = array(
        'aria-hidden'       => true,
        'class'             => true,
        'clip-path'         => true,
        'd'                 => true,
        'fill'              => true,
        'fill-rule'         => true,
        'height'            => true,
        'id'                => true,
        'opacity'           => true,
        'points'            => true,
        'preserveaspectratio'=> true,
        'role'              => true,
        'stroke'            => true,
        'stroke-linecap'    => true,
        'stroke-linejoin'   => true,
        'stroke-width'      => true,
        'style'             => true,
        'transform'         => true,
        'viewbox'           => true,
        'width'             => true,
        'x'                 => true,
        'x1'                => true,
        'x2'                => true,
        'xlink:href'        => true,
        'y'                 => true,
        'y1'                => true,
        'y2'                => true,
    );

    $tags = array( 'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'clippath', 'use', 'symbol', 'title' );
    $allowed = array();
    foreach ( $tags as $tag ) {
        $allowed[ $tag ] = $global;
    }

    return $allowed;
}
