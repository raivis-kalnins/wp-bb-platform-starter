<?php
/** v3.0.10 — clean search button and one-row AJAX suggestions. */
defined('ABSPATH') || exit;

function wpbbshop_v310_search_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v310-search-css">
    body.wpbbshop-theme .llg-search-submit,
    body.wpbbshop-theme .llg-search-extended > .llg-search-submit,
    body.wpbbshop-theme .llg-mobile-search > .llg-search-submit{
      -webkit-appearance:none!important;appearance:none!important;
      box-sizing:border-box!important;display:flex!important;align-items:center!important;justify-content:center!important;
      margin:0!important;padding:0!important;border:0!important;outline:0!important;
      background:#2e994e!important;color:#fff!important;box-shadow:none!important;text-shadow:none!important;
      font:inherit!important;line-height:1!important;cursor:pointer!important;
    }
    body.wpbbshop-theme .llg-search-extended > .llg-search-submit{
      width:54px!important;min-width:54px!important;height:48px!important;border-radius:0 9px 9px 0!important;
    }
    body.wpbbshop-theme .llg-search-submit:hover,
    body.wpbbshop-theme .llg-search-submit:focus-visible{background:#237b3e!important}
    body.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon{
      display:block!important;width:23px!important;height:23px!important;color:#fff!important;stroke:#fff!important;fill:none!important;
      stroke-width:2!important;margin:0!important;padding:0!important;
    }

    body.wpbbshop-theme .wpbbshop-search-results-list{
      display:flex!important;flex-direction:column!important;gap:0!important;grid-template-columns:none!important;width:100%!important;
    }
    body.wpbbshop-theme .wpbbshop-search-result{
      display:grid!important;grid-template-columns:70px minmax(0,1fr)!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;
      gap:12px!important;align-items:center!important;padding:10px 12px!important;margin:0!important;
      border:0!important;border-bottom:1px solid #edf1f3!important;border-radius:0!important;background:#fff!important;color:#102b3c!important;
    }
    body.wpbbshop-theme .wpbbshop-search-result:first-child{border-radius:8px 8px 0 0!important}
    body.wpbbshop-theme .wpbbshop-search-result:last-child{border-bottom:0!important}
    body.wpbbshop-theme .wpbbshop-search-result:hover,
    body.wpbbshop-theme .wpbbshop-search-result:focus-visible{background:#f3f9f5!important}
    body.wpbbshop-theme .wpbbshop-search-result img,
    body.wpbbshop-theme .wpbbshop-search-noimg{
      box-sizing:border-box!important;width:70px!important;height:62px!important;padding:4px!important;border:1px solid #e8eef1!important;border-radius:8px!important;
      background:#fff!important;object-fit:contain!important;
    }
    body.wpbbshop-theme .llg-search-result-copy{display:flex!important;flex-direction:column!important;min-width:0!important;gap:4px!important}
    body.wpbbshop-theme .llg-search-result-top{display:flex!important;flex-wrap:wrap!important;gap:7px!important;align-items:center!important}
    body.wpbbshop-theme .wpbbshop-search-result strong{display:block!important;min-width:0!important;font-size:14px!important;line-height:1.3!important;font-weight:800!important;color:#0d293d!important}
    body.wpbbshop-theme .llg-search-result-bottom{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;min-width:0!important}
    body.wpbbshop-theme .llg-search-result-prices{display:flex!important;align-items:baseline!important;justify-content:flex-end!important;gap:7px!important;white-space:nowrap!important}
    body.wpbbshop-theme .llg-search-result-prices del{font-size:11px!important;color:#87939c!important;text-decoration-thickness:1px!important}
    body.wpbbshop-theme .llg-search-result-price{font-size:14px!important;font-weight:850!important;color:#17813a!important;white-space:nowrap!important}
    body.wpbbshop-theme .wpbbshop-search-all{margin:7px 0 0!important;min-height:44px!important;border-radius:8px!important}

    @media(max-width:820px){
      body.wpbbshop-theme .llg-mobile-search > .llg-search-submit{width:46px!important;min-width:46px!important;height:44px!important;border-radius:0 7px 7px 0!important}
      body.wpbbshop-theme .llg-mobile-search > .llg-search-submit .wpbbshop-green-icon{width:20px!important;height:20px!important}
      body.wpbbshop-theme .wpbbshop-search-result{grid-template-columns:58px minmax(0,1fr)!important;padding:8px!important;gap:9px!important}
      body.wpbbshop-theme .wpbbshop-search-result img,
      body.wpbbshop-theme .wpbbshop-search-noimg{width:58px!important;height:54px!important}
      body.wpbbshop-theme .wpbbshop-search-result strong{font-size:12px!important}
      body.wpbbshop-theme .llg-search-result-bottom{align-items:flex-end!important}
      body.wpbbshop-theme .llg-search-result-prices{gap:5px!important}
      body.wpbbshop-theme .llg-search-result-price{font-size:12px!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v310_search_css', PHP_INT_MAX);
