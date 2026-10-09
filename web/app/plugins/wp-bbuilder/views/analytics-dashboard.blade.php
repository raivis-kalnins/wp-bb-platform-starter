{{-- Laravel/Acorn Blade version of the analytics dashboard. Native WordPress sites use the PHP fallback automatically. --}}
@php
    $fallback = WPBB_PLUGIN_DIR . 'views/analytics-dashboard.php';
    extract(get_defined_vars(), EXTR_SKIP);
    include $fallback;
@endphp
