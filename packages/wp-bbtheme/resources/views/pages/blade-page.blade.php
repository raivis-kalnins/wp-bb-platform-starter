@php(get_header())
<main id="primary" class="site-main container py-5">
  @while(have_posts())
    @php(the_post())
    <article @php(post_class('wpbb-blade-page'))>
      <h1>{{ get_the_title() }}</h1>
      <div class="entry-content">{!! apply_filters('the_content', get_the_content()) !!}</div>
    </article>
  @endwhile
</main>
@php(get_footer())
