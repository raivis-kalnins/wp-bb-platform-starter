<?php
get_header();

while (have_posts()) : the_post();
    $post_id = get_the_ID();
    $lv = function_exists('wpbbshop_v423_lang') ? wpbbshop_v423_lang() === 'lv' : (stripos((string)get_locale(), 'lv') === 0);
    $is_guide = function_exists('wpbbshop_v423_is_guide_post') && wpbbshop_v423_is_guide_post($post_id);
    $image = function_exists('wpbbshop_v423_guide_image_url') ? wpbbshop_v423_guide_image_url($post_id) : get_the_post_thumbnail_url($post_id, 'full');
    if (!$image && has_post_thumbnail($post_id)) { $image = get_the_post_thumbnail_url($post_id, 'full'); }
    $author_id = (int) get_post_field('post_author', $post_id);
    $author_name = get_the_author_meta('display_name', $author_id) ?: get_bloginfo('name');
    $minutes = function_exists('wpbbshop_v423_read_minutes') ? wpbbshop_v423_read_minutes($post_id) : max(2, (int) ceil(str_word_count(wp_strip_all_tags((string)get_post_field('post_content', $post_id))) / 180));
    $prev = get_previous_post(true);
    $next = get_next_post(true);
    $related = function_exists('wpbbshop_v423_related_guides') && $is_guide ? wpbbshop_v423_related_guides($post_id, 3) : array();
    $cats = get_the_category($post_id);
    $cat_name = !empty($cats) ? $cats[0]->name : ($lv ? 'Padomi' : 'Advice');
?>
<main class="wpbb-v423-blog-single">
  <div class="wpbb-v423-blog-shell">
    <nav class="wpbb-v423-blog-breadcrumb" aria-label="<?php echo esc_attr($lv ? 'Maizes drupačas' : 'Breadcrumb'); ?>">
      <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($lv ? 'Sākums' : 'Home'); ?></a><span>›</span><span><?php echo esc_html($cat_name); ?></span>
    </nav>

    <article <?php post_class('wpbb-v423-article'); ?>>
      <header class="wpbb-v423-article-head">
        <div class="wpbb-v423-article-kicker"><?php echo esc_html($cat_name); ?></div>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()) : ?><p class="wpbb-v423-article-deck"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
        <div class="wpbb-v423-article-meta">
          <span class="wpbb-v423-author-mini"><?php echo get_avatar($author_id, 40, '', '', array('class'=>'wpbb-v423-author-avatar')); ?><b><?php echo esc_html($author_name); ?></b></span>
          <span><?php echo esc_html(get_the_date()); ?></span>
          <span><?php echo esc_html(sprintf($lv ? '%d min lasīšanai' : '%d min read', $minutes)); ?></span>
        </div>
      </header>

      <?php
      $v424_gallery = function_exists('wpbbshop_v424_post_gallery_items') ? wpbbshop_v424_post_gallery_items($post_id) : array();
      if ($v424_gallery && function_exists('wpbbshop_v424_post_gallery_html')) {
          echo wpbbshop_v424_post_gallery_html($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      } elseif ($image) { ?>
      <figure class="wpbb-v423-article-hero">
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" fetchpriority="high" decoding="async" referrerpolicy="no-referrer">
      </figure>
      <?php } ?>

      <div class="wpbb-v423-article-layout">
        <div class="wpbb-v423-article-body">
          <?php the_content(); ?>
        </div>
        <aside class="wpbb-v423-article-aside">
          <div class="wpbb-v423-aside-card">
            <span><?php echo esc_html($lv ? 'PAR AUTORU' : 'ABOUT THE AUTHOR'); ?></span>
            <div class="wpbb-v423-author-card-head"><?php echo get_avatar($author_id, 60, '', '', array('class'=>'wpbb-v423-author-avatar')); ?><div><strong><?php echo esc_html($author_name); ?></strong><small>WP BB Home &amp; Garden</small></div></div>
            <p><?php echo esc_html($lv ? 'Praktiski ceļveži mājai, dārzam, darbnīcai un gudrākai preču izvēlei.' : 'Practical guides for home, garden, workshop projects and smarter product choices.'); ?></p>
          </div>
          <div class="wpbb-v423-aside-card is-help">
            <span><?php echo esc_html($lv ? 'VAJADZĪGA PALĪDZĪBA?' : 'NEED HELP?'); ?></span>
            <strong><?php echo esc_html($lv ? 'Atrodi piemērotu preci projektam' : 'Find the right product for the job'); ?></strong>
            <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php echo esc_html($lv ? 'Skatīt katalogu' : 'Browse catalogue'); ?> →</a>
          </div>
        </aside>
      </div>

      <footer class="wpbb-v423-article-footer">
        <div class="wpbb-v423-author-wide">
          <?php echo get_avatar($author_id, 72, '', '', array('class'=>'wpbb-v423-author-avatar')); ?>
          <div><span><?php echo esc_html($lv ? 'Raksta autors' : 'Written by'); ?></span><strong><?php echo esc_html($author_name); ?></strong><p><?php echo esc_html($lv ? 'WP BB Home & Garden produktu un projektu ceļveži.' : 'WP BB Home & Garden product and project guides.'); ?></p></div>
        </div>
      </footer>
    </article>

    <?php if ($prev || $next) : ?>
    <nav class="wpbb-v423-post-nav" aria-label="<?php echo esc_attr($lv ? 'Rakstu navigācija' : 'Post navigation'); ?>">
      <?php if ($prev) : $prev_img=function_exists('wpbbshop_v423_guide_image_url')?wpbbshop_v423_guide_image_url($prev->ID):get_the_post_thumbnail_url($prev->ID,'medium'); ?>
      <a class="is-prev" href="<?php echo esc_url(get_permalink($prev)); ?>"><?php if($prev_img): ?><img src="<?php echo esc_url($prev_img); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?><span><small>← <?php echo esc_html($lv ? 'Iepriekšējais raksts' : 'Previous article'); ?></small><strong><?php echo esc_html(get_the_title($prev)); ?></strong></span></a>
      <?php else : ?><span></span><?php endif; ?>
      <?php if ($next) : $next_img=function_exists('wpbbshop_v423_guide_image_url')?wpbbshop_v423_guide_image_url($next->ID):get_the_post_thumbnail_url($next->ID,'medium'); ?>
      <a class="is-next" href="<?php echo esc_url(get_permalink($next)); ?>"><span><small><?php echo esc_html($lv ? 'Nākamais raksts' : 'Next article'); ?> →</small><strong><?php echo esc_html(get_the_title($next)); ?></strong></span><?php if($next_img): ?><img src="<?php echo esc_url($next_img); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?></a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php if ($related) : ?>
    <section class="wpbb-v423-related-posts">
      <header><span><?php echo esc_html($lv ? 'VAIRĀK CEĻVEŽU' : 'MORE GUIDES'); ?></span><h2><?php echo esc_html($lv ? 'Turpini lasīt' : 'Keep reading'); ?></h2></header>
      <div class="wpbb-v423-related-grid">
        <?php foreach ($related as $item) : $rel_img=wpbbshop_v423_guide_image_url($item->ID); ?>
        <article><a href="<?php echo esc_url(get_permalink($item)); ?>"><?php if($rel_img): ?><img src="<?php echo esc_url($rel_img); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?><span><?php echo esc_html($lv ? 'Preču ceļvedis' : 'Product guide'); ?></span><h3><?php echo esc_html(get_the_title($item)); ?></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt($item), 18)); ?></p><b><?php echo esc_html($lv ? 'Lasīt rakstu →' : 'Read article →'); ?></b></a></article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</main>
<?php endwhile; get_footer();
