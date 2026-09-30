<?php defined('ABSPATH') || exit; get_header(); ?>
<main class="wpbbshop-404-page">
  <div class="wpbbshop-container">
    <section class="wpbbshop-404-card">
      <h1><?php esc_html_e('Lapa nav atrasta', 'wpbbshop'); ?></h1>
      <p><?php esc_html_e('Šī lapa, iespējams, ir pārvietota vai vairs nepastāv. Izmanto meklēšanu vai atgriezies veikalā.', 'wpbbshop'); ?></p>
      <form role="search" method="get" class="wpbbshop-search wpbbshop-ajax-search" action="<?php echo esc_url(home_url('/')); ?>" autocomplete="off">
        <div class="wpbbshop-search-field-wrap"><input type="search" name="s" placeholder="Meklēt produktus, SKU vai ID..."><div class="wpbbshop-search-results" hidden></div></div>
        <input type="hidden" name="post_type" value="product"><button type="submit" aria-label="Meklēt">⌕</button>
      </form>
      <div class="wpbbshop-404-actions"><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Sākums', 'wpbbshop'); ?></a><a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Visas preces', 'wpbbshop'); ?></a></div>
    </section>
  </div>
</main>
<?php get_footer(); ?>
