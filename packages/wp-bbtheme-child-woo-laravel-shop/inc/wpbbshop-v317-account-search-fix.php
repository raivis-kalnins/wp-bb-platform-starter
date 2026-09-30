<?php
/**
 * WP BB Home & Garden 3.0.17
 * Account password controls and site-wide header search alignment hardening.
 */
defined('ABSPATH') || exit;

function wpbbshop_v317_account_search_css() {
    ?>
    <style id="wpbbshop-v317-account-search-css">
    /* One search geometry on every desktop page. Keep the button inside the 1px field border. */
    @media (min-width:821px){
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:164px minmax(0,1fr) 54px!important;
        align-items:center!important;
        width:100%!important;
        height:50px!important;
        min-height:50px!important;
        padding:0!important;
        border:1px solid #d4dde3!important;
        border-radius:9px!important;
        background:#fff!important;
        overflow:visible!important;
        box-shadow:none!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap{
        box-sizing:border-box!important;
        height:48px!important;
        min-height:48px!important;
        border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select{
        box-sizing:border-box!important;
        width:100%!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        border:0!important;
        border-radius:8px 0 0 8px!important;
        background:transparent!important;
        box-shadow:none!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap{
        box-sizing:border-box!important;
        min-width:0!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
        align-self:center!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search]{
        box-sizing:border-box!important;
        display:block!important;
        width:100%!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0 16px!important;
        border:0!important;
        border-radius:0!important;
        outline:0!important;
        background:#fff!important;
        box-shadow:none!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
        align-self:center!important;
        justify-self:end!important;
        width:53px!important;
        min-width:53px!important;
        max-width:53px!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-left:1px solid #278a45!important;
        border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;
        color:#fff!important;
        box-shadow:none!important;
        transform:none!important;
        display:grid!important;
        place-items:center!important;
        line-height:1!important;
        overflow:hidden!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:hover,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:focus-visible{
        background:#247f40!important;
        color:#fff!important;
        outline:0!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button .wpbbshop-green-icon{
        display:block!important;
        width:21px!important;
        height:21px!important;
        margin:0!important;
        position:static!important;
        transform:none!important;
        color:#fff!important;
        stroke:currentColor!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:before,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:after{
        content:none!important;
        display:none!important;
      }
    }

    /* Mobile field also stays fully contained; no 1px protrusion on the search button. */
    @media (max-width:820px){
      body.wpbbshop-theme .llg-mobile-search{
        box-sizing:border-box!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{
        box-sizing:border-box!important;
        align-self:stretch!important;
        width:46px!important;
        min-width:46px!important;
        max-width:46px!important;
        height:44px!important;
        min-height:44px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-left:1px solid #278a45!important;
        border-radius:0 7px 7px 0!important;
        background:#2f9d50!important;
        color:#fff!important;
        box-shadow:none!important;
        display:grid!important;
        place-items:center!important;
      }
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit .wpbbshop-green-icon{
        display:block!important;
        width:19px!important;
        height:19px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
      }
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:before,
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:after{content:none!important;display:none!important}
    }

    /* WooCommerce password reveal controls: replace the broken dash glyph with a real eye button. */
    body.woocommerce-account .password-input,
    body.woocommerce-account span.password-input{
      position:relative!important;
      display:block!important;
      width:100%!important;
      max-width:none!important;
    }
    body.woocommerce-account .password-input input[type=password],
    body.woocommerce-account .password-input input[type=text]{
      box-sizing:border-box!important;
      width:100%!important;
      padding-right:52px!important;
    }
    body.woocommerce-account .show-password-input,
    body.woocommerce-account button.show-password-input,
    body.woocommerce-account span.show-password-input{
      box-sizing:border-box!important;
      position:absolute!important;
      z-index:4!important;
      top:50%!important;
      right:7px!important;
      left:auto!important;
      bottom:auto!important;
      width:36px!important;
      min-width:36px!important;
      max-width:36px!important;
      height:36px!important;
      min-height:36px!important;
      margin:0!important;
      padding:0!important;
      border:1px solid transparent!important;
      border-radius:8px!important;
      background:transparent!important;
      background-image:none!important;
      color:#6d7d88!important;
      font-family:inherit!important;
      font-size:0!important;
      line-height:0!important;
      text-indent:-9999px!important;
      transform:translateY(-50%)!important;
      box-shadow:none!important;
      cursor:pointer!important;
      overflow:visible!important;
    }
    body.woocommerce-account .show-password-input:hover,
    body.woocommerce-account .show-password-input:focus-visible{
      border-color:#d6e2dc!important;
      background:#f0f7f2!important;
      color:#278a45!important;
      outline:0!important;
    }
    body.woocommerce-account .show-password-input:before{
      content:""!important;
      display:block!important;
      position:absolute!important;
      left:50%!important;
      top:50%!important;
      width:17px!important;
      height:11px!important;
      margin:0!important;
      border:2px solid currentColor!important;
      border-radius:55% 45% / 60% 60%!important;
      background:transparent!important;
      transform:translate(-50%,-50%) rotate(45deg)!important;
      box-sizing:border-box!important;
      opacity:1!important;
    }
    body.woocommerce-account .show-password-input:after{
      content:""!important;
      display:block!important;
      position:absolute!important;
      left:50%!important;
      top:50%!important;
      width:5px!important;
      height:5px!important;
      margin:0!important;
      border:0!important;
      border-radius:50%!important;
      background:currentColor!important;
      transform:translate(-50%,-50%)!important;
      opacity:1!important;
    }
    body.woocommerce-account .show-password-input.display-password:before{border-color:#278a45!important}
    body.woocommerce-account .woocommerce-MyAccount-content fieldset .form-row{position:relative!important}
    body.woocommerce-account .woocommerce-MyAccount-content fieldset label{display:block!important;margin:0 0 7px!important;color:#334754!important;font-weight:650!important}
    body.woocommerce-account .woocommerce-MyAccount-content fieldset .woocommerce-form-row{margin-bottom:16px!important}
    body.woocommerce-account .woocommerce-MyAccount-content fieldset button.button,
    body.woocommerce-account .woocommerce-MyAccount-content form button[type=submit]{
      display:inline-flex!important;
      align-items:center!important;
      justify-content:center!important;
      min-height:46px!important;
      padding:0 22px!important;
      border:1px solid #2b9748!important;
      border-radius:9px!important;
      background:#2b9748!important;
      color:#fff!important;
      font-weight:850!important;
      line-height:1!important;
      box-shadow:none!important;
    }
    body.woocommerce-account .woocommerce-MyAccount-content fieldset button.button:hover,
    body.woocommerce-account .woocommerce-MyAccount-content form button[type=submit]:hover{
      border-color:#237d3c!important;
      background:#237d3c!important;
      color:#fff!important;
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v317_account_search_css', PHP_INT_MAX);
