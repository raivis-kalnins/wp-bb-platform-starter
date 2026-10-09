<?php
/**
 * Product Sync: native WooCommerce import/export, scheduled supplier feeds and
 * WooCommerce-to-WooCommerce catalogue synchronisation.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'wpbb_sync_admin_menu', 76 );
add_action( 'admin_post_wpbb_sync_save_profile', 'wpbb_sync_save_profile' );
add_action( 'admin_post_wpbb_sync_run_profile', 'wpbb_sync_run_profile_action' );
add_action( 'admin_post_wpbb_sync_delete_profile', 'wpbb_sync_delete_profile' );
add_action( 'admin_post_wpbb_sync_create_test_profile', 'wpbb_sync_create_test_profile' );
add_action( 'admin_post_wpbb_sync_export', 'wpbb_sync_export_action' );
add_action( 'admin_post_wpbb_sync_upload_import', 'wpbb_sync_upload_import_action' );
add_action( 'wpbb_woo_sync_tick', 'wpbb_sync_cron_tick' );
add_action( 'init', 'wpbb_sync_ensure_cron' );
add_action( 'rest_api_init', 'wpbb_sync_register_rest' );

function wpbb_sync_profiles() {
    $profiles = get_option( 'wpbb_woo_sync_profiles', array() );
    return is_array( $profiles ) ? $profiles : array();
}
function wpbb_sync_save_profiles( $profiles ) { update_option( 'wpbb_woo_sync_profiles', array_values( $profiles ), false ); }
function wpbb_sync_find_profile( $id ) {
    foreach ( wpbb_sync_profiles() as $profile ) { if ( isset( $profile['id'] ) && hash_equals( (string) $profile['id'], (string) $id ) ) return $profile; }
    return null;
}
function wpbb_sync_default_mapping( $type = 'json' ) {
    if ( 'woo_rest' === $type ) return array( 'sku'=>'sku','name'=>'name','regular_price'=>'regular_price','sale_price'=>'sale_price','stock_quantity'=>'stock_quantity','stock_status'=>'stock_status','description'=>'description','short_description'=>'short_description','image'=>'images.0.src','categories'=>'categories','weight'=>'weight','external_id'=>'id' );
    return array( 'sku'=>'sku','name'=>'title','regular_price'=>'price','sale_price'=>'','stock_quantity'=>'stock','stock_status'=>'availabilityStatus','description'=>'description','short_description'=>'description','image'=>'thumbnail','categories'=>'category','brand'=>'brand','weight'=>'weight','external_id'=>'id' );
}
function wpbb_sync_native_mapping() {
    return array( 'sku'=>'sku','name'=>'name','regular_price'=>'regular_price','sale_price'=>'sale_price','stock_quantity'=>'stock_quantity','stock_status'=>'stock_status','description'=>'description','short_description'=>'short_description','image'=>'image','categories'=>'categories','brand'=>'brand','weight'=>'weight','external_id'=>'id' );
}
function wpbb_sync_admin_menu() {
    add_submenu_page( 'woocommerce', __( 'Product Sync', 'wp-theme-woo-support' ), __( 'Product Sync', 'wp-theme-woo-support' ), 'manage_woocommerce', 'wpbb-product-sync', 'wpbb_sync_render_page' );
}
function wpbb_sync_ensure_cron() {
    if ( ! wp_next_scheduled( 'wpbb_woo_sync_tick' ) ) wp_schedule_event( time() + 300, 'hourly', 'wpbb_woo_sync_tick' );
}
function wpbb_sync_register_rest() {
    register_rest_route( 'wpbb-woo/v1', '/sync/(?P<id>[a-zA-Z0-9_-]+)', array(
        'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'wpbb_sync_rest_run',
    ) );
}
function wpbb_sync_rest_run( WP_REST_Request $request ) {
    $profile = wpbb_sync_find_profile( $request['id'] );
    if ( ! $profile ) return new WP_Error( 'wpbb_sync_missing', 'Profile not found', array( 'status'=>404 ) );
    $token = (string) $request->get_param( 'key' );
    if ( empty( $profile['token'] ) || ! hash_equals( (string) $profile['token'], $token ) ) return new WP_Error( 'wpbb_sync_denied', 'Invalid sync key', array( 'status'=>403 ) );
    return rest_ensure_response( wpbb_sync_run_profile( $profile ) );
}
function wpbb_sync_interval_seconds( $schedule ) {
    return array( 'hourly'=>HOUR_IN_SECONDS, 'twicedaily'=>12*HOUR_IN_SECONDS, 'daily'=>DAY_IN_SECONDS )[ $schedule ] ?? 0;
}
function wpbb_sync_cron_tick() {
    $profiles = wpbb_sync_profiles(); $changed = false;
    foreach ( $profiles as &$profile ) {
        $interval = wpbb_sync_interval_seconds( $profile['schedule'] ?? 'manual' );
        if ( ! $interval ) continue;
        $last = absint( $profile['last_run'] ?? 0 );
        if ( ! $last || time() - $last >= $interval ) { wpbb_sync_run_profile( $profile ); $profile['last_run'] = time(); $changed = true; }
    }
    if ( $changed ) wpbb_sync_save_profiles( $profiles );
}
function wpbb_sync_get_path( $data, $path ) {
    $path = trim( (string) $path ); if ( '' === $path ) return '';
    $current = $data;
    foreach ( explode( '.', $path ) as $part ) {
        if ( is_array( $current ) && array_key_exists( $part, $current ) ) { $current = $current[ $part ]; continue; }
        if ( is_array( $current ) && ctype_digit( (string) $part ) && array_key_exists( (int) $part, $current ) ) { $current = $current[ (int) $part ]; continue; }
        return '';
    }
    return $current;
}
function wpbb_sync_fetch_url( $url, $profile ) {
    $args = array( 'timeout'=>25, 'redirection'=>5, 'headers'=>array( 'Accept'=>'application/json, application/xml, text/xml, text/csv, */*' ) );
    if ( ! empty( $profile['consumer_key'] ) && ! empty( $profile['consumer_secret'] ) ) $args['headers']['Authorization'] = 'Basic ' . base64_encode( $profile['consumer_key'] . ':' . $profile['consumer_secret'] );
    $response = wp_remote_get( esc_url_raw( $url ), $args );
    if ( is_wp_error( $response ) ) return $response;
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) return new WP_Error( 'wpbb_sync_http', 'Remote feed returned HTTP ' . $code );
    return wp_remote_retrieve_body( $response );
}
function wpbb_sync_decode_items( $body, $profile ) {
    $type = $profile['source_type'] ?? 'json';
    if ( 'csv' === $type ) {
        $lines = preg_split( '/\r\n|\r|\n/', trim( $body ) ); if ( count( $lines ) < 2 ) return array();
        $headers = str_getcsv( array_shift( $lines ) ); $items = array();
        foreach ( $lines as $line ) { if ( trim( $line ) === '' ) continue; $row = str_getcsv( $line ); $items[] = array_combine( $headers, array_slice( array_pad( $row, count( $headers ), '' ), 0, count( $headers ) ) ); }
        return $items;
    }
    if ( 'xml' === $type ) {
        if ( ! function_exists( 'simplexml_load_string' ) ) return new WP_Error( 'wpbb_sync_xml_missing', 'SimpleXML is not available on this server.' );
        libxml_use_internal_errors( true ); $xml = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );
        if ( ! $xml ) return new WP_Error( 'wpbb_sync_xml', 'Invalid XML feed.' );
        $path = trim( (string) ( $profile['item_path'] ?? '' ) );
        $nodes = $path ? $xml->xpath( $path ) : $xml->children(); $items = array();
        foreach ( (array) $nodes as $node ) $items[] = json_decode( wp_json_encode( $node ), true );
        return $items;
    }
    $decoded = json_decode( $body, true );
    if ( ! is_array( $decoded ) ) return new WP_Error( 'wpbb_sync_json', 'Invalid JSON feed.' );
    $path = trim( (string) ( $profile['item_path'] ?? '' ) );
    $items = $path ? wpbb_sync_get_path( $decoded, $path ) : $decoded;
    if ( isset( $items['id'] ) || isset( $items['sku'] ) ) $items = array( $items );
    return is_array( $items ) ? array_values( $items ) : array();
}
function wpbb_sync_fetch_items( $profile ) {
    $type = $profile['source_type'] ?? 'json'; $url = trim( (string) ( $profile['source_url'] ?? '' ) );
    if ( '' === $url ) return new WP_Error( 'wpbb_sync_url', 'Source URL is empty.' );
    if ( 'woo_rest' === $type ) {
        $all = array();
        for ( $page=1; $page<=max( 1, min( 10, absint( $profile['max_pages'] ?? 5 ) ) ); $page++ ) {
            $page_url = add_query_arg( array( 'per_page'=>100, 'page'=>$page ), $url ); $body = wpbb_sync_fetch_url( $page_url, $profile ); if ( is_wp_error( $body ) ) return $body;
            $batch = json_decode( $body, true ); if ( ! is_array( $batch ) ) return new WP_Error( 'wpbb_sync_json', 'Invalid WooCommerce REST response.' );
            $all = array_merge( $all, $batch ); if ( count( $batch ) < 100 ) break;
        }
        return $all;
    }
    $body = wpbb_sync_fetch_url( $url, $profile ); if ( is_wp_error( $body ) ) return $body;
    return wpbb_sync_decode_items( $body, $profile );
}
function wpbb_sync_normalize_categories( $value ) {
    if ( is_array( $value ) ) {
        $out = array(); foreach ( $value as $v ) { if ( is_array( $v ) ) $v = $v['name'] ?? $v['slug'] ?? ''; if ( $v !== '' ) $out[] = sanitize_text_field( (string) $v ); } return $out;
    }
    return array_values( array_filter( array_map( 'trim', preg_split( '/[,|>]+/', (string) $value ) ) ) );
}
function wpbb_sync_map_item( $item, $profile ) {
    $mapping = $profile['mapping'] ?? array(); if ( ! is_array( $mapping ) ) $mapping = wpbb_sync_default_mapping( $profile['source_type'] ?? 'json' );
    $out = array(); foreach ( $mapping as $target=>$path ) $out[ $target ] = $path !== '' ? wpbb_sync_get_path( $item, $path ) : '';
    return $out;
}
function wpbb_sync_find_product_id( $sku, $external_id, $profile_id ) {
    if ( $sku && function_exists( 'wc_get_product_id_by_sku' ) ) { $id = wc_get_product_id_by_sku( $sku ); if ( $id ) return $id; }
    if ( $external_id !== '' ) {
        $ids = get_posts( array( 'post_type'=>'product','post_status'=>'any','fields'=>'ids','posts_per_page'=>1,'meta_query'=>array(
            array( 'key'=>'_wpbb_sync_profile','value'=>$profile_id ), array( 'key'=>'_wpbb_sync_external_id','value'=>(string)$external_id ),
        ) ) ); if ( $ids ) return (int) $ids[0];
    }
    return 0;
}
function wpbb_sync_sideload_image( $url, $product_id, $name ) {
    $url = esc_url_raw( (string) $url ); if ( ! $url ) return 0;
    $hash = md5( $url ); if ( get_post_meta( $product_id, '_wpbb_sync_image_hash', true ) === $hash && get_post_thumbnail_id( $product_id ) ) return get_post_thumbnail_id( $product_id );
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $id = media_sideload_image( $url, $product_id, $name, 'id' ); if ( is_wp_error( $id ) ) return 0;
    set_post_thumbnail( $product_id, (int) $id ); update_post_meta( $product_id, '_wpbb_sync_image_hash', $hash ); return (int) $id;
}
function wpbb_sync_upsert_product( $row, $profile ) {
    $sku = sanitize_text_field( (string) ( $row['sku'] ?? '' ) ); $external = sanitize_text_field( (string) ( $row['external_id'] ?? '' ) );
    if ( ! $sku && ! $external ) return array( 'status'=>'skipped','message'=>'Missing SKU / external id' );
    $id = wpbb_sync_find_product_id( $sku, $external, $profile['id'] ); $existing = $id ? wc_get_product( $id ) : null;
    if ( ! $existing && empty( $profile['create_missing'] ) ) return array( 'status'=>'skipped','message'=>'Create missing disabled' );
    if ( $existing && empty( $profile['update_existing'] ) ) return array( 'status'=>'skipped','message'=>'Update existing disabled' );
    $product = $existing instanceof WC_Product ? $existing : new WC_Product_Simple();
    $stock_only = ! empty( $profile['stock_only'] );
    if ( ! $stock_only ) {
        if ( ! empty( $row['name'] ) ) $product->set_name( wp_strip_all_tags( (string) $row['name'] ) );
        if ( $sku ) $product->set_sku( $sku );
        if ( isset( $row['description'] ) && $row['description'] !== '' ) $product->set_description( wp_kses_post( (string) $row['description'] ) );
        if ( isset( $row['short_description'] ) && $row['short_description'] !== '' ) $product->set_short_description( wp_kses_post( (string) $row['short_description'] ) );
        if ( isset( $row['weight'] ) && is_numeric( $row['weight'] ) ) $product->set_weight( wc_format_decimal( $row['weight'] ) );
    }
    if ( isset( $row['regular_price'] ) && $row['regular_price'] !== '' && is_numeric( $row['regular_price'] ) ) $product->set_regular_price( wc_format_decimal( $row['regular_price'] ) );
    if ( isset( $row['sale_price'] ) && $row['sale_price'] !== '' && is_numeric( $row['sale_price'] ) ) $product->set_sale_price( wc_format_decimal( $row['sale_price'] ) );
    if ( isset( $row['stock_quantity'] ) && $row['stock_quantity'] !== '' && is_numeric( $row['stock_quantity'] ) ) { $product->set_manage_stock( true ); $product->set_stock_quantity( max( 0, (int) $row['stock_quantity'] ) ); }
    $stock_status = strtolower( (string) ( $row['stock_status'] ?? '' ) ); if ( $stock_status ) $product->set_stock_status( false !== strpos( $stock_status, 'out' ) ? 'outofstock' : 'instock' );
    $product->set_status( in_array( $profile['status'] ?? 'publish', array( 'publish','draft','private' ), true ) ? $profile['status'] : 'publish' );
    $id = $product->save(); if ( ! $id ) return array( 'status'=>'error','message'=>'WooCommerce save failed' );
    update_post_meta( $id, '_wpbb_sync_profile', sanitize_key( $profile['id'] ) ); update_post_meta( $id, '_wpbb_sync_external_id', $external ); update_post_meta( $id, '_wpbb_sync_last_seen', time() );
    if ( ! $stock_only ) {
        $categories = wpbb_sync_normalize_categories( $row['categories'] ?? '' ); if ( $categories ) wp_set_object_terms( $id, $categories, 'product_cat', false );
        if ( ! empty( $row['brand'] ) ) { foreach ( array( 'product_brand','pa_brand' ) as $tax ) if ( taxonomy_exists( $tax ) ) { wp_set_object_terms( $id, sanitize_text_field( (string) $row['brand'] ), $tax, false ); break; } }
        if ( ! empty( $row['image'] ) && ! empty( $profile['sideload_images'] ) ) wpbb_sync_sideload_image( $row['image'], $id, $product->get_name() );
    }
    return array( 'status'=>$existing ? 'updated':'created', 'id'=>$id );
}
function wpbb_sync_log( $profile_id, $summary ) {
    $logs = get_option( 'wpbb_woo_sync_logs', array() ); if ( ! is_array( $logs ) ) $logs = array();
    array_unshift( $logs, array( 'time'=>time(), 'profile'=>$profile_id, 'summary'=>$summary ) ); update_option( 'wpbb_woo_sync_logs', array_slice( $logs, 0, 80 ), false );
}
function wpbb_sync_process_items( $items, $profile, $started = null ) {
    $started = $started ?: microtime( true );
    if ( is_wp_error( $items ) ) { $summary = array( 'ok'=>false,'error'=>$items->get_error_message() ); wpbb_sync_log( $profile['id'] ?? 'manual-upload', $summary ); return $summary; }
    $limit = max( 1, min( 1000, absint( $profile['batch_limit'] ?? 250 ) ) ); $counts = array( 'created'=>0,'updated'=>0,'skipped'=>0,'error'=>0 ); $examples = array();
    foreach ( array_slice( (array) $items, 0, $limit ) as $item ) {
        if ( ! is_array( $item ) ) continue; $row = wpbb_sync_map_item( $item, $profile ); $result = wpbb_sync_upsert_product( $row, $profile ); $status = $result['status'] ?? 'error'; if ( isset( $counts[$status] ) ) $counts[$status]++; if ( count( $examples ) < 10 ) $examples[] = array( 'name'=>$row['name'] ?? '', 'sku'=>$row['sku'] ?? '', 'status'=>$status );
    }
    $summary = array( 'ok'=>true,'fetched'=>count( (array) $items ),'processed'=>array_sum( $counts ),'counts'=>$counts,'seconds'=>round( microtime(true)-$started, 2 ),'examples'=>$examples ); wpbb_sync_log( $profile['id'] ?? 'manual-upload', $summary ); return $summary;
}
function wpbb_sync_run_profile( $profile ) {
    $started = microtime( true ); $items = wpbb_sync_fetch_items( $profile );
    return wpbb_sync_process_items( $items, $profile, $started );
}
function wpbb_sync_require_admin() { if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( esc_html__( 'Permission denied.', 'wp-theme-woo-support' ) ); }
function wpbb_sync_save_profile() {
    wpbb_sync_require_admin(); check_admin_referer( 'wpbb_sync_save_profile' ); $p = wp_unslash( $_POST ); $profiles = wpbb_sync_profiles(); $id = sanitize_key( $p['id'] ?? '' ); if ( ! $id ) $id = 'sync-' . wp_generate_password( 8, false, false );
    $type = in_array( $p['source_type'] ?? '', array( 'json','csv','xml','woo_rest' ), true ) ? $p['source_type'] : 'json';
    $mapping = array(); foreach ( array_keys( wpbb_sync_default_mapping( $type ) ) as $field ) $mapping[$field] = sanitize_text_field( $p['map_'.$field] ?? '' );
    $profile = array( 'id'=>$id,'name'=>sanitize_text_field($p['name']??'Product feed'),'source_type'=>$type,'source_url'=>esc_url_raw($p['source_url']??''),'item_path'=>sanitize_text_field($p['item_path']??''),'consumer_key'=>sanitize_text_field($p['consumer_key']??''),'consumer_secret'=>sanitize_text_field($p['consumer_secret']??''),'schedule'=>in_array($p['schedule']??'',array('manual','hourly','twicedaily','daily'),true)?$p['schedule']:'manual','mapping'=>$mapping,'create_missing'=>!empty($p['create_missing']),'update_existing'=>!empty($p['update_existing']),'stock_only'=>!empty($p['stock_only']),'sideload_images'=>!empty($p['sideload_images']),'status'=>in_array($p['status']??'',array('publish','draft','private'),true)?$p['status']:'publish','batch_limit'=>max(1,min(1000,absint($p['batch_limit']??250))),'max_pages'=>max(1,min(10,absint($p['max_pages']??5))),'token'=>sanitize_text_field($p['token']??wp_generate_password(32,false,false)) );
    $found=false; foreach($profiles as $i=>$existing){ if(($existing['id']??'')===$id){ $profile['last_run']=$existing['last_run']??0; $profiles[$i]=$profile; $found=true; break; } } if(!$found)$profiles[]=$profile; wpbb_sync_save_profiles($profiles); wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync&saved=1')); exit;
}
function wpbb_sync_run_profile_action(){ wpbb_sync_require_admin(); $id=sanitize_key($_GET['id']??''); check_admin_referer('wpbb_sync_run_'.$id); $profile=wpbb_sync_find_profile($id); if(!$profile)wp_die('Profile not found.'); $result=wpbb_sync_run_profile($profile); $profiles=wpbb_sync_profiles(); foreach($profiles as &$p)if(($p['id']??'')===$id)$p['last_run']=time(); wpbb_sync_save_profiles($profiles); set_transient('wpbb_sync_flash_'.get_current_user_id(),$result,60); wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync')); exit; }
function wpbb_sync_delete_profile(){ wpbb_sync_require_admin(); $id=sanitize_key($_GET['id']??''); check_admin_referer('wpbb_sync_delete_'.$id); wpbb_sync_save_profiles(array_values(array_filter(wpbb_sync_profiles(),fn($p)=>(string)($p['id']??'')!==$id))); wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync&deleted=1')); exit; }
function wpbb_sync_create_test_profile(){ wpbb_sync_require_admin(); check_admin_referer('wpbb_sync_create_test_profile'); $profiles=wpbb_sync_profiles(); foreach($profiles as $p)if(($p['id']??'')==='dummyjson-demo'){wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync&test_exists=1'));exit;} $profiles[]=array('id'=>'dummyjson-demo','name'=>'Live JSON demo — DummyJSON','source_type'=>'json','source_url'=>'https://dummyjson.com/products?limit=20','item_path'=>'products','schedule'=>'manual','mapping'=>wpbb_sync_default_mapping('json'),'create_missing'=>true,'update_existing'=>true,'stock_only'=>false,'sideload_images'=>true,'status'=>'draft','batch_limit'=>20,'max_pages'=>1,'token'=>wp_generate_password(32,false,false),'last_run'=>0); wpbb_sync_save_profiles($profiles); wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync&test_created=1')); exit; }
function wpbb_sync_upload_import_action(){
    wpbb_sync_require_admin(); check_admin_referer('wpbb_sync_upload_import');
    if(empty($_FILES['catalogue_file']['tmp_name'])||!is_uploaded_file($_FILES['catalogue_file']['tmp_name'])) wp_die('No import file received.');
    $name=sanitize_file_name($_FILES['catalogue_file']['name']??''); $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(!in_array($ext,array('csv','json','xml'),true)) wp_die('Only CSV, JSON and XML files are supported.');
    if((int)($_FILES['catalogue_file']['size']??0)>20*MB_IN_BYTES) wp_die('Import file is larger than 20 MB.');
    $body=file_get_contents($_FILES['catalogue_file']['tmp_name']); if($body===false) wp_die('Could not read the import file.');
    $type=$ext; $profile=array('id'=>'manual-upload','name'=>'Manual upload','source_type'=>$type,'item_path'=>$type==='json'?'products':($type==='xml'?'//product':''),'mapping'=>wpbb_sync_native_mapping(),'create_missing'=>!empty($_POST['create_missing']),'update_existing'=>!empty($_POST['update_existing']),'stock_only'=>!empty($_POST['stock_only']),'sideload_images'=>!empty($_POST['sideload_images']),'status'=>in_array($_POST['status']??'',array('publish','draft','private'),true)?$_POST['status']:'draft','batch_limit'=>max(1,min(1000,absint($_POST['batch_limit']??500))));
    $items=wpbb_sync_decode_items($body,$profile); $result=wpbb_sync_process_items($items,$profile,microtime(true)); set_transient('wpbb_sync_flash_'.get_current_user_id(),$result,120); wp_safe_redirect(admin_url('admin.php?page=wpbb-product-sync&uploaded=1')); exit;
}
function wpbb_sync_export_action(){ wpbb_sync_require_admin(); check_admin_referer('wpbb_sync_export'); $format=in_array($_GET['format']??'',array('csv','json','xml'),true)?$_GET['format']:'csv'; $products=wc_get_products(array('limit'=>-1,'status'=>array('publish','draft','private'))); $rows=array(); foreach($products as $p)$rows[]=array('id'=>$p->get_id(),'sku'=>$p->get_sku(),'name'=>$p->get_name(),'type'=>$p->get_type(),'regular_price'=>$p->get_regular_price(),'sale_price'=>$p->get_sale_price(),'stock_quantity'=>$p->get_stock_quantity(),'stock_status'=>$p->get_stock_status(),'categories'=>implode('|',wp_get_post_terms($p->get_id(),'product_cat',array('fields'=>'names'))),'image'=>wp_get_attachment_url($p->get_image_id())); nocache_headers(); header('Content-Disposition: attachment; filename=wpbb-products-'.gmdate('Ymd-His').'.'.$format); if($format==='json'){header('Content-Type: application/json; charset=utf-8');echo wp_json_encode(array('products'=>$rows),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);exit;} if($format==='xml'){header('Content-Type: application/xml; charset=utf-8');$xml=new SimpleXMLElement('<products/>');foreach($rows as $row){$node=$xml->addChild('product');foreach($row as $k=>$v)$node->addChild($k,htmlspecialchars((string)$v,ENT_XML1|ENT_COMPAT,'UTF-8'));}echo $xml->asXML();exit;} header('Content-Type: text/csv; charset=utf-8');$out=fopen('php://output','w');fputcsv($out,array_keys($rows[0]??array('id'=>'')));foreach($rows as $row)fputcsv($out,$row);fclose($out);exit; }
function wpbb_sync_render_page(){
    wpbb_sync_require_admin(); $profiles=wpbb_sync_profiles(); $edit_id=sanitize_key($_GET['edit']??''); $editing=$edit_id?wpbb_sync_find_profile($edit_id):null; $p=$editing?:array('id'=>'','name'=>'','source_type'=>'json','source_url'=>'','item_path'=>'products','consumer_key'=>'','consumer_secret'=>'','schedule'=>'manual','mapping'=>wpbb_sync_default_mapping('json'),'create_missing'=>true,'update_existing'=>true,'stock_only'=>false,'sideload_images'=>true,'status'=>'publish','batch_limit'=>250,'max_pages'=>5,'token'=>wp_generate_password(32,false,false)); $flash=get_transient('wpbb_sync_flash_'.get_current_user_id()); if($flash)delete_transient('wpbb_sync_flash_'.get_current_user_id()); $logs=get_option('wpbb_woo_sync_logs',array()); ?>
    <div class="wrap wpbb-sync-wrap"><h1><?php esc_html_e('WooCommerce Product Sync','wp-theme-woo-support'); ?></h1><p>Import and update products from JSON, XML, CSV or another WooCommerce REST API. Profiles can run manually, on WP-Cron, or from a secure external cron URL.</p>
    <?php if($flash):?><div class="notice notice-info"><p><strong>Last run:</strong> <?php echo esc_html(wp_json_encode($flash));?></p></div><?php endif;?>
    <style>.wpbb-sync-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(360px,.65fr);gap:20px;max-width:1500px}.wpbb-sync-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.04)}.wpbb-sync-card h2{margin-top:0}.wpbb-sync-table td,.wpbb-sync-table th{vertical-align:top}.wpbb-sync-map{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.wpbb-sync-map label{display:block;font-weight:600}.wpbb-sync-map input{width:100%}.wpbb-sync-actions{display:flex;gap:8px;flex-wrap:wrap}.wpbb-sync-badge{display:inline-block;padding:3px 8px;border-radius:999px;background:#eef6ff;color:#145ea8;font-size:12px;font-weight:700}@media(max-width:1000px){.wpbb-sync-grid{grid-template-columns:1fr}.wpbb-sync-map{grid-template-columns:1fr}}</style>
    <div class="wpbb-sync-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="wpbb_sync_create_test_profile"><?php wp_nonce_field('wpbb_sync_create_test_profile');?><button class="button button-primary">Add live JSON test feed</button></form><?php foreach(array('csv','json','xml') as $f):?><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbb_sync_export&format='.$f),'wpbb_sync_export'));?>">Export <?php echo esc_html(strtoupper($f));?></a><?php endforeach;?></div><br>
    <div class="wpbb-sync-card" style="max-width:1500px;margin-bottom:20px"><h2>One-off file import</h2><p>Upload a CSV, JSON or XML product file exported from another shop, warehouse or ERP. Native WP BB exports can be re-imported directly.</p><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="wpbb_sync_upload_import"><?php wp_nonce_field('wpbb_sync_upload_import');?><input type="file" name="catalogue_file" accept=".csv,.json,.xml" required> <label><input type="checkbox" name="create_missing" value="1" checked> Create</label> <label><input type="checkbox" name="update_existing" value="1" checked> Update</label> <label><input type="checkbox" name="sideload_images" value="1" checked> Download images</label> <label><input type="checkbox" name="stock_only" value="1"> Stock/price only</label> <label>Status <select name="status"><option value="draft">draft</option><option value="publish">publish</option><option value="private">private</option></select></label> <label>Limit <input type="number" name="batch_limit" value="500" min="1" max="1000" style="width:80px"></label> <button class="button button-secondary">Import file</button></form></div>
    <div class="wpbb-sync-grid"><div><div class="wpbb-sync-card"><h2>Sync profiles</h2><table class="widefat striped wpbb-sync-table"><thead><tr><th>Name</th><th>Source</th><th>Schedule</th><th>Last run</th><th>Actions</th></tr></thead><tbody><?php if(!$profiles):?><tr><td colspan="5">No profiles yet.</td></tr><?php endif; foreach($profiles as $profile):$id=$profile['id'];?><tr><td><strong><?php echo esc_html($profile['name']);?></strong><br><code><?php echo esc_html($id);?></code></td><td><span class="wpbb-sync-badge"><?php echo esc_html($profile['source_type']);?></span><br><small><?php echo esc_html($profile['source_url']);?></small></td><td><?php echo esc_html($profile['schedule']);?></td><td><?php echo !empty($profile['last_run'])?esc_html(wp_date('Y-m-d H:i',$profile['last_run'])):'—';?></td><td><div class="wpbb-sync-actions"><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbb_sync_run_profile&id='.$id),'wpbb_sync_run_'.$id));?>">Run now</a><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wpbb-product-sync&edit='.$id));?>">Edit</a><a class="button" onclick="return confirm('Delete profile?')" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbb_sync_delete_profile&id='.$id),'wpbb_sync_delete_'.$id));?>">Delete</a></div></td></tr><?php endforeach;?></tbody></table></div>
    <div class="wpbb-sync-card" style="margin-top:20px"><h2>Recent runs</h2><table class="widefat striped"><thead><tr><th>Time</th><th>Profile</th><th>Result</th></tr></thead><tbody><?php foreach(array_slice((array)$logs,0,15) as $log):?><tr><td><?php echo esc_html(wp_date('Y-m-d H:i:s',$log['time']));?></td><td><?php echo esc_html($log['profile']);?></td><td><code><?php echo esc_html(wp_json_encode($log['summary']));?></code></td></tr><?php endforeach;?></tbody></table></div></div>
    <div class="wpbb-sync-card"><h2><?php echo $editing?'Edit profile':'New profile';?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="wpbb_sync_save_profile"><input type="hidden" name="id" value="<?php echo esc_attr($p['id']);?>"><?php wp_nonce_field('wpbb_sync_save_profile');?><p><label><strong>Name</strong><br><input class="widefat" name="name" value="<?php echo esc_attr($p['name']);?>" required></label></p><p><label><strong>Source type</strong><br><select class="widefat" name="source_type"><?php foreach(array('json'=>'JSON feed','csv'=>'CSV feed','xml'=>'XML feed','woo_rest'=>'WooCommerce REST API') as $k=>$v):?><option value="<?php echo esc_attr($k);?>" <?php selected($p['source_type'],$k);?>><?php echo esc_html($v);?></option><?php endforeach;?></select></label></p><p><label><strong>Source URL</strong><br><input class="widefat" type="url" name="source_url" value="<?php echo esc_attr($p['source_url']);?>" required></label></p><p><label><strong>Items path / XML XPath</strong><br><input class="widefat" name="item_path" value="<?php echo esc_attr($p['item_path']);?>"><small>JSON example: products. XML example: //product</small></label></p><p><label>Woo REST consumer key<br><input class="widefat" name="consumer_key" value="<?php echo esc_attr($p['consumer_key']);?>"></label></p><p><label>Woo REST consumer secret<br><input class="widefat" type="password" name="consumer_secret" value="<?php echo esc_attr($p['consumer_secret']);?>"></label></p><p><label><strong>Schedule</strong><br><select class="widefat" name="schedule"><?php foreach(array('manual','hourly','twicedaily','daily') as $v):?><option <?php selected($p['schedule'],$v);?>><?php echo esc_html($v);?></option><?php endforeach;?></select></label></p><p><label>Batch limit <input type="number" name="batch_limit" min="1" max="1000" value="<?php echo esc_attr($p['batch_limit']);?>"></label> <label>REST max pages <input type="number" name="max_pages" min="1" max="10" value="<?php echo esc_attr($p['max_pages']);?>"></label></p><p><label><input type="checkbox" name="create_missing" value="1" <?php checked(!empty($p['create_missing']));?>> Create missing products</label><br><label><input type="checkbox" name="update_existing" value="1" <?php checked(!empty($p['update_existing']));?>> Update existing products</label><br><label><input type="checkbox" name="stock_only" value="1" <?php checked(!empty($p['stock_only']));?>> Fast stock/price-only mode</label><br><label><input type="checkbox" name="sideload_images" value="1" <?php checked(!empty($p['sideload_images']));?>> Download product images into Media Library</label></p><p><label>Imported product status <select name="status"><?php foreach(array('publish','draft','private') as $v):?><option <?php selected($p['status'],$v);?>><?php echo esc_html($v);?></option><?php endforeach;?></select></label></p><h3>Field mapping</h3><div class="wpbb-sync-map"><?php $fields=wpbb_sync_default_mapping($p['source_type']);foreach($fields as $field=>$default):$value=$p['mapping'][$field]??$default;?><label><?php echo esc_html($field);?><input name="map_<?php echo esc_attr($field);?>" value="<?php echo esc_attr($value);?>"></label><?php endforeach;?></div><p><label><strong>External cron key</strong><br><input class="widefat" name="token" value="<?php echo esc_attr($p['token']);?>"></label></p><?php if(!empty($p['id'])):?><p><small>External cron POST URL:<br><code><?php echo esc_html(rest_url('wpbb-woo/v1/sync/'.$p['id']).'?key='.$p['token']);?></code></small></p><?php endif;?><?php submit_button($editing?'Update profile':'Save profile');?></form></div></div></div><?php
}
