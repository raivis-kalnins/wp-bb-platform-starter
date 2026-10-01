<?php /**
 * Tabbed Information Shortcode
 * Displays tabbed content from ACF repeater field 'tabbed_information'
 * Using Bootstrap AREOI Navs and Tabs
 */

function tabbed_information_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'style' => 'tabs', // Options: 'default', 'tabs', 'pills'
        'alignment' => 'start', // Options: 'start', 'center', 'end'
        'fill' => false, // true for nav-fill, false for default
    ), $atts);

    $post_id = $atts['post_id'];
    
    // Get ACF repeater field
    $tabbed_information = get_field('tabbed_information', $post_id);
    
    // Return early if no tabs
    if (!$tabbed_information || empty($tabbed_information)) {
        return '';
    }
    
    // Generate unique ID for this tabs instance
    $unique_id = 'tabs-' . uniqid();
    
    // Build nav classes
    $nav_classes = ['nav'];
    if ($atts['style'] === 'tabs') {
        $nav_classes[] = 'nav-tabs';
    } elseif ($atts['style'] === 'pills') {
        $nav_classes[] = 'nav-pills';
    }
    
    if ($atts['fill']) {
        $nav_classes[] = 'nav-fill';
    }
    
    // Alignment classes
    $alignment_classes = '';
    if ($atts['alignment'] === 'center') {
        $alignment_classes = 'justify-content-center';
    } elseif ($atts['alignment'] === 'end') {
        $alignment_classes = 'justify-content-end';
    }
    
    // Start output buffering
    ob_start();
    ?>
    
    <div class="woo-tabs tabbed-information-wrapper" id="<?php echo esc_attr($unique_id); ?>">
        <!-- Nav tabs -->
        <ul class="<?php echo esc_attr(implode(' ', $nav_classes) . ' ' . $alignment_classes); ?>" role="tablist">
            <?php foreach ($tabbed_information as $index => $tab) : 
                $tab_id = $unique_id . '-tab-' . $index;
                $pane_id = $unique_id . '-pane-' . $index;
                $is_active = ($index === 0) ? 'active' : '';
                $is_selected = ($index === 0) ? 'true' : 'false';
            ?>
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link <?php echo $is_active; ?>" 
                        id="<?php echo esc_attr($tab_id); ?>" 
                        data-bs-toggle="tab" 
                        data-bs-target="#<?php echo esc_attr($pane_id); ?>" 
                        type="button" 
                        role="tab" 
                        aria-controls="<?php echo esc_attr($pane_id); ?>" 
                        aria-selected="<?php echo $is_selected; ?>">
                        <?php echo esc_html($tab['tab_name']); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Tab panes -->
        <div class="tab-content mt-3">
            <?php foreach ($tabbed_information as $index => $tab) : 
                $tab_id = $unique_id . '-tab-' . $index;
                $pane_id = $unique_id . '-pane-' . $index;
                $is_active = ($index === 0) ? 'show active' : '';
            ?>
                <div 
                    class="tab-pane fade <?php echo $is_active; ?>" 
                    id="<?php echo esc_attr($pane_id); ?>" 
                    role="tabpanel" 
                    aria-labelledby="<?php echo esc_attr($tab_id); ?>" 
                    tabindex="0">
                    <?php echo wp_kses_post($tab['tab_content']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode('tabbed_information', 'tabbed_information_shortcode');
