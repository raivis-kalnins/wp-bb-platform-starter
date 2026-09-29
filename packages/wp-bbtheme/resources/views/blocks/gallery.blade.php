@php
  $images = is_array($fields['images'] ?? null) ? $fields['images'] : [];
  $columns = $fields['columns'] ?? '3';
  $gap = $fields['gap_class'] ?? 'g-3';
@endphp
@if($images)
<div class="row row-cols-2 row-cols-md-{{ esc_attr($columns) }} {{ esc_attr($gap) }}">
  @foreach($images as $image)
    @if(!empty($image['url']))
      <div class="col"><img class="img-fluid rounded" src="{{ esc_url($image['url']) }}" alt="{{ esc_attr($image['alt'] ?? '') }}"></div>
    @endif
  @endforeach
</div>
@endif
