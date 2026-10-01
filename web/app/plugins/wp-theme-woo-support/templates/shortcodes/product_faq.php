<?php
/**
 * FAQ Accordion Shortcode
 * Displays FAQ accordion from ACF repeater field 'faq'
 * Using Bootstrap 5 Accordion
 */

function faq_accordion_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'flush' => false, // true for accordion-flush style
        'always_open' => false, // true to allow multiple items open at once
    ), $atts);

    $post_id = $atts['post_id'];
    
    // Get ACF repeater field
    $faq = get_field('faq', $post_id);
    
    // Return early if no FAQ items
    if (!$faq || empty($faq)) {
        return '';
    }
    
    // Generate unique ID for this accordion instance
    $unique_id = 'accordion-' . uniqid();
    
    // Build accordion classes
    $accordion_classes = ['accordion'];
    if ($atts['flush']) {
        $accordion_classes[] = 'accordion-flush';
    }
    
    // Data attribute for always open
    $data_parent = $atts['always_open'] ? '' : 'data-bs-parent="#' . esc_attr($unique_id) . '"';
    
    // Start output buffering
    ob_start();
    ?>
    
    <div class="woo-faq <?php echo esc_attr(implode(' ', $accordion_classes)); ?>" id="<?php echo esc_attr($unique_id); ?>">
        <?php foreach ($faq as $index => $item) : 
            $item_id = $unique_id . '-item-' . $index;
            $heading_id = $unique_id . '-heading-' . $index;
            $collapse_id = $unique_id . '-collapse-' . $index;
            $is_expanded = ($index === 0) ? 'true' : 'false';
            $show_class = ($index === 0) ? 'show' : '';
            $collapsed_class = ($index === 0) ? '' : 'collapsed';
        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="<?php echo esc_attr($heading_id); ?>">
                    <button 
                        class="accordion-button collapsed<?php //echo $collapsed_class; ?>" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#<?php echo esc_attr($collapse_id); ?>" 
                        aria-expanded="false <?php //echo $is_expanded; ?>" 
                        aria-controls="<?php echo esc_attr($collapse_id); ?>">
                        <?php echo esc_html($item['question']); ?>
                    </button>
                </h2>
                <div 
                    id="<?php echo esc_attr($collapse_id); ?>" 
                    class="accordion-collapse collapse <?php //echo $show_class; ?>" 
                    aria-labelledby="<?php echo esc_attr($heading_id); ?>" 
                    <?php echo $data_parent; ?>>
                    <div class="accordion-body">
                        <?php echo wp_kses_post($item['answer']); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode('faq_accordion', 'faq_accordion_shortcode');
