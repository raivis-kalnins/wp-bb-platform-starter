<?php
/**
 * Single Product tabs Custom Template Owerride
 * @version 9.8.0
 */
global $product;
$heading_d = apply_filters( 'woocommerce_product_description_heading', __( 'Product Details', 'woocommerce' ) );
$heading_m = apply_filters( 'woocommerce_product_description_heading', __( 'Product Specifications', 'woocommerce' ) );
$home_url = get_home_url();
$fields = function_exists( 'get_fields' ) ? (array) get_fields() : array();
$files = $fields["product_files"] ?? '';
$extra_variations = $fields["extra_variations"] ?? ''; //var_dump($extra_variations);
?>
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<div class="woocommerce-tabs wc-tabs-wrapper container">
	<div class="row">
		<div class="col col-12 col-lg-6">
			<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
			<h2><?php echo esc_html( $heading_m ); ?></h2>
			<div class="product_meta">
				<?php
				$category_ids   = $product->get_category_ids();
				$tag_ids        = $product->get_tag_ids();
				$category_label = _n( 'Category:', 'Categories:', count( $category_ids ), 'woocommerce' );
				$tag_label      = _n( 'Tag:', 'Tags:', count( $tag_ids ), 'woocommerce' );
				$category_list  = wc_get_product_category_list( $product->get_id(), ', ' );
				$tag_list       = wc_get_product_tag_list( $product->get_id(), ', ' );
				?>
				<?php do_action( 'woocommerce_product_additional_information', $product ); ?>
				<?php do_action( 'woocommerce_product_meta_start' ); ?>
					<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>
						<div class="sku_wrapper iws-meta-row"><span class="iws-meta-label"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?></span><span class="iws-meta-value sku"><?php echo esc_html( ( $sku = $product->get_sku() ) ? $sku : __( 'N/A', 'woocommerce' ) ); ?></span></div>
					<?php endif; ?>
					<?php if ( ! empty( $extra_variations ) ) : ?>
						<table class="woocommerce-product-attributes shop_attributes">
							<?php foreach ( $extra_variations as $variation ) : $caption = $variation['caption'] ?? ''; $text = $variation['text'] ?? ''; ?>
								<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--attribute_material"><th class="woocommerce-product-attributes-item__label" scope="row"><?php echo esc_html( $caption ); ?></th><td class="woocommerce-product-attributes-item__value"><p><?php echo wp_kses_post( $text ); ?></p></td></tr>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
					<?php if ( $category_list ) : ?>
						<div class="posted_in iws-meta-row"><span class="iws-meta-label"><?php echo esc_html( $category_label ); ?></span><span class="iws-meta-value"><?php echo wp_kses_post( $category_list ); ?></span></div>
					<?php endif; ?>
					<?php if ( $tag_list ) : ?>
						<div class="tagged_as iws-meta-row"><span class="iws-meta-label"><?php echo esc_html( $tag_label ); ?></span><span class="iws-meta-value"><?php echo wp_kses_post( $tag_list ); ?></span></div>
					<?php endif; ?>
				<?php do_action( 'woocommerce_product_meta_end' ); ?>
			</div>
			<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
		</div>
		<div class="col col-12 col-lg-6">
			<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
			<?php /* =do_shortcode('[pi_shipping_calculator]') */ ?>
			<?php if ( !empty($files) ) : ?>
				<h2>Downloads</h2>
				<ul class="prod-files">
					<?php foreach( $files as $file ) : $f = $file["file"]['url'] ?? '';	$fname = $file["file"]["title"] ?? ''; ?>
						<li class="prod-files_file"><a href="<?php echo esc_url( $f ); ?>" target="_blank" rel="noopener noreferrer"><span class="prod-files_file-type" aria-hidden="true">PDF</span><br /><span><?php echo esc_html( $fname ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
			
		</div>
		<div class="col col-12">
			<hr />
			<h2><?php echo esc_html( $heading_d ); ?></h2>
			<?php the_content(); ?>
			<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
		</div>		
	</div>
	<?php echo do_shortcode('[tabbed_information][faq_accordion]'); ?>
</div>
