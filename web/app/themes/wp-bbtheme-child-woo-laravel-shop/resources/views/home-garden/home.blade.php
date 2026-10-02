<main class="wpbb-v400-home">
  <section class="wpbb-v400-shell wpbb-v400-hero">
    <div class="wpbb-v400-hero-copy">
      <span class="wpbb-v400-eyebrow">{{ $is_en ? 'HOME • GARDEN • BUILD • DIY' : 'MĀJA • DĀRZS • BŪVE • DIY' }}</span>
      <h1>{{ $is_en ? 'Big-project range. Fast everyday shopping.' : 'Plašs sortiments lieliem projektiem. Ātra ikdienas iepirkšanās.' }}</h1>
      <p>{{ $is_en ? 'Department-first navigation, quick SKU search and a WooCommerce architecture prepared for 40k+ products.' : 'Nodaļu navigācija, ātra SKU meklēšana un WooCommerce arhitektūra, kas sagatavota 40k+ precēm.' }}</p>
      <div class="wpbb-v400-hero-actions">
        <a class="wpbb-v400-btn is-primary" href="{{ $shop }}">{{ $is_en ? 'Shop catalogue' : 'Skatīt katalogu' }}</a>
        <a class="wpbb-v400-btn is-ghost" href="#departments">{{ $is_en ? 'Browse departments' : 'Skatīt nodaļas' }}</a>
      </div>
      <div class="wpbb-v400-trust">
        <span>40k+ {{ $is_en ? 'catalogue ready' : 'kataloga gatavība' }}</span>
        <span>{{ $delivery_label }}</span>
        <span>{{ $store_city }}</span>
      </div>
    </div>
    <div class="wpbb-v400-hero-media">
      <img src="{{ $hero_image }}" alt="{{ $is_en ? 'Garden machinery and home improvement' : 'Dārza tehnika un mājas labiekārtošana' }}" fetchpriority="high" decoding="async">
      <div class="wpbb-v400-hero-card"><strong>{{ $is_en ? 'Store pickup' : 'Saņemšana veikalā' }}</strong><span>{{ $address }}</span></div>
    </div>
  </section>

  <section id="departments" class="wpbb-v400-shell wpbb-v400-departments">
    <div class="wpbb-v400-section-head">
      <div><span>{{ $is_en ? '20 departments' : '20 nodaļas' }}</span><h2>{{ $is_en ? 'Everything for the job, organised clearly' : 'Viss darbam un projektam, sakārtots saprotami' }}</h2></div>
      <a href="{{ $shop }}">{{ $is_en ? 'Full catalogue' : 'Pilns katalogs' }} →</a>
    </div>
    <div class="wpbb-v400-dept-grid">
      @foreach ($terms as $item)
        <a class="wpbb-v400-dept" href="{{ $item['link'] }}">
          <span class="wpbb-v400-dept-icon">{!! function_exists('wpbbshop_category_icon_html') ? wpbbshop_category_icon_html($item['term'], true) : '•' !!}</span>
          <strong>{{ $item['label'] }}</strong>
          <small>{{ number_format_i18n((int) $item['term']->count) }} {{ $is_en ? 'products' : 'preces' }}</small>
        </a>
      @endforeach
    </div>
  </section>

  <section class="wpbb-v400-shell wpbb-v400-projects">
    <article><span>01</span><h3>{{ $is_en ? 'Garden & outdoor' : 'Dārzs un āra vide' }}</h3><p>{{ $is_en ? 'Machinery, irrigation, furniture, plants and seasonal care.' : 'Tehnika, laistīšana, mēbeles, augi un sezonas kopšana.' }}</p></article>
    <article><span>02</span><h3>{{ $is_en ? 'Build & renovate' : 'Būvē un atjauno' }}</h3><p>{{ $is_en ? 'Materials, tools, paint, plumbing, heating and electrical.' : 'Materiāli, instrumenti, krāsas, santehnika, apkure un elektrība.' }}</p></article>
    <article><span>03</span><h3>{{ $is_en ? 'Home & workshop' : 'Māja un darbnīca' }}</h3><p>{{ $is_en ? 'Storage, cleaning, safety and practical everyday equipment.' : 'Uzglabāšana, uzkopšana, drošība un praktisks ikdienas aprīkojums.' }}</p></article>
  </section>

  <section class="wpbb-v400-shell wpbb-v400-products">{!! $featured_html !!}</section>
  <section class="wpbb-v400-shell wpbb-v400-products">{!! $sale_html !!}</section>
  <section class="wpbb-v400-shell wpbb-v400-products">{!! $recent_html !!}</section>

  <section class="wpbb-v400-shell wpbb-v400-services">
    <div><strong>{{ $is_en ? 'Fast search' : 'Ātra meklēšana' }}</strong><span>{{ $is_en ? 'Name, brand, SKU and barcode.' : 'Nosaukums, zīmols, SKU un svītrkods.' }}</span></div>
    <div><strong>{{ $is_en ? 'Real stock' : 'Reāls atlikums' }}</strong><span>{{ $is_en ? 'WooCommerce stock and HPOS-ready commerce.' : 'WooCommerce noliktava un HPOS gatava komercija.' }}</span></div>
    <div><strong>{{ $is_en ? 'Comparison feeds' : 'Cenu salīdzināšana' }}</strong><span>KurPirkt.lv • Salidzini.lv • Ceno.lv</span></div>
    <div><strong>{{ $pickup_label }}</strong><span>{{ $address }}</span></div>
  </section>
</main>
