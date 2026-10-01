<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP Admin customizations for WooCommerce.
 */

if ( ! function_exists( 'iws_admin_single_product_default_custom_fields' ) ) {
	function iws_admin_single_product_default_custom_fields() {
		return array();
	}
}

if ( ! function_exists( 'iws_get_admin_single_product_custom_fields' ) ) {
	function iws_get_admin_single_product_custom_fields( $product_id ) {
		$fields = get_post_meta( $product_id, '_iws_single_product_custom_fields', true );

		if ( is_array( $fields ) ) {
			$clean = array();
			foreach ( $fields as $field ) {
				$label       = isset( $field['label'] ) ? trim( wp_strip_all_tags( (string) $field['label'] ) ) : '';
				$placeholder = isset( $field['placeholder'] ) ? trim( wp_strip_all_tags( (string) $field['placeholder'] ) ) : '';
				if ( '' === $label ) {
					continue;
				}
				$clean[] = array(
					'label'       => $label,
					'placeholder' => $placeholder,
				);
			}
			if ( ! empty( $clean ) ) {
				return $clean;
			}
		}

		$legacy = get_post_meta( $product_id, '_iws_single_product_custom_text_fields', true );
		if ( ! empty( $legacy ) ) {
			$labels = preg_split( '/\r\n|\r|\n/', (string) $legacy );
			$clean  = array();
			foreach ( $labels as $index => $label ) {
				$label = trim( wp_strip_all_tags( $label ) );
				if ( '' === $label ) {
					continue;
				}
				$clean[] = array(
					'label'       => $label,
					'placeholder' => 0 === $index ? 'Minimum length 1400mm' : '',
				);
			}
			if ( ! empty( $clean ) ) {
				return $clean;
			}
		}

		return array();
	}
}

add_action( 'woocommerce_product_options_general_product_data', function() {
	global $post;

	$product_id = $post instanceof WP_Post ? $post->ID : 0;
	$fields     = iws_get_admin_single_product_custom_fields( $product_id );
	$max_rows   = 40;

	woocommerce_wp_checkbox(
		array(
			'id'          => '_iws_disable_single_add_to_cart',
			'label'       => __( 'Turn off add to cart button', 'wp-theme-woo-support' ),
			'description' => __( 'Hide/disable the normal WooCommerce Add to cart button for this product. Works for simple and variable products. Quote button can still be used if enabled.', 'wp-theme-woo-support' ),
			'desc_tip'    => true,
		)
	);

	echo '<div class="options_group iws-single-product-custom-fields-admin" data-iws-max-fields="' . esc_attr( $max_rows ) . '">';
	echo '<p class="form-field"><strong>' . esc_html__( 'Single product custom text fields', 'wp-theme-woo-support' ) . '</strong><br><span class="description">' . esc_html__( 'Optional frontend text fields for this product only. Use + Add field when more fields are needed. Maximum 40 fields.', 'wp-theme-woo-support' ) . '</span></p>';
	echo '<div class="iws-single-product-custom-fields-list">';

	foreach ( array_values( $fields ) as $i => $field ) {
		$label       = isset( $field['label'] ) ? $field['label'] : '';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		echo '<p class="form-field iws-single-product-custom-field-row">';
		echo '<label>' . esc_html( sprintf( __( 'Custom field %d', 'wp-theme-woo-support' ), $i + 1 ) ) . '</label>';
		echo '<span style="display:inline-flex;gap:8px;align-items:center;max-width:760px;width:100%;">';
		echo '<input type="text" style="width:45%;" class="iws-custom-field-label" name="iws_single_product_custom_fields[' . esc_attr( $i ) . '][label]" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Label text', 'wp-theme-woo-support' ) . '" />';
		echo '<input type="text" style="width:45%;" class="iws-custom-field-placeholder" name="iws_single_product_custom_fields[' . esc_attr( $i ) . '][placeholder]" value="' . esc_attr( $placeholder ) . '" placeholder="' . esc_attr__( 'Placeholder text', 'wp-theme-woo-support' ) . '" />';
		echo '<button type="button" class="button iws-remove-custom-field" aria-label="' . esc_attr__( 'Remove custom field', 'wp-theme-woo-support' ) . '">&times;</button>';
		echo '</span>';
		echo '</p>';
	}

	echo '</div>';
	echo '<p class="form-field"><label></label><button type="button" class="button button-secondary iws-add-custom-field">' . esc_html__( '+ Add field', 'wp-theme-woo-support' ) . '</button> <span class="description iws-custom-fields-count"></span></p>';
	echo '</div>';

	?>
	<script>
	(function(){
		function ready(fn){
			if(document.readyState !== 'loading'){ fn(); return; }
			document.addEventListener('DOMContentLoaded', fn);
		}
		ready(function(){
			var box = document.querySelector('.iws-single-product-custom-fields-admin');
			if(!box || box.dataset.bound === '1'){ return; }
			box.dataset.bound = '1';
			var list = box.querySelector('.iws-single-product-custom-fields-list');
			var add = box.querySelector('.iws-add-custom-field');
			var count = box.querySelector('.iws-custom-fields-count');
			var max = parseInt(box.getAttribute('data-iws-max-fields') || '40', 10);

			function renumber(){
				var rows = Array.prototype.slice.call(list.querySelectorAll('.iws-single-product-custom-field-row'));
				rows.forEach(function(row, index){
					var rowLabel = row.querySelector('label');
					var labelInput = row.querySelector('.iws-custom-field-label');
					var placeholderInput = row.querySelector('.iws-custom-field-placeholder');
					if(rowLabel){ rowLabel.textContent = '<?php echo esc_js( __( 'Custom field', 'wp-theme-woo-support' ) ); ?> ' + (index + 1); }
					if(labelInput){ labelInput.name = 'iws_single_product_custom_fields[' + index + '][label]'; }
					if(placeholderInput){ placeholderInput.name = 'iws_single_product_custom_fields[' + index + '][placeholder]'; }
				});
				if(count){ count.textContent = rows.length + ' / ' + max; }
				if(add){ add.disabled = rows.length >= max; }
			}

			function createRow(){
				var current = list.querySelectorAll('.iws-single-product-custom-field-row').length;
				if(current >= max){ return; }
				var row = document.createElement('p');
				row.className = 'form-field iws-single-product-custom-field-row';
				row.innerHTML = '<label></label><span style="display:inline-flex;gap:8px;align-items:center;max-width:760px;width:100%;"><input type="text" style="width:45%;" class="iws-custom-field-label" placeholder="<?php echo esc_js( __( 'Label text', 'wp-theme-woo-support' ) ); ?>" /><input type="text" style="width:45%;" class="iws-custom-field-placeholder" placeholder="<?php echo esc_js( __( 'Placeholder text', 'wp-theme-woo-support' ) ); ?>" /><button type="button" class="button iws-remove-custom-field" aria-label="<?php echo esc_js( __( 'Remove custom field', 'wp-theme-woo-support' ) ); ?>">&times;</button></span>';
				list.appendChild(row);
				renumber();
			}

			if(add){
				add.addEventListener('click', function(e){
					e.preventDefault();
					createRow();
				});
			}

			box.addEventListener('click', function(e){
				var remove = e.target.closest('.iws-remove-custom-field');
				if(!remove){ return; }
				e.preventDefault();
				var row = remove.closest('.iws-single-product-custom-field-row');
				if(!row){ return; }
				row.remove();
				renumber();
			});

			renumber();
		});
	})();
	</script>
	<?php
} );

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
	update_post_meta( $post_id, '_iws_disable_single_add_to_cart', isset( $_POST['_iws_disable_single_add_to_cart'] ) ? 'yes' : 'no' );

	$raw   = isset( $_POST['iws_single_product_custom_fields'] ) && is_array( $_POST['iws_single_product_custom_fields'] ) ? wp_unslash( $_POST['iws_single_product_custom_fields'] ) : array();
	$clean = array();

	foreach ( $raw as $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}

		$label       = isset( $field['label'] ) ? trim( wp_strip_all_tags( (string) $field['label'] ) ) : '';
		$placeholder = isset( $field['placeholder'] ) ? trim( wp_strip_all_tags( (string) $field['placeholder'] ) ) : '';

		if ( '' === $label ) {
			continue;
		}

		$clean[] = array(
			'label'       => $label,
			'placeholder' => $placeholder,
		);
	}

	if ( empty( $clean ) ) {
		delete_post_meta( $post_id, '_iws_single_product_custom_fields' );
		delete_post_meta( $post_id, '_iws_single_product_custom_text_fields' );
		return;
	}

	update_post_meta( $post_id, '_iws_single_product_custom_fields', $clean );
	delete_post_meta( $post_id, '_iws_single_product_custom_text_fields' );
} );
