@php
  $title = $fields['title'] ?? '';
  $text = $fields['text'] ?? '';
  $buttonText = $fields['button_text'] ?? '';
  $buttonUrl = $fields['button_url'] ?? '';
  $background = $fields['background_image'] ?? [];
  $theme = $fields['theme'] ?? 'light';
  $titleSize = $fields['title_size'] ?? 'display-3';
  $textSize = $fields['text_size'] ?? 'lead';
  $titleColor = $fields['title_color'] ?? '';
  $textColor = $fields['text_color'] ?? '';
  $style = !empty($background['url']) ? 'background-image:url(' . esc_url($background['url']) . ');' : '';
@endphp
<section class="wpbb-hero wpbb-hero--{{ sanitize_html_class($theme) }} alignfull" style="{{ esc_attr($style) }}">
  <div class="container-fluid py-5">
    @if($title)
      <h1 class="wpbb-hero__title {{ esc_attr($titleSize) }}" @if($titleColor) style="color:{{ esc_attr($titleColor) }};" @endif>{{ $title }}</h1>
    @endif
    @if($text)
      <div class="wpbb-hero__text {{ esc_attr($textSize) }}" @if($textColor) style="color:{{ esc_attr($textColor) }};" @endif>{!! wp_kses_post(wpautop($text)) !!}</div>
    @endif
    @if($buttonText && $buttonUrl)
      <p><a class="btn btn-primary" href="{{ esc_url($buttonUrl) }}">{{ $buttonText }}</a></p>
    @endif
  </div>
</section>
