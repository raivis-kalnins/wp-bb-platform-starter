<?php
/** WP BB Home & Garden styled WooCommerce account login/register. */
defined('ABSPATH') || exit;
do_action('woocommerce_before_customer_login_form');
?>
<div class="llg-account-auth" id="customer_login">
  <section class="llg-account-auth-card llg-login-card">
    <span class="llg-account-kicker">WP BB HOME & GARDEN</span>
    <h2><?php esc_html_e('Ieiet kontā', 'wpbbshop'); ?></h2>
    <p class="llg-account-intro"><?php esc_html_e('Pieslēdzies, lai apskatītu pasūtījumus, adreses un konta informāciju.', 'wpbbshop'); ?></p>
    <form class="woocommerce-form woocommerce-form-login login" method="post">
      <?php do_action('woocommerce_login_form_start'); ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="username"><?php esc_html_e('E-pasts vai lietotājvārds', 'wpbbshop'); ?> <span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (!empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''); ?>" />
      </p>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="password"><?php esc_html_e('Parole', 'wpbbshop'); ?> <span class="required">*</span></label>
        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
      </p>
      <?php do_action('woocommerce_login_form'); ?>
      <div class="llg-login-actions">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme"><input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e('Atcerēties mani', 'wpbbshop'); ?></span></label>
        <p class="woocommerce-LostPassword lost_password"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Aizmirsāt paroli?', 'wpbbshop'); ?></a></p>
      </div>
      <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
      <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Ieiet', 'wpbbshop'); ?>"><?php esc_html_e('Ieiet', 'wpbbshop'); ?></button>
      <?php do_action('woocommerce_login_form_end'); ?>
    </form>
  </section>

  <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>
  <section class="llg-account-auth-card llg-register-card">
    <span class="llg-account-kicker">JAUNS KLIENTS</span>
    <h2><?php esc_html_e('Izveidot kontu', 'wpbbshop'); ?></h2>
    <p class="llg-account-intro"><?php esc_html_e('Reģistrējies, lai turpmāk pasūtījumus noformētu ātrāk un redzētu pirkumu vēsturi.', 'wpbbshop'); ?></p>
    <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>
      <?php do_action('woocommerce_register_form_start'); ?>
      <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_username"><?php esc_html_e('Lietotājvārds', 'wpbbshop'); ?> <span class="required">*</span></label><input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (!empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''); ?>" /></p>
      <?php endif; ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_email"><?php esc_html_e('E-pasts', 'wpbbshop'); ?> <span class="required">*</span></label><input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (!empty($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''); ?>" /></p>
      <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label for="reg_password"><?php esc_html_e('Parole', 'wpbbshop'); ?> <span class="required">*</span></label><input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" /></p>
      <?php else : ?><p><?php esc_html_e('Droša parole tiks nosūtīta uz jūsu e-pastu.', 'wpbbshop'); ?></p><?php endif; ?>
      <?php do_action('woocommerce_register_form'); ?>
      <p class="woocommerce-form-row form-row"><?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?><button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Reģistrēties', 'wpbbshop'); ?>"><?php esc_html_e('Reģistrēties', 'wpbbshop'); ?></button></p>
      <?php do_action('woocommerce_register_form_end'); ?>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php do_action('woocommerce_after_customer_login_form'); ?>
