<?php
/** WP BB Home & Garden styled WooCommerce account login/register. */
defined('ABSPATH') || exit;
$is_en = function_exists('wpbbshop_v313_is_en') ? wpbbshop_v313_is_en() : (substr((string) get_locale(), 0, 2) !== 'lv');
$t = static function($lv, $en) use ($is_en) { return $is_en ? $en : $lv; };
$woo_registration = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$b2b_registration = function_exists('iws_b2b_account_registration_available') && iws_b2b_account_registration_available();
$show_registration = $woo_registration || $b2b_registration;
do_action('woocommerce_before_customer_login_form');
?>
<div class="llg-account-auth<?php echo $show_registration ? '' : ' llg-account-auth--login-only'; ?>" id="customer_login">
  <section class="llg-account-auth-card llg-login-card">
    <span class="llg-account-kicker">WP BB HOME &amp; GARDEN</span>
    <h2><?php echo esc_html($t('Ieiet kontā', 'Sign in')); ?></h2>
    <p class="llg-account-intro"><?php echo esc_html($t('Pieslēdzies, lai apskatītu pasūtījumus, adreses un konta informāciju.', 'Sign in to view orders, addresses and account information.')); ?></p>
    <form class="woocommerce-form woocommerce-form-login login" method="post">
      <?php do_action('woocommerce_login_form_start'); ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="username"><?php echo esc_html($t('E-pasts vai lietotājvārds', 'Email address or username')); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (!empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''); ?>" />
      </p>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="password"><?php echo esc_html($t('Parole', 'Password')); ?> <span class="required">*</span></label>
        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
      </p>
      <?php do_action('woocommerce_login_form'); ?>
      <div class="llg-login-actions">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme"><input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php echo esc_html($t('Atcerēties mani', 'Remember me')); ?></span></label>
        <p class="woocommerce-LostPassword lost_password"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php echo esc_html($t('Aizmirsāt paroli?', 'Lost your password?')); ?></a></p>
      </div>
      <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
      <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php echo esc_attr($t('Ieiet', 'Sign in')); ?>"><?php echo esc_html($t('Ieiet', 'Sign in')); ?></button>
      <?php do_action('woocommerce_login_form_end'); ?>
    </form>
  </section>

  <?php if ($show_registration) : ?>
  <section class="llg-account-auth-card llg-register-card">
    <span class="llg-account-kicker"><?php echo esc_html($t('JAUNS KLIENTS', 'NEW CUSTOMER')); ?></span>
    <h2><?php echo esc_html($t('Izveidot kontu', 'Create an account')); ?></h2>
    <p class="llg-account-intro"><?php echo esc_html($b2b_registration && !$woo_registration ? $t('Piesakies B2B kontam, lai saņemtu biznesa cenas un uzņēmuma nosacījumus.', 'Apply for a B2B account to access trade pricing and business terms.') : $t('Reģistrējies, lai turpmāk pasūtījumus noformētu ātrāk un redzētu pirkumu vēsturi.', 'Register for faster checkout and access to your order history.')); ?></p>
    <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>
      <?php do_action('woocommerce_register_form_start'); ?>
      <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_username"><?php echo esc_html($t('Lietotājvārds', 'Username')); ?> <span class="required">*</span></label><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (!empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''); ?>" /></p>
      <?php endif; ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_email"><?php echo esc_html($t('E-pasts', 'Email address')); ?> <span class="required">*</span></label><input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (!empty($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''); ?>" /></p>
      <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_password"><?php echo esc_html($t('Parole', 'Password')); ?> <span class="required">*</span></label><input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" /></p>
      <?php else : ?><p><?php echo esc_html($t('Droša parole tiks nosūtīta uz jūsu e-pastu.', 'A secure password will be sent to your email address.')); ?></p><?php endif; ?>
      <?php do_action('woocommerce_register_form'); ?>
      <p class="woocommerce-form-row form-row"><?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?><button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php echo esc_attr($t('Reģistrēties', 'Register')); ?>"><?php echo esc_html($t('Reģistrēties', 'Register')); ?></button></p>
      <?php do_action('woocommerce_register_form_end'); ?>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php do_action('woocommerce_after_customer_login_form'); ?>
