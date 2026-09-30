<?php
/**
 * Plugin Name: TFA Payment Hub for WooCommerce
 * Plugin URI:  https://wp-workspace.co.uk/plugins/wp-payment-hub/
 * Description: One clear WooCommerce payment method with organised bank, Direct Debit and wallet options, including Stripe-hosted Apple Pay and Google Pay.
 * Version:     2.3.0
 * Author:      TFA Workspace
 * Author URI:  https://wp-workspace.co.uk/
 * Text Domain: wp-payment-hub
 * Update URI:  https://wp-workspace.co.uk/plugins/wp-payment-hub/
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.5
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'before_woocommerce_init', 'tfa_payment_hub_declare_compatibility' );
function tfa_payment_hub_declare_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
}

add_action( 'admin_enqueue_scripts', 'tfa_payment_hub_enqueue_admin_media' );
function tfa_payment_hub_enqueue_admin_media( $hook_suffix ) {
    if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
        return;
    }

    $tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
    $section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
    if ( 'checkout' === $tab && 'universal_payments_gateway' === $section ) {
        wp_enqueue_media();
    }
}

add_action( 'plugins_loaded', 'tfa_payment_hub_init', 11 );
function tfa_payment_hub_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_Gateway_TFA_Payment_Hub extends WC_Payment_Gateway {
        private $providers = array();

        public function __construct() {
            $this->id                 = 'universal_payments_gateway';
            $this->method_title       = __( 'TFA Payment Hub', 'wp-payment-hub' );
            $this->method_description = __( 'One WooCommerce payment method containing bank, wallet and Direct Debit choices, with provider-level controls and hosted-payment configuration.', 'wp-payment-hub' );
            $this->has_fields         = true;
            $this->supports           = array( 'products' );
            $this->providers          = $this->get_providers();

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title', __( 'Secure payment', 'wp-payment-hub' ) );
            $this->description = $this->get_option( 'description', __( 'Choose how you would like to pay.', 'wp-payment-hub' ) );
            $this->enabled     = $this->get_option( 'enabled', 'no' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_wc_gateway_tfa_payment_hub_redirect', array( $this, 'render_redirect_form' ) );
            add_action( 'woocommerce_api_wc_gateway_tfa_payment_hub_response', array( $this, 'handle_response' ) );
            add_action( 'woocommerce_api_wc_gateway_tfa_payment_hub_notify', array( $this, 'handle_notification' ) );
            add_action( 'woocommerce_api_wc_gateway_tfa_payment_hub_stripe_return', array( $this, 'handle_stripe_return' ) );
            add_action( 'woocommerce_api_wc_gateway_tfa_payment_hub_stripe_webhook', array( $this, 'handle_stripe_webhook' ) );
            add_action( 'wp_ajax_tfa_payment_hub_test_provider', array( $this, 'ajax_test_provider' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_manual_instructions' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_manual_instructions' ), 10, 3 );

            // Backward-compatible callback URLs from previous private versions.
            add_action( 'woocommerce_api_wc_gateway_universal_payments_redirect', array( $this, 'render_redirect_form' ) );
            add_action( 'woocommerce_api_wc_gateway_universal_payments_response', array( $this, 'handle_response' ) );
            add_action( 'woocommerce_api_wc_gateway_universal_payments_notify', array( $this, 'handle_notification' ) );

            // Backward-compatible old callback URLs from the original single-provider version.
            add_action( 'woocommerce_api_wc_gateway_rack_group_redirect', array( $this, 'render_redirect_form' ) );
            add_action( 'woocommerce_api_wc_gateway_rack_group_response', array( $this, 'handle_response' ) );
            add_action( 'woocommerce_api_wc_gateway_rack_group_notify', array( $this, 'handle_notification' ) );
        }

        private function get_providers() {
            return array(
                'natwest' => array(
                    'name' => 'NatWest',
                    'group' => 'bank',
                    'flow' => 'hosted',
                    'default_title' => 'NatWest Pay',
                    'default_description' => 'Pay securely by card through NatWest.',
                    'default_test_url' => 'https://test.ipg-online.com/connect/gateway/processing',
                    'default_live_url' => 'https://www.ipg-online.com/connect/gateway/processing',
                    'help' => 'Original NatWest/IPG Connect profile. Enter the Store ID and Shared Secret supplied for the hosted payment page, test first, then use Live mode only after approval.',
                ),
                'barclays' => array(
                    'name' => 'Barclays', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Barclays Payment', 'default_description' => 'Pay securely through Barclays.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Configure the hosted payment endpoint and merchant credentials supplied by Barclays or its approved processor.',
                ),
                'lloyds' => array(
                    'name' => 'Lloyds / Cardnet', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Lloyds Cardnet', 'default_description' => 'Pay securely through Lloyds Cardnet.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use the hosted payment endpoint and credentials supplied by Lloyds Cardnet or its connected processor.',
                ),
                'hsbc' => array(
                    'name' => 'HSBC', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'HSBC Payment', 'default_description' => 'Pay securely through HSBC.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Enter the hosted payment URL, merchant ID and signing secret supplied by HSBC or its payment processor.',
                ),
                'standard_chartered' => array(
                    'name' => 'Standard Chartered', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Standard Chartered', 'default_description' => 'Pay securely through Standard Chartered.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Add the hosted payment endpoint and credentials supplied by Standard Chartered or its processor.',
                ),
                'worldpay' => array(
                    'name' => 'Worldpay', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Worldpay', 'default_description' => 'Pay securely through Worldpay.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use a Worldpay hosted payment page or approved redirect endpoint. Confirm the required request and response fields with Worldpay before enabling Live mode.',
                ),
                'swedbank' => array(
                    'name' => 'Swedbank', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Swedbank', 'default_description' => 'Pay securely through Swedbank.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use Swedbank Baltic e-commerce or processor-provided hosted payment credentials.',
                ),
                'seb' => array(
                    'name' => 'SEB', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'SEB', 'default_description' => 'Pay securely through SEB.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use the SEB merchant portal or processor documentation to obtain the hosted endpoint and credentials.',
                ),
                'luminor' => array(
                    'name' => 'Luminor', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Luminor', 'default_description' => 'Pay securely through Luminor.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Enter the hosted payment URL and credentials supplied by Luminor or your Baltic payment processor.',
                ),
                'revolut' => array(
                    'name' => 'Revolut', 'group' => 'bank', 'flow' => 'hosted',
                    'default_title' => 'Revolut Pay', 'default_description' => 'Pay securely with Revolut.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use a Revolut Business hosted checkout or approved redirect endpoint. Native Revolut Pay API features require the provider integration.',
                ),

                'apple_google_pay' => array(
                    'name' => 'Apple Pay & Google Pay', 'group' => 'wallet', 'flow' => 'stripe_checkout',
                    'default_title' => 'Apple Pay or Google Pay',
                    'default_description' => 'Use Apple Pay or Google Pay through a secure Stripe-hosted checkout.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'A real Stripe Checkout integration. Stripe displays Apple Pay on eligible Apple devices and Google Pay on eligible devices and browsers. Wallet and card details stay on Stripe. Enable this only after the Stripe account is approved for the products sold by the store.',
                ),

                'paypal' => array(
                    'name' => 'PayPal', 'group' => 'wallet', 'flow' => 'hosted',
                    'default_title' => 'PayPal', 'default_description' => 'Pay securely with PayPal.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'This profile is for a hosted redirect URL. Native PayPal buttons, vaulting and refunds require the approved PayPal WooCommerce extension.',
                ),
                'stripe' => array(
                    'name' => 'Stripe', 'group' => 'wallet', 'flow' => 'hosted',
                    'default_title' => 'Stripe Checkout', 'default_description' => 'Pay securely through Stripe Checkout.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'This profile is for a hosted redirect endpoint. Native Payment Intents, Apple Pay and Google Pay require an approved Stripe or WooPayments extension.',
                ),
                'woopayments' => array(
                    'name' => 'WooPayments', 'group' => 'wallet', 'flow' => 'hosted',
                    'default_title' => 'WooPayments', 'default_description' => 'Pay securely with WooPayments.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'WooPayments normally runs as its own approved extension. Keep this profile disabled unless you have a supported hosted redirect endpoint.',
                ),
                'square' => array(
                    'name' => 'Square', 'group' => 'wallet', 'flow' => 'hosted',
                    'default_title' => 'Square', 'default_description' => 'Pay securely with Square.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'This profile is for a hosted Square payment link or redirect endpoint. Native Square checkout requires its official extension.',
                ),
                'amazon_pay' => array(
                    'name' => 'Amazon Pay', 'group' => 'wallet', 'flow' => 'hosted',
                    'default_title' => 'Amazon Pay', 'default_description' => 'Pay securely with Amazon Pay.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'This profile is for a hosted redirect endpoint. Native Amazon Pay buttons require the approved Amazon Pay integration.',
                ),

                'bacs_direct_debit' => array(
                    'name' => 'Bacs Direct Debit', 'group' => 'direct_debit', 'flow' => 'manual',
                    'default_title' => 'Bacs Direct Debit',
                    'default_description' => 'Place the order and complete the approved Direct Debit mandate instructions.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Manual mandate workflow. The plugin does not collect or store bank account details. Provide approved mandate instructions and complete collection through your sponsor bank or Direct Debit bureau.',
                ),
                'gocardless' => array(
                    'name' => 'GoCardless', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Direct Debit by GoCardless', 'default_description' => 'Set up a secure Direct Debit mandate with GoCardless.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use an approved hosted mandate endpoint. Native GoCardless API, mandate lifecycle and webhook handling require a dedicated certified integration.',
                ),
                'sepa_direct_debit' => array(
                    'name' => 'SEPA Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'SEPA Direct Debit', 'default_description' => 'Set up a secure SEPA Direct Debit mandate.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use a provider-hosted SEPA mandate page. Do not collect IBAN details directly through this generic plugin.',
                ),
                'natwest_direct_debit' => array(
                    'name' => 'NatWest Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'NatWest Direct Debit', 'default_description' => 'Set up Direct Debit through NatWest.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a bank-approved hosted mandate endpoint and credentials supplied for Direct Debit.',
                ),
                'barclays_direct_debit' => array(
                    'name' => 'Barclays Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Barclays Direct Debit', 'default_description' => 'Set up Direct Debit through Barclays.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a Barclays-approved hosted mandate endpoint and credentials.',
                ),
                'lloyds_direct_debit' => array(
                    'name' => 'Lloyds Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Lloyds Direct Debit', 'default_description' => 'Set up Direct Debit through Lloyds.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a Lloyds-approved hosted mandate endpoint and credentials.',
                ),
                'hsbc_direct_debit' => array(
                    'name' => 'HSBC Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'HSBC Direct Debit', 'default_description' => 'Set up Direct Debit through HSBC.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only an HSBC-approved hosted mandate endpoint and credentials.',
                ),
                'standard_chartered_direct_debit' => array(
                    'name' => 'Standard Chartered Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Standard Chartered Direct Debit', 'default_description' => 'Set up Direct Debit through Standard Chartered.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only an approved hosted mandate endpoint and credentials.',
                ),
                'worldpay_direct_debit' => array(
                    'name' => 'Worldpay Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Worldpay Direct Debit', 'default_description' => 'Set up Direct Debit through Worldpay.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use a Worldpay-approved hosted mandate or alternative payment method endpoint.',
                ),
                'swedbank_direct_debit' => array(
                    'name' => 'Swedbank Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Swedbank Direct Debit', 'default_description' => 'Set up Direct Debit through Swedbank.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a Swedbank-approved hosted mandate endpoint and credentials.',
                ),
                'seb_direct_debit' => array(
                    'name' => 'SEB Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'SEB Direct Debit', 'default_description' => 'Set up Direct Debit through SEB.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only an SEB-approved hosted mandate endpoint and credentials.',
                ),
                'luminor_direct_debit' => array(
                    'name' => 'Luminor Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Luminor Direct Debit', 'default_description' => 'Set up Direct Debit through Luminor.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a Luminor-approved hosted mandate endpoint and credentials.',
                ),
                'revolut_direct_debit' => array(
                    'name' => 'Revolut Direct Debit', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Revolut Direct Debit', 'default_description' => 'Set up Direct Debit through Revolut.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Use only a Revolut-approved hosted mandate endpoint and credentials.',
                ),
                'custom_direct_debit_1' => array(
                    'name' => 'Custom Direct Debit 1', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Direct Debit', 'default_description' => 'Set up a secure Direct Debit mandate.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Custom hosted mandate profile for an approved Direct Debit provider.',
                ),
                'custom_direct_debit_2' => array(
                    'name' => 'Custom Direct Debit 2', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Direct Debit', 'default_description' => 'Set up a secure Direct Debit mandate.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Second custom hosted mandate profile for an approved Direct Debit provider.',
                ),
                'custom_direct_debit_3' => array(
                    'name' => 'Custom Direct Debit 3', 'group' => 'direct_debit', 'flow' => 'hosted',
                    'default_title' => 'Direct Debit', 'default_description' => 'Set up a secure Direct Debit mandate.',
                    'default_test_url' => '', 'default_live_url' => '',
                    'help' => 'Third custom hosted mandate profile for an approved Direct Debit provider.',
                ),
            );
        }

        public function init_form_fields() {
            $fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable / Disable', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable TFA Payment Hub at checkout', 'wp-payment-hub' ),
                    'default' => 'no',
                ),
                'title' => array(
                    'title'       => __( 'Checkout title', 'wp-payment-hub' ),
                    'type'        => 'text',
                    'description' => __( 'The single payment method title customers see in WooCommerce.', 'wp-payment-hub' ),
                    'default'     => __( 'Secure payment', 'wp-payment-hub' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Checkout description', 'wp-payment-hub' ),
                    'type'        => 'textarea',
                    'description' => __( 'Introductory text displayed above the bank, wallet and Direct Debit choices.', 'wp-payment-hub' ),
                    'default'     => __( 'Choose how you would like to pay.', 'wp-payment-hub' ),
                    'desc_tip'    => true,
                ),
                'enable_bank' => array(
                    'title'   => __( 'Banks and cards', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Show enabled bank and hosted card providers', 'wp-payment-hub' ),
                    'default' => 'yes',
                ),
                'enable_direct_debit' => array(
                    'title'   => __( 'Direct Debit', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Show enabled Direct Debit options inside this payment method', 'wp-payment-hub' ),
                    'default' => 'no',
                ),
                'enable_wallet' => array(
                    'title'   => __( 'Wallets and other providers', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Show enabled wallet and alternative hosted providers', 'wp-payment-hub' ),
                    'default' => 'no',
                ),
                'default_group' => array(
                    'title'       => __( 'Default payment section', 'wp-payment-hub' ),
                    'type'        => 'select',
                    'default'     => 'bank',
                    'options'     => array(
                        'bank'         => __( 'Banks and cards', 'wp-payment-hub' ),
                        'direct_debit' => __( 'Direct Debit', 'wp-payment-hub' ),
                        'wallet'       => __( 'Wallets and other', 'wp-payment-hub' ),
                    ),
                ),
                'default_provider' => array(
                    'title'       => __( 'Default provider', 'wp-payment-hub' ),
                    'type'        => 'select',
                    'description' => __( 'Provider pre-selected when it is enabled in the selected section.', 'wp-payment-hub' ),
                    'default'     => 'natwest',
                    'options'     => $this->provider_options(),
                ),
                'direct_debit_consent_text' => array(
                    'title'       => __( 'Direct Debit consent', 'wp-payment-hub' ),
                    'type'        => 'textarea',
                    'default'     => __( 'I understand that a Direct Debit mandate must be completed and approved before collection.', 'wp-payment-hub' ),
                    'description' => __( 'Displayed as a required confirmation when a Direct Debit provider is selected. Use wording approved by your sponsor bank or provider.', 'wp-payment-hub' ),
                ),
                'manual_dd_order_status' => array(
                    'title'   => __( 'Manual Direct Debit order status', 'wp-payment-hub' ),
                    'type'    => 'select',
                    'default' => 'on-hold',
                    'options' => array(
                        'on-hold' => __( 'On hold', 'wp-payment-hub' ),
                        'pending' => __( 'Pending payment', 'wp-payment-hub' ),
                    ),
                ),
                'manual_dd_instructions' => array(
                    'title'       => __( 'Default manual Direct Debit instructions', 'wp-payment-hub' ),
                    'type'        => 'textarea',
                    'default'     => __( 'We will contact you with the approved Direct Debit mandate instructions. Your order will remain on hold until the mandate is confirmed.', 'wp-payment-hub' ),
                    'description' => __( 'Shown on the order confirmation page and customer email for manual Direct Debit orders.', 'wp-payment-hub' ),
                ),
                'transaction_type' => array(
                    'title'       => __( 'Hosted transaction type', 'wp-payment-hub' ),
                    'type'        => 'select',
                    'default'     => 'sale',
                    'options'     => array(
                        'sale'    => __( 'Sale', 'wp-payment-hub' ),
                        'preauth' => __( 'Pre-authorisation', 'wp-payment-hub' ),
                    ),
                ),
                'debug' => array(
                    'title'   => __( 'Debug log', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable logging in WooCommerce > Status > Logs', 'wp-payment-hub' ),
                    'default' => 'no',
                ),
                'send_3ds_challenge_indicator' => array(
                    'title'       => __( '3-D Secure challenge indicator', 'wp-payment-hub' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Send threeDSRequestorChallengeIndicator=01 with IPG Connect requests', 'wp-payment-hub' ),
                    'default'     => 'no',
                    'description' => __( 'Leave disabled unless the processor specifically requires it.', 'wp-payment-hub' ),
                ),
            );

            foreach ( $this->providers as $provider_id => $provider ) {
                $prefix = $provider_id . '_';
                $base_class = 'wp-provider-field wp-provider-field-' . esc_attr( $provider_id ) . ' wp-provider-group-' . esc_attr( $provider['group'] );

                $fields[ $prefix . 'section' ] = array(
                    'title'       => $provider['name'],
                    'type'        => 'provider_title',
                    'description' => $provider['help'],
                    'provider_id' => $provider_id,
                    'provider_group' => $provider['group'],
                    'provider_flow' => $provider['flow'],
                );
                $fields[ $prefix . 'enabled' ] = array(
                    'title'   => __( 'Provider status', 'wp-payment-hub' ),
                    'type'    => 'checkbox',
                    'label'   => sprintf( __( 'Enable %s inside TFA Payment Hub', 'wp-payment-hub' ), $provider['name'] ),
                    'default' => ( 'natwest' === $provider_id ) ? 'yes' : 'no',
                    'class'   => $base_class,
                );
                $fields[ $prefix . 'title' ] = array(
                    'title'       => __( 'Customer label', 'wp-payment-hub' ),
                    'type'        => 'text',
                    'default'     => $provider['default_title'],
                    'description' => __( 'Name shown inside the single checkout payment method.', 'wp-payment-hub' ),
                    'class'       => $base_class,
                );
                $fields[ $prefix . 'description' ] = array(
                    'title'       => __( 'Customer description', 'wp-payment-hub' ),
                    'type'        => 'textarea',
                    'default'     => $provider['default_description'],
                    'description' => __( 'Short explanation shown beneath this provider choice.', 'wp-payment-hub' ),
                    'class'       => $base_class,
                );
                $fields[ $prefix . 'logo_id' ] = array(
                    'title'         => __( 'Bank / provider logo', 'wp-payment-hub' ),
                    'type'          => 'media_image',
                    'description'   => __( 'Optional logo displayed beside this payment choice at checkout. A transparent PNG, WebP or SVG with a wide layout works best.', 'wp-payment-hub' ),
                    'provider_id'   => $provider_id,
                    'provider_name' => $provider['name'],
                    'image_role'    => 'logo',
                    'class'         => $base_class,
                );
                $fields[ $prefix . 'icon_id' ] = array(
                    'title'         => __( 'Card / payment icon', 'wp-payment-hub' ),
                    'type'          => 'media_image',
                    'description'   => __( 'Optional compact card, mandate or wallet icon displayed on the right of this payment choice.', 'wp-payment-hub' ),
                    'provider_id'   => $provider_id,
                    'provider_name' => $provider['name'],
                    'image_role'    => 'icon',
                    'class'         => $base_class,
                );

                if ( 'manual' === $provider['flow'] ) {
                    $fields[ $prefix . 'manual_instructions' ] = array(
                        'title'       => __( 'Provider-specific instructions', 'wp-payment-hub' ),
                        'type'        => 'textarea',
                        'default'     => '',
                        'description' => __( 'Optional. Leave blank to use the default manual Direct Debit instructions from General settings.', 'wp-payment-hub' ),
                        'class'       => $base_class,
                    );
                    continue;
                }

                if ( 'stripe_checkout' === $provider['flow'] ) {
                    $fields[ $prefix . 'setup_info' ] = array(
                        'title'       => __( 'How it works', 'wp-payment-hub' ),
                        'type'        => 'wallet_setup_info',
                        'class'       => $base_class,
                    );
                    $fields[ $prefix . 'payment_mode' ] = array(
                        'title'   => __( 'Mode', 'wp-payment-hub' ),
                        'type'    => 'select',
                        'default' => 'test',
                        'options' => array(
                            'test' => __( 'Test / sandbox', 'wp-payment-hub' ),
                            'live' => __( 'Live', 'wp-payment-hub' ),
                        ),
                        'class'   => $base_class,
                    );
                    $fields[ $prefix . 'test_secret_key' ] = array(
                        'title'       => __( 'Test secret key', 'wp-payment-hub' ),
                        'type'        => 'password',
                        'description' => __( 'Stripe test secret key beginning with sk_test_.', 'wp-payment-hub' ),
                        'class'       => $base_class,
                    );
                    $fields[ $prefix . 'test_webhook_secret' ] = array(
                        'title'       => __( 'Test webhook secret', 'wp-payment-hub' ),
                        'type'        => 'password',
                        'description' => __( 'Recommended. Stripe signing secret beginning with whsec_.', 'wp-payment-hub' ),
                        'class'       => $base_class,
                    );
                    $fields[ $prefix . 'live_secret_key' ] = array(
                        'title'       => __( 'Live secret key', 'wp-payment-hub' ),
                        'type'        => 'password',
                        'description' => __( 'Stripe live secret key beginning with sk_live_.', 'wp-payment-hub' ),
                        'class'       => $base_class,
                    );
                    $fields[ $prefix . 'live_webhook_secret' ] = array(
                        'title'       => __( 'Live webhook secret', 'wp-payment-hub' ),
                        'type'        => 'password',
                        'description' => __( 'Recommended. Live Stripe signing secret beginning with whsec_.', 'wp-payment-hub' ),
                        'class'       => $base_class,
                    );
                    $fields[ $prefix . 'test_button' ] = array(
                        'title'         => __( 'Connection check', 'wp-payment-hub' ),
                        'type'          => 'provider_test_button',
                        'provider_id'   => $provider_id,
                        'provider_name' => $provider['name'],
                        'class'         => $base_class,
                    );
                    continue;
                }

                $fields[ $prefix . 'payment_mode' ] = array(
                    'title'   => __( 'Mode', 'wp-payment-hub' ),
                    'type'    => 'select',
                    'default' => 'test',
                    'options' => array(
                        'test' => __( 'Test / sandbox', 'wp-payment-hub' ),
                        'live' => __( 'Live', 'wp-payment-hub' ),
                    ),
                    'class'   => $base_class,
                );
                $fields[ $prefix . 'hash_encoding' ] = array(
                    'title'       => __( 'Hash encoding', 'wp-payment-hub' ),
                    'type'        => 'select',
                    'default'     => 'hex',
                    'options'     => array(
                        'hex' => __( 'SHA256 over hex-encoded string (IPG Connect)', 'wp-payment-hub' ),
                        'raw' => __( 'SHA256 over raw string', 'wp-payment-hub' ),
                    ),
                    'description' => __( 'Use the format required by the hosted payment provider.', 'wp-payment-hub' ),
                    'class'       => $base_class,
                );
                $fields[ $prefix . 'test_gateway_url' ] = array(
                    'title'       => __( 'Test gateway or mandate URL', 'wp-payment-hub' ),
                    'type'        => 'text',
                    'default'     => $provider['default_test_url'],
                    'description' => __( 'Provider-hosted test endpoint. Bank details must remain on the provider page.', 'wp-payment-hub' ),
                    'class'       => $base_class,
                );
                $fields[ $prefix . 'test_store_id' ] = array(
                    'title' => __( 'Test merchant / store ID', 'wp-payment-hub' ),
                    'type'  => 'text',
                    'class' => $base_class,
                );
                $fields[ $prefix . 'test_shared_secret' ] = array(
                    'title' => __( 'Test shared secret', 'wp-payment-hub' ),
                    'type'  => 'password',
                    'class' => $base_class,
                );
                $fields[ $prefix . 'live_gateway_url' ] = array(
                    'title'       => __( 'Live gateway or mandate URL', 'wp-payment-hub' ),
                    'type'        => 'text',
                    'default'     => $provider['default_live_url'],
                    'description' => __( 'Provider-hosted production endpoint.', 'wp-payment-hub' ),
                    'class'       => $base_class,
                );
                $fields[ $prefix . 'live_store_id' ] = array(
                    'title' => __( 'Live merchant / store ID', 'wp-payment-hub' ),
                    'type'  => 'text',
                    'class' => $base_class,
                );
                $fields[ $prefix . 'live_shared_secret' ] = array(
                    'title' => __( 'Live shared secret', 'wp-payment-hub' ),
                    'type'  => 'password',
                    'class' => $base_class,
                );
                $fields[ $prefix . 'test_button' ] = array(
                    'title'         => __( 'Configuration check', 'wp-payment-hub' ),
                    'type'          => 'provider_test_button',
                    'provider_id'   => $provider_id,
                    'provider_name' => $provider['name'],
                    'class'         => $base_class,
                );
            }

            $this->form_fields = $fields;
        }

        public function process_admin_options() {
            $result = parent::process_admin_options();
            if ( ! $result ) {
                return $result;
            }

            $settings = get_option( $this->get_option_key(), array() );
            if ( ! is_array( $settings ) ) {
                $settings = array();
            }

            foreach ( array_keys( $this->providers ) as $provider_id ) {
                foreach ( array( 'logo_id', 'icon_id' ) as $image_key ) {
                    $option_key = $provider_id . '_' . $image_key;
                    $field_key  = $this->get_field_key( $option_key );
                    if ( isset( $_POST[ $field_key ] ) ) {
                        $settings[ $option_key ] = absint( wp_unslash( $_POST[ $field_key ] ) );
                    }
                }
            }

            update_option( $this->get_option_key(), $settings );
            $this->settings = $settings;
            return $result;
        }

        private function provider_options() {
            $options = array();
            foreach ( $this->providers as $provider_id => $provider ) {
                $options[ $provider_id ] = $provider['name'];
            }
            return $options;
        }

        public function admin_options() {
            wp_enqueue_media();

            $counts = array( 'bank' => 0, 'direct_debit' => 0, 'wallet' => 0 );
            $enabled_counts = array( 'bank' => 0, 'direct_debit' => 0, 'wallet' => 0 );
            foreach ( $this->providers as $provider_id => $provider ) {
                $counts[ $provider['group'] ]++;
                if ( 'yes' === $this->get_option( $provider_id . '_enabled', 'no' ) ) {
                    $enabled_counts[ $provider['group'] ]++;
                }
            }

            echo '<div class="wp-payment-hub-admin">';
            echo '<div class="wp-payment-hub-hero">';
            echo '<div><h2>' . esc_html( $this->get_method_title() ) . '</h2><p>' . esc_html__( 'Manage the customer-facing gateway, then open a category and choose one payment method to edit.', 'wp-payment-hub' ) . '</p></div>';
            echo '<div class="wp-hub-status ' . ( 'yes' === $this->enabled ? 'is-enabled' : 'is-disabled' ) . '"><span></span>' . esc_html( 'yes' === $this->enabled ? __( 'Gateway enabled', 'wp-payment-hub' ) : __( 'Gateway disabled', 'wp-payment-hub' ) ) . '</div>';
            echo '</div>';

            echo '<div class="wp-hub-summary">';
            echo '<div><strong>' . esc_html( $enabled_counts['bank'] ) . '</strong><span>' . esc_html__( 'banks enabled', 'wp-payment-hub' ) . '</span></div>';
            echo '<div><strong>' . esc_html( $enabled_counts['direct_debit'] ) . '</strong><span>' . esc_html__( 'Direct Debit enabled', 'wp-payment-hub' ) . '</span></div>';
            echo '<div><strong>' . esc_html( $enabled_counts['wallet'] ) . '</strong><span>' . esc_html__( 'wallets enabled', 'wp-payment-hub' ) . '</span></div>';
            echo '</div>';

            echo '<nav class="nav-tab-wrapper wp-primary-tabs" aria-label="' . esc_attr__( 'Payment settings sections', 'wp-payment-hub' ) . '">';
            echo '<a href="#" class="nav-tab nav-tab-active" data-group="general">' . esc_html__( 'General', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-group="bank">' . esc_html__( 'Banks & Cards', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-group="direct_debit">' . esc_html__( 'Direct Debit', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-group="wallet">' . esc_html__( 'Wallets', 'wp-payment-hub' ) . '</a>';
            echo '</nav>';

            echo '<div class="wp-secondary-tabs-wrap">';
            echo '<nav class="nav-tab-wrapper wp-secondary-tabs is-visible" data-parent="general" aria-hidden="false" aria-label="' . esc_attr__( 'General settings', 'wp-payment-hub' ) . '">';
            echo '<a href="#" class="nav-tab nav-tab-active" data-subtab="checkout">' . esc_html__( 'Checkout', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-subtab="choices">' . esc_html__( 'Payment choices', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-subtab="processing">' . esc_html__( 'Processing', 'wp-payment-hub' ) . '</a>';
            echo '<a href="#" class="nav-tab" data-subtab="logs">' . esc_html__( 'Logs & security', 'wp-payment-hub' ) . '</a>';
            echo '</nav>';

            foreach ( array( 'bank', 'direct_debit', 'wallet' ) as $group ) {
                echo '<nav class="nav-tab-wrapper wp-secondary-tabs" data-parent="' . esc_attr( $group ) . '" aria-hidden="true" hidden aria-label="' . esc_attr__( 'Payment methods', 'wp-payment-hub' ) . '">';
                if ( 'direct_debit' === $group ) {
                    echo '<a href="#" class="nav-tab nav-tab-active" data-subtab="direct_debit_settings"><span class="wp-method-dot is-global"></span>' . esc_html__( 'Direct Debit settings', 'wp-payment-hub' ) . '</a>';
                }
                $first = true;
                foreach ( $this->providers as $provider_id => $provider ) {
                    if ( $provider['group'] !== $group ) {
                        continue;
                    }
                    $active_class = ( $first && 'direct_debit' !== $group ) ? ' nav-tab-active' : '';
                    $enabled_class = 'yes' === $this->get_option( $provider_id . '_enabled', 'no' ) ? ' is-enabled' : '';
                    echo '<a href="#" class="nav-tab' . esc_attr( $active_class ) . '" data-subtab="' . esc_attr( $provider_id ) . '"><span class="wp-method-dot' . esc_attr( $enabled_class ) . '"></span>' . esc_html( $provider['name'] ) . '</a>';
                    $first = false;
                }
                echo '</nav>';
            }
            echo '</div>';

            echo '<div class="wp-current-panel"><strong class="wp-current-title"></strong><span class="wp-current-description"></span></div>';
            echo '<table class="form-table wp-payments-settings-table">';
            $this->generate_settings_html();
            echo '</table>';
            echo '</div>';
            $this->print_admin_script();
        }


        public function generate_provider_title_html( $key, $data ) {
            $provider_id = isset( $data['provider_id'] ) ? $data['provider_id'] : '';
            $group = isset( $data['provider_group'] ) ? $data['provider_group'] : 'bank';
            $flow = isset( $data['provider_flow'] ) ? $data['provider_flow'] : 'hosted';
            $group_labels = array(
                'bank' => __( 'Bank / card', 'wp-payment-hub' ),
                'direct_debit' => __( 'Direct Debit', 'wp-payment-hub' ),
                'wallet' => __( 'Wallet / other', 'wp-payment-hub' ),
            );
            ob_start();
            ?>
            <tr valign="top" class="wp-provider-section wp-provider-section-<?php echo esc_attr( $provider_id ); ?> wp-provider-group-<?php echo esc_attr( $group ); ?>">
                <th colspan="2">
                    <div class="wp-provider-card-title">
                        <div>
                            <span class="wp-provider-type"><?php echo esc_html( $group_labels[ $group ] ); ?></span>
                            <h3><?php echo esc_html( $data['title'] ); ?></h3>
                        </div>
                        <span class="wp-provider-flow"><?php echo esc_html( 'manual' === $flow ? __( 'Manual Direct Debit', 'wp-payment-hub' ) : ( 'stripe_checkout' === $flow ? __( 'Express wallets', 'wp-payment-hub' ) : __( 'Secure provider page', 'wp-payment-hub' ) ) ); ?></span>
                    </div>
                    <div class="wp-provider-help"><strong><?php esc_html_e( 'Setup notes:', 'wp-payment-hub' ); ?></strong> <?php echo esc_html( $data['description'] ); ?></div>
                </th>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function generate_media_image_html( $key, $data ) {
            $field_key     = $this->get_field_key( $key );
            $attachment_id = absint( $this->get_option( $key, 0 ) );
            $provider_id   = isset( $data['provider_id'] ) ? sanitize_key( $data['provider_id'] ) : '';
            $provider_name = isset( $data['provider_name'] ) ? $data['provider_name'] : '';
            $image_role    = isset( $data['image_role'] ) ? sanitize_key( $data['image_role'] ) : 'logo';
            $image_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
            $preview_alt   = 'icon' === $image_role
                ? sprintf( __( '%s payment icon preview', 'wp-payment-hub' ), $provider_name )
                : sprintf( __( '%s logo preview', 'wp-payment-hub' ), $provider_name );
            ob_start();
            ?>
            <tr valign="top" class="universal-provider-field universal-provider-field-<?php echo esc_attr( $provider_id ); ?>" data-provider="<?php echo esc_attr( $provider_id ); ?>">
                <th scope="row" class="titledesc"><label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $data['title'] ); ?></label></th>
                <td class="forminp">
                    <div class="wp-media-image-field" data-role="<?php echo esc_attr( $image_role ); ?>">
                        <input type="hidden" id="<?php echo esc_attr( $field_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>">
                        <div class="wp-media-image-preview<?php echo $image_url ? ' has-image' : ''; ?>">
                            <?php if ( $image_url ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $preview_alt ); ?>">
                            <?php else : ?>
                                <span><?php esc_html_e( 'No image selected', 'wp-payment-hub' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="wp-media-image-actions">
                            <button type="button" class="button wp-select-provider-image" data-title="<?php echo esc_attr( 'icon' === $image_role ? __( 'Choose payment icon', 'wp-payment-hub' ) : __( 'Choose bank or provider logo', 'wp-payment-hub' ) ); ?>"><?php esc_html_e( 'Choose image', 'wp-payment-hub' ); ?></button>
                            <button type="button" class="button-link-delete wp-remove-provider-image"<?php echo $image_url ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'wp-payment-hub' ); ?></button>
                        </div>
                        <?php if ( ! empty( $data['description'] ) ) : ?>
                            <p class="description"><?php echo esc_html( $data['description'] ); ?></p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function validate_media_image_field( $key, $value ) {
            return absint( $value );
        }

        public function generate_wallet_setup_info_html( $key, $data ) {
            $webhook_url = add_query_arg( array( 'wc-api' => 'wc_gateway_tfa_payment_hub_stripe_webhook' ), home_url( '/' ) );
            ob_start();
            ?>
            <tr valign="top" class="universal-provider-field universal-provider-field-apple_google_pay" data-provider="apple_google_pay">
                <th scope="row" class="titledesc"><?php esc_html_e( 'Stripe Checkout', 'wp-payment-hub' ); ?></th>
                <td class="forminp">
                    <div class="wp-wallet-steps">
                        <div><span>1</span><p><strong><?php esc_html_e( 'Add Stripe keys', 'wp-payment-hub' ); ?></strong><br><?php esc_html_e( 'Use test keys first, then switch to live keys after a successful test order.', 'wp-payment-hub' ); ?></p></div>
                        <div><span>2</span><p><strong><?php esc_html_e( 'Add the webhook', 'wp-payment-hub' ); ?></strong><br><code><?php echo esc_html( $webhook_url ); ?></code></p></div>
                        <div><span>3</span><p><strong><?php esc_html_e( 'Enable wallets in Stripe', 'wp-payment-hub' ); ?></strong><br><?php esc_html_e( 'Stripe Checkout shows Apple Pay or Google Pay only when the customer device and account support it.', 'wp-payment-hub' ); ?></p></div>
                    </div>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function generate_provider_test_button_html( $key, $data ) {
            $field_key = $this->get_field_key( $key );
            $provider_id = isset( $data['provider_id'] ) ? $data['provider_id'] : '';
            $provider_name = isset( $data['provider_name'] ) ? $data['provider_name'] : '';
            $provider_flow = isset( $this->providers[ $provider_id ]['flow'] ) ? $this->providers[ $provider_id ]['flow'] : 'hosted';
            ob_start();
            ?>
            <tr valign="top" class="universal-provider-field universal-provider-field-<?php echo esc_attr( $provider_id ); ?>">
                <th scope="row" class="titledesc"><label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $data['title'] ); ?></label></th>
                <td class="forminp">
                    <button type="button" class="button button-secondary wp-test-provider" data-provider="<?php echo esc_attr( $provider_id ); ?>"><?php echo esc_html( sprintf( __( 'Check %s configuration', 'wp-payment-hub' ), $provider_name ) ); ?></button>
                    <span class="wp-test-result" id="wp-test-result-<?php echo esc_attr( $provider_id ); ?>"></span>
                    <p class="description"><?php echo esc_html( 'stripe_checkout' === $provider_flow ? __( 'Checks the selected Stripe key and confirms that the account can be reached. It does not create a charge.', 'wp-payment-hub' ) : __( 'Checks the required credentials and tests whether the configured endpoint can be reached. It does not perform a real transaction.', 'wp-payment-hub' ) ); ?></p>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        private function print_admin_script() {
            $provider_groups = array();
            $provider_titles = array();
            $provider_descriptions = array();
            foreach ( $this->providers as $provider_id => $provider ) {
                $provider_groups[ $provider_id ] = $provider['group'];
                $provider_titles[ $provider_id ] = $provider['name'];
                $provider_descriptions[ $provider_id ] = $provider['help'];
            }
            $nonce = wp_create_nonce( 'tfa_payment_hub_test_provider' );
            ?>
            <style>
                .wp-payment-hub-admin{max-width:1180px}.wp-payment-hub-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:22px 24px;margin:14px 0 12px;box-shadow:0 1px 2px rgba(0,0,0,.03)}.wp-payment-hub-hero h2{font-size:25px;margin:0 0 6px}.wp-payment-hub-hero p{margin:0;color:#50575e;max-width:720px}.wp-hub-status{display:flex;align-items:center;gap:8px;white-space:nowrap;border-radius:999px;padding:7px 12px;font-weight:600;background:#f6f7f7}.wp-hub-status span{width:9px;height:9px;border-radius:50%;background:#8c8f94}.wp-hub-status.is-enabled{background:#edfaef;color:#006b2d}.wp-hub-status.is-enabled span{background:#00a32a}.wp-hub-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:0 0 16px}.wp-hub-summary>div{display:flex;align-items:baseline;gap:8px;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:13px 15px}.wp-hub-summary strong{font-size:20px}.wp-hub-summary span{color:#646970}.wp-primary-tabs{margin-top:0}.wp-secondary-tabs-wrap{padding-top:12px}.wp-secondary-tabs{display:none!important;overflow-x:auto;overflow-y:hidden;scrollbar-width:thin}.wp-secondary-tabs[hidden]{display:none!important}.wp-secondary-tabs.is-visible:not([hidden]){display:flex!important}.wp-secondary-tabs .nav-tab{display:flex;align-items:center;gap:7px;flex:0 0 auto;white-space:nowrap}.wp-method-dot{width:8px;height:8px;border-radius:50%;background:#a7aaad}.wp-method-dot.is-enabled{background:#00a32a}.wp-method-dot.is-global{background:#2271b1}.wp-current-panel{display:flex;align-items:baseline;gap:10px;background:#f0f6fc;border:1px solid #c5d9ed;border-radius:8px;padding:10px 13px;margin:12px 0}.wp-current-title{font-size:14px;white-space:nowrap}.wp-current-description{color:#50575e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wp-payments-settings-table{background:#fff;border:1px solid #dcdcde;border-radius:10px;border-collapse:separate;padding:2px 20px;margin-top:0}.wp-payments-settings-table th{width:245px}.wp-provider-section th{padding-top:22px!important;border-top:0!important}.wp-provider-card-title{display:flex;justify-content:space-between;align-items:center}.wp-provider-card-title h3{font-size:20px;margin:3px 0 0}.wp-provider-type,.wp-provider-flow{display:inline-block;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:600}.wp-provider-type{background:#f0f0f1;color:#2c3338}.wp-provider-flow{background:#edfaef;color:#006b2d}.wp-provider-help{background:#f6f7f7;border-left:4px solid #2271b1;padding:12px;margin:12px 0 0;font-weight:400}.wp-test-result{display:inline-block;margin-left:10px;font-weight:600}.wp-test-result.success{color:#008a20}.wp-test-result.error{color:#b32d2e}.wp-wallet-steps{display:grid;gap:8px}.wp-wallet-steps>div{display:flex;align-items:flex-start;gap:10px;background:#f6f7f7;border-radius:8px;padding:10px}.wp-wallet-steps>div>span{display:flex;align-items:center;justify-content:center;flex:0 0 24px;width:24px;height:24px;border-radius:50%;background:#2271b1;color:#fff;font-weight:700}.wp-wallet-steps p{margin:0}.wp-wallet-steps code{word-break:break-all}.wp-media-image-field{display:grid;gap:10px}.wp-media-image-preview{display:flex;align-items:center;justify-content:center;width:190px;min-height:76px;border:1px dashed #c3c4c7;border-radius:8px;background:#f6f7f7;color:#646970;padding:10px;box-sizing:border-box}.wp-media-image-preview.has-image{background:#fff;border-style:solid}.wp-media-image-preview img{display:block;max-width:168px;max-height:64px;width:auto;height:auto}.wp-media-image-field[data-role=icon] .wp-media-image-preview{width:112px}.wp-media-image-field[data-role=icon] .wp-media-image-preview img{max-width:88px;max-height:48px}.wp-media-image-actions{display:flex;align-items:center;gap:12px}.wp-payment-hub-admin input.regular-input,.wp-payment-hub-admin input[type=text],.wp-payment-hub-admin input[type=password],.wp-payment-hub-admin textarea,.wp-payment-hub-admin select{max-width:560px}.wp-payment-hub-admin textarea{min-height:92px}.wp-payments-settings-table tr.is-wp-hidden{display:none!important}@media(max-width:782px){.wp-payment-hub-hero{display:block}.wp-hub-status{display:inline-flex;margin-top:12px}.wp-hub-summary{grid-template-columns:1fr}.wp-primary-tabs{display:flex;overflow-x:auto}.wp-primary-tabs .nav-tab{white-space:nowrap}.wp-current-panel{display:block}.wp-current-description{display:block;white-space:normal;margin-top:4px}.wp-provider-card-title{display:block}.wp-provider-flow{margin-top:10px}.wp-payments-settings-table{padding:2px 10px}.wp-payments-settings-table th{width:auto}.wp-secondary-tabs{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:thin}}
            </style>
            <script>
                jQuery(function($){
                    var providerGroups = <?php echo wp_json_encode( $provider_groups ); ?>;
                    var providerTitles = <?php echo wp_json_encode( $provider_titles ); ?>;
                    var providerDescriptions = <?php echo wp_json_encode( $provider_descriptions ); ?>;
                    var generalSections = {
                        enabled: 'checkout',
                        title: 'checkout',
                        description: 'checkout',
                        enable_bank: 'choices',
                        enable_direct_debit: 'choices',
                        enable_wallet: 'choices',
                        default_group: 'choices',
                        default_provider: 'choices',
                        transaction_type: 'processing',
                        send_3ds_challenge_indicator: 'processing',
                        debug: 'logs'
                    };
                    var directDebitSettings = {
                        direct_debit_consent_text: true,
                        manual_dd_order_status: true,
                        manual_dd_instructions: true
                    };
                    var panelCopy = {
                        checkout: ['<?php echo esc_js( __( 'Checkout', 'wp-payment-hub' ) ); ?>', '<?php echo esc_js( __( 'Turn the gateway on and set the title and description customers see.', 'wp-payment-hub' ) ); ?>'],
                        choices: ['<?php echo esc_js( __( 'Payment choices', 'wp-payment-hub' ) ); ?>', '<?php echo esc_js( __( 'Choose which payment categories appear and which option is selected first.', 'wp-payment-hub' ) ); ?>'],
                        processing: ['<?php echo esc_js( __( 'Processing', 'wp-payment-hub' ) ); ?>', '<?php echo esc_js( __( 'Control hosted transaction behaviour and 3-D Secure options.', 'wp-payment-hub' ) ); ?>'],
                        logs: ['<?php echo esc_js( __( 'Logs & security', 'wp-payment-hub' ) ); ?>', '<?php echo esc_js( __( 'Enable diagnostic logging only while testing or troubleshooting.', 'wp-payment-hub' ) ); ?>'],
                        direct_debit_settings: ['<?php echo esc_js( __( 'Direct Debit settings', 'wp-payment-hub' ) ); ?>', '<?php echo esc_js( __( 'Set the consent wording, order status and customer instructions used by Direct Debit methods.', 'wp-payment-hub' ) ); ?>']
                    };

                    var providerIds = Object.keys(providerGroups).sort(function(a, b){ return b.length - a.length; });
                    var settingsFieldPrefix = 'woocommerce_universal_payments_gateway_';

                    function keyForRow($row) {
                        var fieldId = $row.find('[id^="' + settingsFieldPrefix + '"]').first().attr('id') || '';
                        if (fieldId.indexOf(settingsFieldPrefix) === 0) {
                            return fieldId.substring(settingsFieldPrefix.length);
                        }
                        var html = $row.html() || '';
                        var match = html.match(/(?:woocommerce_)?universal_payments_gateway_([a-z0-9_]+)/);
                        return match ? match[1] : '';
                    }

                    function providerForRow($row) {
                        var explicitProvider = $row.attr('data-provider') || $row.find('[data-provider]').first().attr('data-provider') || '';
                        if (explicitProvider && providerGroups[explicitProvider]) {
                            return explicitProvider;
                        }
                        for (var i = 0; i < providerIds.length; i++) {
                            var classProvider = providerIds[i];
                            if ($row.hasClass('wp-provider-section-' + classProvider) || $row.hasClass('universal-provider-field-' + classProvider)) {
                                return classProvider;
                            }
                        }
                        var key = keyForRow($row);
                        for (var j = 0; j < providerIds.length; j++) {
                            var id = providerIds[j];
                            if (key === id || key.indexOf(id + '_') === 0) {
                                return id;
                            }
                        }
                        return '';
                    }

                    function defaultSubtab(group) {
                        if (group === 'general') return 'checkout';
                        if (group === 'direct_debit') return 'direct_debit_settings';
                        var $first = $('.wp-secondary-tabs[data-parent="' + group + '"] a').first();
                        return $first.data('subtab') || '';
                    }

                    function setPanelCopy(group, subtab) {
                        var title = '';
                        var description = '';
                        if (providerTitles[subtab]) {
                            title = providerTitles[subtab];
                            description = providerDescriptions[subtab] || '';
                        } else if (panelCopy[subtab]) {
                            title = panelCopy[subtab][0];
                            description = panelCopy[subtab][1];
                        }
                        $('.wp-current-title').text(title);
                        $('.wp-current-description').text(description);
                    }

                    function rowBelongs($row, group, subtab) {
                        var provider = providerForRow($row);
                        if (provider) return providerGroups[provider] === group && provider === subtab;
                        var key = keyForRow($row);
                        if (group === 'general') return generalSections[key] === subtab;
                        if (group === 'direct_debit' && subtab === 'direct_debit_settings') return !!directDebitSettings[key];
                        return false;
                    }

                    function showPanel(group, subtab) {
                        if (!$('.wp-secondary-tabs[data-parent="' + group + '"] a[data-subtab="' + subtab + '"]').length) {
                            subtab = defaultSubtab(group);
                        }
                        $('.wp-primary-tabs .nav-tab').removeClass('nav-tab-active');
                        $('.wp-primary-tabs .nav-tab[data-group="' + group + '"]').addClass('nav-tab-active');
                        var $secondaryTabs = $('.wp-secondary-tabs');
                        $secondaryTabs.removeClass('is-visible').attr('hidden', true).attr('aria-hidden', 'true');
                        $secondaryTabs.filter('[data-parent="' + group + '"]').first().removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-visible');
                        $('.wp-secondary-tabs a').removeClass('nav-tab-active');
                        $('.wp-secondary-tabs[data-parent="' + group + '"] a[data-subtab="' + subtab + '"]').addClass('nav-tab-active');
                        $('.wp-payments-settings-table tr').each(function(){
                            $(this).toggleClass('is-wp-hidden', !rowBelongs($(this), group, subtab));
                        });
                        setPanelCopy(group, subtab);
                        try {
                            window.localStorage.setItem('tfaPaymentHubAdminGroup', group);
                            window.localStorage.setItem('tfaPaymentHubAdminSubtab_' + group, subtab);
                        } catch(e) {}
                    }

                    $('.wp-primary-tabs').on('click', '.nav-tab', function(e){
                        e.preventDefault();
                        var group = $(this).data('group');
                        var subtab = '';
                        try { subtab = window.localStorage.getItem('tfaPaymentHubAdminSubtab_' + group) || ''; } catch(e) {}
                        showPanel(group, subtab || defaultSubtab(group));
                    });
                    $('.wp-secondary-tabs').on('click', 'a', function(e){
                        e.preventDefault();
                        showPanel($(this).closest('.wp-secondary-tabs').data('parent'), $(this).data('subtab'));
                    });

                    $('.wp-payment-hub-admin').on('click', '.wp-select-provider-image', function(e){
                        e.preventDefault();
                        var $button = $(this);
                        var $field = $button.closest('.wp-media-image-field');
                        var frame = wp.media({
                            title: $button.data('title') || '<?php echo esc_js( __( 'Choose image', 'wp-payment-hub' ) ); ?>',
                            button: { text: '<?php echo esc_js( __( 'Use this image', 'wp-payment-hub' ) ); ?>' },
                            library: { type: 'image' },
                            multiple: false
                        });
                        frame.on('select', function(){
                            var attachment = frame.state().get('selection').first().toJSON();
                            var previewUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                            $field.find('input[type="hidden"]').val(attachment.id).trigger('change');
                            $field.find('.wp-media-image-preview').addClass('has-image').html($('<img>', { src: previewUrl, alt: attachment.alt || attachment.title || '' }));
                            $field.find('.wp-remove-provider-image').prop('hidden', false).removeAttr('hidden');
                        });
                        frame.open();
                    });

                    $('.wp-payment-hub-admin').on('click', '.wp-remove-provider-image', function(e){
                        e.preventDefault();
                        var $field = $(this).closest('.wp-media-image-field');
                        $field.find('input[type="hidden"]').val('').trigger('change');
                        $field.find('.wp-media-image-preview').removeClass('has-image').html($('<span>').text('<?php echo esc_js( __( 'No image selected', 'wp-payment-hub' ) ); ?>'));
                        $(this).prop('hidden', true).attr('hidden', 'hidden');
                    });

                    $('.wp-test-provider').on('click', function(e){
                        e.preventDefault();
                        var provider = $(this).data('provider');
                        var $result = $('#wp-test-result-' + provider);
                        $result.removeClass('success error').text('<?php echo esc_js( __( 'Checking...', 'wp-payment-hub' ) ); ?>');
                        $.post(ajaxurl, {
                            action: 'tfa_payment_hub_test_provider',
                            nonce: '<?php echo esc_js( $nonce ); ?>',
                            provider: provider
                        }).done(function(response){
                            if (response && response.success) {
                                $result.addClass('success').text(response.data.message);
                            } else {
                                $result.addClass('error').text(response && response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Configuration check failed.', 'wp-payment-hub' ) ); ?>');
                            }
                        }).fail(function(){
                            $result.addClass('error').text('<?php echo esc_js( __( 'Configuration check failed.', 'wp-payment-hub' ) ); ?>');
                        });
                    });

                    var initialGroup = 'general';
                    try { initialGroup = window.localStorage.getItem('tfaPaymentHubAdminGroup') || 'general'; } catch(e) {}
                    if (!$('.wp-primary-tabs .nav-tab[data-group="' + initialGroup + '"]').length) initialGroup = 'general';
                    var initialSubtab = '';
                    try { initialSubtab = window.localStorage.getItem('tfaPaymentHubAdminSubtab_' + initialGroup) || ''; } catch(e) {}
                    showPanel(initialGroup, initialSubtab || defaultSubtab(initialGroup));
                });
            </script>
            <?php
        }


        public function ajax_test_provider() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-payment-hub' ) ) );
            }
            check_ajax_referer( 'tfa_payment_hub_test_provider', 'nonce' );

            $provider_id = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
            if ( empty( $this->providers[ $provider_id ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Unknown provider.', 'wp-payment-hub' ) ) );
            }

            $config = $this->get_provider_config( $provider_id );
            if ( 'yes' !== $config['enabled'] ) {
                wp_send_json_error( array( 'message' => __( 'Provider is disabled. Enable it before testing.', 'wp-payment-hub' ) ) );
            }
            if ( 'manual' === $config['flow'] ) {
                wp_send_json_success( array( 'message' => __( 'Manual mandate option is enabled. No gateway credentials are required.', 'wp-payment-hub' ) ) );
            }
            if ( 'stripe_checkout' === $config['flow'] ) {
                if ( empty( $config['secret_key'] ) ) {
                    wp_send_json_error( array( 'message' => __( 'Add the Stripe secret key for the selected mode.', 'wp-payment-hub' ) ) );
                }
                $response = $this->stripe_api_request( 'GET', '/v1/account', $config['secret_key'] );
                if ( is_wp_error( $response ) ) {
                    wp_send_json_error( array( 'message' => $response->get_error_message() ) );
                }
                $message = ! empty( $config['webhook_secret'] )
                    ? __( 'Stripe connected. The webhook secret is also configured.', 'wp-payment-hub' )
                    : __( 'Stripe connected. Add the webhook secret before going live.', 'wp-payment-hub' );
                wp_send_json_success( array( 'message' => $message ) );
            }
            if ( empty( $config['gateway_url'] ) || empty( $config['store_id'] ) || empty( $config['shared_secret'] ) ) {
                wp_send_json_error( array( 'message' => __( 'Missing Gateway URL, Merchant/Store ID or Shared Secret for the selected mode.', 'wp-payment-hub' ) ) );
            }

            $response = wp_remote_head( $config['gateway_url'], array( 'timeout' => 10, 'redirection' => 2 ) );
            if ( is_wp_error( $response ) ) {
                wp_send_json_success( array( 'message' => sprintf( __( 'Required fields are present. URL check warning: %s', 'wp-payment-hub' ), $response->get_error_message() ) ) );
            }
            $code = wp_remote_retrieve_response_code( $response );
            wp_send_json_success( array( 'message' => sprintf( __( 'Required fields are present. Gateway URL returned HTTP %s.', 'wp-payment-hub' ), $code ? $code : __( 'no status', 'wp-payment-hub' ) ) ) );
        }

        public function payment_fields() {
            if ( $this->description ) {
                echo wp_kses_post( wpautop( $this->description ) );
            }

            $enabled_providers = $this->get_checkout_providers();
            if ( empty( $enabled_providers ) ) {
                echo '<p>' . esc_html__( 'No configured provider is currently available inside TFA Payment Hub. Please choose another payment method or contact the store.', 'wp-payment-hub' ) . '</p>';
                return;
            }

            $groups = array();
            foreach ( $enabled_providers as $provider_id => $provider ) {
                $groups[ $provider['group'] ][ $provider_id ] = $provider;
            }

            $group_labels = array(
                'bank' => __( 'Bank or card', 'wp-payment-hub' ),
                'direct_debit' => __( 'Direct Debit', 'wp-payment-hub' ),
                'wallet' => __( 'Wallets', 'wp-payment-hub' ),
            );
            $default_group = $this->get_option( 'default_group', 'bank' );
            if ( empty( $groups[ $default_group ] ) ) {
                $default_group = key( $groups );
            }
            $default_provider = $this->get_option( 'default_provider', '' );
            if ( empty( $groups[ $default_group ][ $default_provider ] ) ) {
                $default_provider = key( $groups[ $default_group ] );
            }

            echo '<div class="wp-payment-hub-checkout">';
            if ( count( $groups ) > 1 ) {
                echo '<div class="wp-payment-group-switcher" role="radiogroup" aria-label="' . esc_attr__( 'Payment type', 'wp-payment-hub' ) . '">';
                foreach ( $groups as $group_id => $providers ) {
                    echo '<label class="wp-payment-group-choice"><input type="radio" name="tfa_payment_group" value="' . esc_attr( $group_id ) . '" ' . checked( $group_id, $default_group, false ) . '> <span>' . esc_html( $group_labels[ $group_id ] ) . '</span></label>';
                }
                echo '</div>';
            } else {
                echo '<input type="hidden" name="tfa_payment_group" value="' . esc_attr( $default_group ) . '">';
            }

            foreach ( $groups as $group_id => $providers ) {
                $style = $group_id === $default_group ? '' : ' style="display:none"';
                echo '<div class="wp-provider-options" data-group="' . esc_attr( $group_id ) . '"' . $style . '>';
                foreach ( $providers as $provider_id => $provider ) {
                    $config = $this->get_provider_config( $provider_id );
                    $checked = $provider_id === $default_provider;
                    echo '<label class="wp-provider-option">';
                    echo '<input type="radio" name="tfa_payment_provider" value="' . esc_attr( $provider_id ) . '" data-group="' . esc_attr( $group_id ) . '" ' . checked( $checked, true, false ) . '> ';
                    if ( ! empty( $config['logo_id'] ) ) {
                        echo '<span class="wp-provider-logo-wrap">' . wp_get_attachment_image( $config['logo_id'], 'medium', false, array( 'class' => 'wp-provider-logo', 'alt' => $config['title'] ) ) . '</span>';
                    }
                    echo '<span class="wp-provider-option-content"><strong>' . esc_html( $config['title'] ) . '</strong>';
                    if ( ! empty( $config['description'] ) ) {
                        echo '<small>' . esc_html( $config['description'] ) . '</small>';
                    }
                    if ( 'manual' === $provider['flow'] ) {
                        echo '<em>' . esc_html__( 'Order placed on hold while the mandate is arranged.', 'wp-payment-hub' ) . '</em>';
                    } elseif ( 'stripe_checkout' === $provider['flow'] ) {
                        echo '<em>' . esc_html__( 'Apple Pay or Google Pay appears on Stripe Checkout when supported by the customer device.', 'wp-payment-hub' ) . '</em>';
                    }
                    echo '</span>';
                    if ( ! empty( $config['icon_id'] ) ) {
                        echo '<span class="wp-provider-icon-wrap">' . wp_get_attachment_image( $config['icon_id'], 'thumbnail', false, array( 'class' => 'wp-provider-icon', 'alt' => sprintf( __( '%s payment icon', 'wp-payment-hub' ), $config['title'] ) ) ) . '</span>';
                    }
                    echo '</label>';
                }
                if ( 'direct_debit' === $group_id ) {
                    echo '<label class="wp-dd-consent"><input type="checkbox" name="tfa_direct_debit_consent" value="yes"> <span>' . esc_html( $this->get_option( 'direct_debit_consent_text', __( 'I understand that a Direct Debit mandate must be completed and approved before collection.', 'wp-payment-hub' ) ) ) . '</span></label>';
                }
                echo '</div>';
            }
            echo '</div>';
            ?>
            <style>
                .wp-payment-group-switcher{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 14px}.wp-payment-group-choice input{position:absolute;opacity:0}.wp-payment-group-choice span{display:block;border:1px solid #c3c4c7;border-radius:8px;padding:8px 12px;background:#fff;cursor:pointer}.wp-payment-group-choice input:checked+span{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;background:#f0f6fc}.wp-provider-options{display:grid;gap:9px}.wp-provider-option{display:flex!important;align-items:flex-start;gap:9px;border:1px solid #dcdcde;border-radius:8px;padding:11px 12px;background:#fff;cursor:pointer}.wp-provider-option:has(input:checked){border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.wp-provider-option input{margin-top:4px}.wp-provider-logo-wrap{display:flex;align-items:center;justify-content:center;flex:0 0 88px;min-height:42px}.wp-provider-logo{display:block;max-width:82px!important;max-height:42px!important;width:auto!important;height:auto!important;object-fit:contain}.wp-provider-option-content{display:flex;flex:1 1 auto;min-width:0;flex-direction:column;gap:2px}.wp-provider-icon-wrap{display:flex;align-items:center;justify-content:flex-end;flex:0 0 58px;min-height:40px;margin-left:auto}.wp-provider-icon{display:block;max-width:54px!important;max-height:36px!important;width:auto!important;height:auto!important;object-fit:contain}.wp-provider-option-content small{font-size:12px;line-height:1.4}.wp-provider-option-content em{font-size:11px;color:#646970}.wp-dd-consent{display:flex!important;gap:8px;align-items:flex-start;background:#f6f7f7;border-radius:8px;padding:11px;margin-top:3px}.wp-dd-consent input{margin-top:4px}@media(max-width:520px){.wp-provider-option{flex-wrap:wrap}.wp-provider-logo-wrap{flex-basis:64px}.wp-provider-logo{max-width:60px!important}.wp-provider-option-content{flex-basis:calc(100% - 96px)}.wp-provider-icon-wrap{flex-basis:44px}.wp-provider-icon{max-width:42px!important}}
            </style>
            <script>
                (function($){
                    var $hub = $('.wp-payment-hub-checkout').last();
                    if (!$hub.length) return;
                    function showGroup(group){
                        $hub.find('.wp-provider-options').hide().filter('[data-group="' + group + '"]').show();
                        var $visible = $hub.find('.wp-provider-options[data-group="' + group + '"] input[name="tfa_payment_provider"]');
                        if (!$visible.filter(':checked').length && $visible.length) { $visible.first().prop('checked', true); }
                    }
                    $hub.on('change', 'input[name="tfa_payment_group"]', function(){ showGroup(this.value); });
                    var initial = $hub.find('input[name="tfa_payment_group"]:checked').val() || $hub.find('input[name="tfa_payment_group"]').val();
                    showGroup(initial);
                })(jQuery);
            </script>
            <?php
        }

        public function validate_fields() {
            $provider_id = isset( $_POST['tfa_payment_provider'] ) ? sanitize_key( wp_unslash( $_POST['tfa_payment_provider'] ) ) : ( isset( $_POST['universal_payment_provider'] ) ? sanitize_key( wp_unslash( $_POST['universal_payment_provider'] ) ) : '' );
            $enabled_providers = $this->get_enabled_providers();

            if ( empty( $provider_id ) || empty( $enabled_providers[ $provider_id ] ) ) {
                wc_add_notice( __( 'Please choose an available payment option.', 'wp-payment-hub' ), 'error' );
                return false;
            }

            $provider = $enabled_providers[ $provider_id ];
            if ( 'direct_debit' === $provider['group'] ) {
                $consent = isset( $_POST['tfa_direct_debit_consent'] ) ? sanitize_text_field( wp_unslash( $_POST['tfa_direct_debit_consent'] ) ) : '';
                if ( 'yes' !== $consent ) {
                    wc_add_notice( __( 'Please confirm the Direct Debit mandate statement.', 'wp-payment-hub' ), 'error' );
                    return false;
                }
            }

            if ( 'manual' === $provider['flow'] ) {
                return true;
            }

            $config = $this->get_provider_config( $provider_id );
            if ( 'stripe_checkout' === $provider['flow'] ) {
                if ( empty( $config['secret_key'] ) ) {
                    wc_add_notice( __( 'Apple Pay and Google Pay are temporarily unavailable. Please use another payment option.', 'wp-payment-hub' ), 'error' );
                    return false;
                }
                return true;
            }
            if ( empty( $config['gateway_url'] ) || empty( $config['store_id'] ) || empty( $config['shared_secret'] ) ) {
                wc_add_notice( sprintf( __( '%s is not fully configured. Please use another payment option or contact the store.', 'wp-payment-hub' ), $provider['name'] ), 'error' );
                return false;
            }

            return true;
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wc_add_notice( __( 'Invalid order.', 'wp-payment-hub' ), 'error' );
                return array( 'result' => 'failure' );
            }

            $provider_id = isset( $_POST['tfa_payment_provider'] ) ? sanitize_key( wp_unslash( $_POST['tfa_payment_provider'] ) ) : ( isset( $_POST['universal_payment_provider'] ) ? sanitize_key( wp_unslash( $_POST['universal_payment_provider'] ) ) : $this->get_option( 'default_provider', 'natwest' ) );
            $enabled_providers = $this->get_enabled_providers();
            if ( empty( $enabled_providers[ $provider_id ] ) ) {
                wc_add_notice( __( 'Selected payment option is not available.', 'wp-payment-hub' ), 'error' );
                return array( 'result' => 'failure' );
            }

            $provider = $enabled_providers[ $provider_id ];
            $merchant_transaction_id = $order->get_order_number() . '-' . wp_generate_password( 8, false, false );
            $order->update_meta_data( '_universal_payments_provider', $provider_id );
            $order->update_meta_data( '_tfa_payment_group', $provider['group'] );
            $order->update_meta_data( '_tfa_payment_flow', $provider['flow'] );
            $order->update_meta_data( '_universal_payments_merchant_transaction_id', $merchant_transaction_id );
            $order->save();

            if ( 'manual' === $provider['flow'] ) {
                $status = $this->get_option( 'manual_dd_order_status', 'on-hold' );
                $instructions = $this->get_manual_instructions( $provider_id );
                $order->update_status( $status, sprintf( __( 'Awaiting %s mandate confirmation. %s', 'wp-payment-hub' ), $provider['name'], $instructions ) );
                wc_reduce_stock_levels( $order_id );
                if ( WC()->cart ) {
                    WC()->cart->empty_cart();
                }
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }

            $config = $this->get_provider_config( $provider_id );
            if ( 'stripe_checkout' === $provider['flow'] ) {
                if ( empty( $config['secret_key'] ) ) {
                    wc_add_notice( __( 'Apple Pay and Google Pay are not fully configured.', 'wp-payment-hub' ), 'error' );
                    return array( 'result' => 'failure' );
                }
                $session = $this->create_stripe_checkout_session( $order, $config );
                if ( is_wp_error( $session ) ) {
                    $this->log( 'Stripe Checkout session error: ' . $session->get_error_message() );
                    wc_add_notice( __( 'The express wallet checkout could not be started. Please try again or use another payment option.', 'wp-payment-hub' ), 'error' );
                    return array( 'result' => 'failure' );
                }
                $order->update_status( 'pending', __( 'Awaiting Apple Pay or Google Pay payment through Stripe Checkout.', 'wp-payment-hub' ) );
                $order->update_meta_data( '_tfa_stripe_session_id', sanitize_text_field( $session['id'] ) );
                $order->update_meta_data( '_tfa_stripe_mode', $config['mode'] );
                $order->save();
                return array(
                    'result'   => 'success',
                    'redirect' => esc_url_raw( $session['url'] ),
                );
            }

            if ( empty( $config['store_id'] ) || empty( $config['shared_secret'] ) || empty( $config['gateway_url'] ) ) {
                wc_add_notice( sprintf( __( '%s is not fully configured.', 'wp-payment-hub' ), $provider['name'] ), 'error' );
                return array( 'result' => 'failure' );
            }

            $order->update_status( 'pending', sprintf( __( 'Awaiting %s payment.', 'wp-payment-hub' ), $provider['name'] ) );

            return array(
                'result'   => 'success',
                'redirect' => add_query_arg(
                    array(
                        'wc-api'   => 'wc_gateway_tfa_payment_hub_redirect',
                        'order_id' => $order->get_id(),
                        'key'      => $order->get_order_key(),
                    ),
                    home_url( '/' )
                ),
            );
        }

        public function render_redirect_form() {
            $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
            $key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
            $order    = wc_get_order( $order_id );

            if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) ) {
                wc_add_notice( __( 'Invalid payment request.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }

            $provider_id = $this->get_order_provider_id( $order );
            $config = $this->get_provider_config( $provider_id );
            if ( empty( $config['store_id'] ) || empty( $config['shared_secret'] ) || empty( $config['gateway_url'] ) ) {
                wc_add_notice( sprintf( __( '%s payment gateway is not fully configured.', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'] ), 'error' );
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }

            $currency_numeric = $this->get_currency_number( $order->get_currency() );
            if ( empty( $currency_numeric ) ) {
                wc_add_notice( __( 'This currency is not configured for universal payments.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }

            $transaction_time = gmdate( 'Y:m:d-H:i:s' );
            $charge_total     = wc_format_decimal( $order->get_total(), 2 );
            $merchant_txn_id  = $order->get_meta( '_universal_payments_merchant_transaction_id' );
            if ( empty( $merchant_txn_id ) ) {
                $merchant_txn_id = $order->get_meta( '_rack_group_merchant_transaction_id' );
            }
            if ( empty( $merchant_txn_id ) ) {
                $merchant_txn_id = $order->get_meta( '_universal_payments_legacy_merchant_transaction_id' );
            }
            if ( empty( $merchant_txn_id ) ) {
                $merchant_txn_id = $order->get_order_number() . '-' . wp_generate_password( 8, false, false );
                $order->update_meta_data( '_universal_payments_merchant_transaction_id', $merchant_txn_id );
                $order->save();
            }

            $hash = $this->create_hash( $config['store_id'], $transaction_time, $charge_total, $currency_numeric, $config['shared_secret'], $config['hash_encoding'] );

            $response_url = add_query_arg(
                array(
                    'wc-api'   => 'wc_gateway_tfa_payment_hub_response',
                    'order_id' => $order->get_id(),
                    'key'      => $order->get_order_key(),
                ),
                home_url( '/' )
            );
            $notify_url = add_query_arg( array( 'wc-api' => 'wc_gateway_tfa_payment_hub_notify' ), home_url( '/' ) );

            $fields = array(
                'txntype'                            => $this->get_option( 'transaction_type', 'sale' ),
                'oid'                                => $order->get_id(),
                'timezone'                           => 'Europe/London',
                'txndatetime'                        => $transaction_time,
                'hash_algorithm'                     => 'SHA256',
                'hash'                               => $hash,
                'storename'                          => $config['store_id'],
                'mode'                               => 'payonly',
                'checkoutoption'                     => 'combinedpage',
                'comments'                           => $this->providers[ $provider_id ]['name'] . ' WooCommerce TFA Payment Hub',
                'bcompany'                           => $order->get_billing_company(),
                'bname'                              => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                'baddr1'                             => $order->get_billing_address_1(),
                'baddr2'                             => $order->get_billing_address_2(),
                'bcity'                              => $order->get_billing_city(),
                'bstate'                             => $order->get_billing_state(),
                'bcountry'                           => $order->get_billing_country(),
                'bzip'                               => $order->get_billing_postcode(),
                'phone'                              => $order->get_billing_phone(),
                'email'                              => $order->get_billing_email(),
                'sname'                              => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
                'saddr1'                             => $order->get_shipping_address_1(),
                'saddr2'                             => $order->get_shipping_address_2(),
                'scity'                              => $order->get_shipping_city(),
                'sstate'                             => $order->get_shipping_state(),
                'scountry'                           => $order->get_shipping_country(),
                'szip'                               => $order->get_shipping_postcode(),
                'chargetotal'                        => $charge_total,
                'currency'                           => $currency_numeric,
                'merchantTransactionId'              => $merchant_txn_id,
                'responseFailURL'                    => $response_url,
                'responseSuccessURL'                 => $response_url,
                'transactionNotificationURL'         => $notify_url,
                'authenticateTransaction'            => 'true',
            );

            if ( 'yes' === $this->get_option( 'send_3ds_challenge_indicator', 'no' ) ) {
                $fields['threeDSRequestorChallengeIndicator'] = '01';
            }

            $required_fields = array(
                'txntype',
                'oid',
                'timezone',
                'txndatetime',
                'hash_algorithm',
                'hash',
                'storename',
                'mode',
                'chargetotal',
                'currency',
                'merchantTransactionId',
                'responseFailURL',
                'responseSuccessURL',
                'transactionNotificationURL',
            );
            $fields = $this->clean_gateway_fields( $fields, $required_fields );

            $missing = $this->missing_required_gateway_fields( $fields, $required_fields );
            if ( ! empty( $missing ) ) {
                $this->log( 'Payment redirect blocked. Missing required fields: ' . implode( ', ', $missing ) );
                wc_add_notice( sprintf( __( '%s payment request is missing required fields. Please check gateway settings.', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'] ), 'error' );
                wp_safe_redirect( wc_get_checkout_url() );
                exit;
            }

            $this->log( 'Redirect form provider ' . $provider_id . ': ' . wc_print_r( array_diff_key( $fields, array( 'hash' => true, 'shared_secret' => true ) ), true ) );

            nocache_headers();
            echo '<!doctype html><html><head><meta charset="utf-8"><title>' . esc_html__( 'Redirecting to payment...', 'wp-payment-hub' ) . '</title></head><body>';
            echo '<p>' . esc_html__( 'Redirecting to the secure payment page. Please wait...', 'wp-payment-hub' ) . '</p>';
            echo '<form id="wp-payment-hub-form" method="post" action="' . esc_url( $config['gateway_url'] ) . '">';
            foreach ( $fields as $name => $value ) {
                echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
            }
            echo '<noscript><button type="submit">' . esc_html__( 'Continue to payment', 'wp-payment-hub' ) . '</button></noscript>';
            echo '</form><script>document.getElementById("wp-payment-hub-form").submit();</script></body></html>';
            exit;
        }

        public function handle_response() {
            $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : ( isset( $_REQUEST['oid'] ) ? absint( $_REQUEST['oid'] ) : 0 );
            $key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
            $order    = wc_get_order( $order_id );

            if ( ! $order || ( $key && ! hash_equals( $order->get_order_key(), $key ) ) ) {
                wc_add_notice( __( 'Invalid payment response.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }

            $provider_id = $this->get_order_provider_id( $order );
            $valid = $this->verify_gateway_response( 'response_hash', $provider_id );
            $status = $this->request_value( 'status' );
            $approval_code = $this->request_value( 'approval_code' );
            $merchant_txn_id = $this->request_value( 'merchantTransactionId' );

            if ( $valid && 'APPROVED' === strtoupper( $status ) && 0 === strpos( strtoupper( $approval_code ), 'Y:' ) ) {
                if ( ! $order->is_paid() ) {
                    $order->payment_complete( $merchant_txn_id );
                    $order->add_order_note( sprintf( __( '%1$s payment approved. Approval code: %2$s', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'], $approval_code ) );
                    WC()->cart->empty_cart();
                }
                wp_safe_redirect( $this->get_return_url( $order ) );
                exit;
            }

            $message = $approval_code ? $approval_code : __( 'Payment was not approved.', 'wp-payment-hub' );
            $order->update_status( 'failed', sprintf( __( '%1$s payment failed or could not be verified. Message: %2$s', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'], $message ) );
            wc_add_notice( __( 'There was an issue with the payment. Please try again or use another payment method.', 'wp-payment-hub' ), 'error' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }

        public function handle_notification() {
            $order_id = isset( $_REQUEST['oid'] ) ? absint( $_REQUEST['oid'] ) : 0;
            $order    = wc_get_order( $order_id );

            if ( ! $order ) {
                status_header( 400 );
                exit;
            }

            $provider_id = $this->get_order_provider_id( $order );
            $valid = $this->verify_gateway_response( 'notification_hash', $provider_id );
            $status = $this->request_value( 'status' );
            $approval_code = $this->request_value( 'approval_code' );
            $merchant_txn_id = $this->request_value( 'merchantTransactionId' );

            if ( $valid && 'APPROVED' === strtoupper( $status ) && ( 0 === strpos( strtoupper( $approval_code ), 'Y:' ) || false !== stripos( $approval_code, 'waiting 3dsecure' ) ) ) {
                if ( ! $order->is_paid() ) {
                    $order->payment_complete( $merchant_txn_id );
                    $order->add_order_note( sprintf( __( '%1$s payment notification approved. Approval code: %2$s', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'], $approval_code ) );
                }
            } elseif ( $valid && ! $order->is_paid() ) {
                $order->update_status( 'failed', sprintf( __( '%1$s payment notification received but not approved. Message: %2$s', 'wp-payment-hub' ), $this->providers[ $provider_id ]['name'], $approval_code ) );
            }

            status_header( 200 );
            exit;
        }


        public function get_blocks_payment_method_data() {
            $providers = $this->get_checkout_providers();
            $groups = array();
            foreach ( $providers as $provider_id => $provider ) {
                $config = $this->get_provider_config( $provider_id );
                if ( ! isset( $groups[ $provider['group'] ] ) ) {
                    $groups[ $provider['group'] ] = array();
                }
                $groups[ $provider['group'] ][ $provider_id ] = array(
                    'id'          => $provider_id,
                    'name'        => $provider['name'],
                    'title'       => $config['title'],
                    'description' => $config['description'],
                    'logo_url'    => $config['logo_id'] ? wp_get_attachment_image_url( $config['logo_id'], 'medium' ) : '',
                    'logo_alt'    => $config['title'],
                    'icon_url'    => $config['icon_id'] ? wp_get_attachment_image_url( $config['icon_id'], 'thumbnail' ) : '',
                    'icon_alt'    => sprintf( __( '%s payment icon', 'wp-payment-hub' ), $config['title'] ),
                    'group'       => $provider['group'],
                    'flow'        => $provider['flow'],
                );
            }

            $default_group = $this->get_option( 'default_group', 'bank' );
            if ( empty( $groups[ $default_group ] ) && ! empty( $groups ) ) {
                $default_group = key( $groups );
            }
            $default_provider = $this->get_option( 'default_provider', '' );
            if ( ! empty( $groups[ $default_group ] ) && empty( $groups[ $default_group ][ $default_provider ] ) ) {
                $default_provider = key( $groups[ $default_group ] );
            }

            return array(
                'title'              => $this->title,
                'description'        => $this->description,
                'groups'             => $groups,
                'group_labels'       => array(
                    'bank'         => __( 'Bank or card', 'wp-payment-hub' ),
                    'direct_debit' => __( 'Direct Debit', 'wp-payment-hub' ),
                    'wallet'       => __( 'Wallets', 'wp-payment-hub' ),
                ),
                'default_group'      => $default_group,
                'default_provider'   => $default_provider,
                'direct_debit_text'  => $this->get_option( 'direct_debit_consent_text', __( 'I understand that a Direct Debit mandate must be completed and approved before collection.', 'wp-payment-hub' ) ),
                'supports'           => array_values( array_filter( $this->supports, array( $this, 'supports' ) ) ),
                'available'          => ! empty( $providers ),
            );
        }

        private function stripe_api_request( $method, $path, $secret_key, $body = array() ) {
            $args = array(
                'method'      => strtoupper( $method ),
                'timeout'     => 30,
                'redirection' => 0,
                'headers'     => array(
                    'Authorization' => 'Bearer ' . $secret_key,
                ),
            );
            if ( ! empty( $body ) ) {
                $args['body'] = $body;
            }

            $response = wp_remote_request( 'https://api.stripe.com' . $path, $args );
            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
                $message = isset( $data['error']['message'] ) ? sanitize_text_field( $data['error']['message'] ) : __( 'Stripe returned an unexpected response.', 'wp-payment-hub' );
                return new WP_Error( 'tfa_stripe_error', $message );
            }
            return $data;
        }

        private function create_stripe_checkout_session( $order, $config ) {
            $success_url = add_query_arg(
                array(
                    'wc-api'    => 'wc_gateway_tfa_payment_hub_stripe_return',
                    'order_id'  => $order->get_id(),
                    'key'       => $order->get_order_key(),
                    'session_id'=> 'TFA_STRIPE_SESSION_ID',
                ),
                home_url( '/' )
            );
            $success_url = str_replace( 'TFA_STRIPE_SESSION_ID', '{CHECKOUT_SESSION_ID}', $success_url );

            $body = array(
                'mode'                                             => 'payment',
                'success_url'                                      => $success_url,
                'cancel_url'                                       => $order->get_cancel_order_url_raw(),
                'client_reference_id'                              => (string) $order->get_id(),
                'customer_email'                                   => $order->get_billing_email(),
                'payment_method_types[0]'                          => 'card',
                'line_items[0][price_data][currency]'              => strtolower( $order->get_currency() ),
                'line_items[0][price_data][product_data][name]'    => sprintf( __( 'Order #%s', 'wp-payment-hub' ), $order->get_order_number() ),
                'line_items[0][price_data][unit_amount]'           => $this->get_stripe_minor_amount( $order->get_total(), $order->get_currency() ),
                'line_items[0][quantity]'                          => 1,
                'metadata[order_id]'                               => (string) $order->get_id(),
                'metadata[payment_method]'                         => 'apple_google_pay',
                'payment_intent_data[metadata][order_id]'          => (string) $order->get_id(),
                'payment_intent_data[description]'                 => sprintf( __( 'WooCommerce order #%s', 'wp-payment-hub' ), $order->get_order_number() ),
                'locale'                                           => 'auto',
                'submit_type'                                      => 'pay',
            );

            return $this->stripe_api_request( 'POST', '/v1/checkout/sessions', $config['secret_key'], $body );
        }

        public function handle_stripe_return() {
            $order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
            $key       = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
            $session_id= isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';
            $order     = wc_get_order( $order_id );

            if ( ! $order || empty( $session_id ) || ! hash_equals( $order->get_order_key(), $key ) ) {
                wc_add_notice( __( 'The express wallet response could not be verified.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_checkout_url() );
                exit;
            }

            $secret_key = $this->get_stripe_order_credential( $order, 'secret_key' );
            if ( empty( $secret_key ) ) {
                wc_add_notice( __( 'The express wallet connection is unavailable.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_checkout_url() );
                exit;
            }

            $session = $this->stripe_api_request( 'GET', '/v1/checkout/sessions/' . rawurlencode( $session_id ), $secret_key );
            if ( is_wp_error( $session ) || ! $this->stripe_session_matches_order( $session, $order ) ) {
                $this->log( 'Stripe return verification failed for order ' . $order_id );
                wc_add_notice( __( 'The express wallet payment could not be verified.', 'wp-payment-hub' ), 'error' );
                wp_safe_redirect( wc_get_checkout_url() );
                exit;
            }

            if ( 'paid' === ( isset( $session['payment_status'] ) ? $session['payment_status'] : '' ) ) {
                $this->complete_stripe_order( $order, $session, __( 'Stripe Checkout return verified.', 'wp-payment-hub' ) );
                if ( WC()->cart ) {
                    WC()->cart->empty_cart();
                }
                wp_safe_redirect( $this->get_return_url( $order ) );
                exit;
            }

            $order->add_order_note( __( 'Stripe Checkout returned before payment was confirmed. The order remains pending.', 'wp-payment-hub' ) );
            wc_add_notice( __( 'Your payment is still being confirmed. Please check the order status shortly.', 'wp-payment-hub' ), 'notice' );
            wp_safe_redirect( $this->get_return_url( $order ) );
            exit;
        }

        public function handle_stripe_webhook() {
            $payload = file_get_contents( 'php://input' );
            $event = json_decode( $payload, true );
            $session = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();
            $order_id = ! empty( $session['metadata']['order_id'] ) ? absint( $session['metadata']['order_id'] ) : ( ! empty( $session['client_reference_id'] ) ? absint( $session['client_reference_id'] ) : 0 );
            $order = $order_id ? wc_get_order( $order_id ) : false;

            if ( ! $order ) {
                status_header( 400 );
                exit;
            }

            $webhook_secret = $this->get_stripe_order_credential( $order, 'webhook_secret' );
            $signature = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? trim( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';
            if ( empty( $webhook_secret ) || ! $this->verify_stripe_signature( $payload, $signature, $webhook_secret ) ) {
                $this->log( 'Stripe webhook signature failed for order ' . $order_id );
                status_header( 400 );
                exit;
            }

            if ( ! $this->stripe_session_matches_order( $session, $order ) ) {
                $this->log( 'Stripe webhook order verification failed for order ' . $order_id );
                status_header( 400 );
                exit;
            }

            $event_type = isset( $event['type'] ) ? sanitize_text_field( $event['type'] ) : '';
            if ( in_array( $event_type, array( 'checkout.session.completed', 'checkout.session.async_payment_succeeded' ), true ) && 'paid' === ( isset( $session['payment_status'] ) ? $session['payment_status'] : '' ) ) {
                $this->complete_stripe_order( $order, $session, sprintf( __( 'Stripe webhook confirmed %s.', 'wp-payment-hub' ), $event_type ) );
            } elseif ( 'checkout.session.async_payment_failed' === $event_type && ! $order->is_paid() ) {
                $order->update_status( 'failed', __( 'Stripe reported that the express wallet payment failed.', 'wp-payment-hub' ) );
            }

            status_header( 200 );
            exit;
        }

        private function complete_stripe_order( $order, $session, $note ) {
            if ( $order->is_paid() ) {
                return;
            }
            $transaction_id = ! empty( $session['payment_intent'] ) ? sanitize_text_field( $session['payment_intent'] ) : ( ! empty( $session['id'] ) ? sanitize_text_field( $session['id'] ) : '' );
            $order->payment_complete( $transaction_id );
            $order->add_order_note( $note );
        }

        private function stripe_session_matches_order( $session, $order ) {
            if ( ! is_array( $session ) ) {
                return false;
            }
            $session_order_id = ! empty( $session['metadata']['order_id'] ) ? absint( $session['metadata']['order_id'] ) : ( ! empty( $session['client_reference_id'] ) ? absint( $session['client_reference_id'] ) : 0 );
            if ( $session_order_id !== (int) $order->get_id() ) {
                return false;
            }
            $expected_amount = $this->get_stripe_minor_amount( $order->get_total(), $order->get_currency() );
            $actual_amount = isset( $session['amount_total'] ) ? (int) $session['amount_total'] : -1;
            $actual_currency = isset( $session['currency'] ) ? strtolower( sanitize_text_field( $session['currency'] ) ) : '';
            return $expected_amount === $actual_amount && hash_equals( strtolower( $order->get_currency() ), $actual_currency );
        }

        private function verify_stripe_signature( $payload, $signature_header, $secret ) {
            if ( empty( $payload ) || empty( $signature_header ) || empty( $secret ) ) {
                return false;
            }
            $parts = array();
            foreach ( explode( ',', $signature_header ) as $item ) {
                $pair = array_map( 'trim', explode( '=', $item, 2 ) );
                if ( 2 === count( $pair ) ) {
                    $parts[ $pair[0] ][] = $pair[1];
                }
            }
            $timestamp = ! empty( $parts['t'][0] ) ? absint( $parts['t'][0] ) : 0;
            if ( ! $timestamp || abs( time() - $timestamp ) > 300 || empty( $parts['v1'] ) ) {
                return false;
            }
            $expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
            foreach ( $parts['v1'] as $signature ) {
                if ( hash_equals( $expected, strtolower( $signature ) ) ) {
                    return true;
                }
            }
            return false;
        }

        private function get_stripe_order_credential( $order, $credential ) {
            $mode = $order->get_meta( '_tfa_stripe_mode' );
            if ( ! in_array( $mode, array( 'test', 'live' ), true ) ) {
                $mode = $this->get_option( 'apple_google_pay_payment_mode', 'test' );
            }
            return trim( (string) $this->get_option( 'apple_google_pay_' . $mode . '_' . $credential, '' ) );
        }

        private function get_stripe_minor_amount( $amount, $currency ) {
            $currency = strtoupper( $currency );
            $zero_decimal = array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' );
            $three_decimal = array( 'BHD', 'JOD', 'KWD', 'OMR', 'TND' );
            $factor = in_array( $currency, $zero_decimal, true ) ? 1 : ( in_array( $currency, $three_decimal, true ) ? 1000 : 100 );
            return (int) round( (float) $amount * $factor );
        }

        public function is_available() {
            if ( ! parent::is_available() ) {
                return false;
            }
            return ! empty( $this->get_checkout_providers() );
        }

        public function display_manual_instructions( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order || $this->id !== $order->get_payment_method() || 'manual' !== $order->get_meta( '_tfa_payment_flow' ) ) {
                return;
            }
            $provider_id = $this->get_order_provider_id( $order );
            echo '<section class="woocommerce-order-details wp-manual-dd-instructions"><h2>' . esc_html__( 'Direct Debit instructions', 'wp-payment-hub' ) . '</h2><p>' . wp_kses_post( nl2br( $this->get_manual_instructions( $provider_id ) ) ) . '</p></section>';
        }

        public function email_manual_instructions( $order, $sent_to_admin, $plain_text ) {
            if ( $sent_to_admin || ! ( $order instanceof WC_Order ) || $this->id !== $order->get_payment_method() || 'manual' !== $order->get_meta( '_tfa_payment_flow' ) ) {
                return;
            }
            $provider_id = $this->get_order_provider_id( $order );
            $instructions = $this->get_manual_instructions( $provider_id );
            if ( $plain_text ) {
                echo "\n" . esc_html__( 'Direct Debit instructions:', 'wp-payment-hub' ) . "\n" . wp_strip_all_tags( $instructions ) . "\n";
            } else {
                echo '<h2>' . esc_html__( 'Direct Debit instructions', 'wp-payment-hub' ) . '</h2><p>' . wp_kses_post( nl2br( $instructions ) ) . '</p>';
            }
        }

        private function get_manual_instructions( $provider_id ) {
            $config = $this->get_provider_config( $provider_id );
            if ( ! empty( $config['manual_instructions'] ) ) {
                return $config['manual_instructions'];
            }
            return $this->get_option( 'manual_dd_instructions', __( 'We will contact you with the approved Direct Debit mandate instructions.', 'wp-payment-hub' ) );
        }

        private function clean_gateway_fields( $fields, $required_fields ) {
            $clean = array();
            foreach ( $fields as $key => $value ) {
                if ( is_array( $value ) ) {
                    continue;
                }
                $value = trim( (string) $value );
                if ( '' === $value && ! in_array( $key, $required_fields, true ) ) {
                    continue;
                }
                $clean[ $key ] = $value;
            }
            return $clean;
        }

        private function missing_required_gateway_fields( $fields, $required_fields ) {
            $missing = array();
            foreach ( $required_fields as $field ) {
                if ( ! isset( $fields[ $field ] ) || '' === trim( (string) $fields[ $field ] ) ) {
                    $missing[] = $field;
                }
            }
            return $missing;
        }

        private function get_checkout_providers() {
            $available = array();
            foreach ( $this->get_enabled_providers() as $provider_id => $provider ) {
                if ( 'manual' === $provider['flow'] ) {
                    $available[ $provider_id ] = $provider;
                    continue;
                }
                $config = $this->get_provider_config( $provider_id );
                if ( 'stripe_checkout' === $provider['flow'] ) {
                    if ( ! empty( $config['secret_key'] ) ) {
                        $available[ $provider_id ] = $provider;
                    }
                    continue;
                }
                if ( ! empty( $config['gateway_url'] ) && ! empty( $config['store_id'] ) && ! empty( $config['shared_secret'] ) ) {
                    $available[ $provider_id ] = $provider;
                }
            }
            return $available;
        }

        private function get_enabled_providers() {
            $enabled = array();
            $group_settings = array(
                'bank' => 'enable_bank',
                'direct_debit' => 'enable_direct_debit',
                'wallet' => 'enable_wallet',
            );
            foreach ( $this->providers as $provider_id => $provider ) {
                $group_setting = $group_settings[ $provider['group'] ];
                $group_default = 'bank' === $provider['group'] ? 'yes' : 'no';
                if ( 'yes' !== $this->get_option( $group_setting, $group_default ) ) {
                    continue;
                }
                if ( 'yes' === $this->get_option( $provider_id . '_enabled', 'no' ) ) {
                    $enabled[ $provider_id ] = $provider;
                }
            }
            return $enabled;
        }

        private function get_provider_config( $provider_id ) {
            if ( empty( $this->providers[ $provider_id ] ) ) {
                $provider_id = 'natwest';
            }
            $provider = $this->providers[ $provider_id ];
            $mode = $this->get_option( $provider_id . '_payment_mode', 'test' );
            $config = array(
                'provider_id'        => $provider_id,
                'enabled'            => $this->get_option( $provider_id . '_enabled', 'no' ),
                'title'              => $this->get_option( $provider_id . '_title', $provider['default_title'] ),
                'description'        => $this->get_option( $provider_id . '_description', $provider['default_description'] ),
                'logo_id'            => absint( $this->get_option( $provider_id . '_logo_id', 0 ) ),
                'icon_id'            => absint( $this->get_option( $provider_id . '_icon_id', 0 ) ),
                'group'              => $provider['group'],
                'flow'               => $provider['flow'],
                'mode'               => $mode,
                'gateway_url'        => trim( (string) $this->get_option( $provider_id . '_' . $mode . '_gateway_url' ) ),
                'store_id'           => trim( (string) $this->get_option( $provider_id . '_' . $mode . '_store_id' ) ),
                'shared_secret'      => trim( (string) $this->get_option( $provider_id . '_' . $mode . '_shared_secret' ) ),
                'hash_encoding'      => $this->get_option( $provider_id . '_hash_encoding', 'hex' ),
                'manual_instructions'=> $this->get_option( $provider_id . '_manual_instructions', '' ),
                'secret_key'         => trim( (string) $this->get_option( $provider_id . '_' . $mode . '_secret_key', '' ) ),
                'webhook_secret'     => trim( (string) $this->get_option( $provider_id . '_' . $mode . '_webhook_secret', '' ) ),
            );

            if ( 'natwest' === $provider_id && ( empty( $config['gateway_url'] ) || empty( $config['store_id'] ) || empty( $config['shared_secret'] ) ) ) {
                $legacy = get_option( 'woocommerce_rack_group_gateway_settings', array() );
                if ( is_array( $legacy ) ) {
                    $legacy_mode = isset( $legacy['payment_mode'] ) ? $legacy['payment_mode'] : $mode;
                    $config['mode'] = $legacy_mode;
                    if ( empty( $config['gateway_url'] ) && ! empty( $legacy[ $legacy_mode . '_gateway_url' ] ) ) {
                        $config['gateway_url'] = trim( $legacy[ $legacy_mode . '_gateway_url' ] );
                    }
                    if ( empty( $config['store_id'] ) && ! empty( $legacy[ $legacy_mode . '_store_id' ] ) ) {
                        $config['store_id'] = trim( $legacy[ $legacy_mode . '_store_id' ] );
                    }
                    if ( empty( $config['shared_secret'] ) && ! empty( $legacy[ $legacy_mode . '_shared_secret' ] ) ) {
                        $config['shared_secret'] = trim( $legacy[ $legacy_mode . '_shared_secret' ] );
                    }
                }
            }

            return $config;
        }

        private function get_order_provider_id( $order ) {
            $provider_id = $order->get_meta( '_universal_payments_provider' );
            if ( empty( $provider_id ) ) {
                $provider_id = 'natwest';
            }
            if ( empty( $this->providers[ $provider_id ] ) ) {
                $provider_id = 'natwest';
            }
            return $provider_id;
        }

        private function create_hash( $store_id, $transaction_time, $charge_total, $currency, $shared_secret, $hash_encoding = 'hex' ) {
            $string_to_hash = $store_id . $transaction_time . $charge_total . $currency . $shared_secret;
            return $this->sha256_hash( $string_to_hash, $hash_encoding );
        }

        private function sha256_hash( $string_to_hash, $hash_encoding = 'hex' ) {
            if ( 'raw' === $hash_encoding ) {
                return hash( 'sha256', $string_to_hash );
            }
            return hash( 'sha256', bin2hex( $string_to_hash ) );
        }

        private function verify_gateway_response( $hash_field, $provider_id ) {
            $provided_hash = $this->request_value( $hash_field );
            if ( empty( $provided_hash ) ) {
                $this->log( 'Missing ' . $hash_field );
                return false;
            }

            $config = $this->get_provider_config( $provider_id );
            $approval_code = $this->request_value( 'approval_code' );
            $charge_total  = $this->request_value( 'chargetotal' );
            $currency      = $this->request_value( 'currency' );
            $txn_datetime  = $this->request_value( 'txndatetime' );
            $order_id      = absint( $this->request_value( 'oid' ) );
            $order         = $order_id ? wc_get_order( $order_id ) : false;

            if ( $order ) {
                $expected_total = number_format( (float) $order->get_total(), 2, '.', '' );
                $expected_currency = $this->get_currency_number( $order->get_currency() );
                if ( ! hash_equals( $expected_total, number_format( (float) $charge_total, 2, '.', '' ) ) || ! hash_equals( (string) $expected_currency, (string) $currency ) ) {
                    $this->log( 'Amount or currency verification failed for provider ' . $provider_id . ' / order ' . $order_id );
                    return false;
                }
            }

            if ( 'response_hash' === $hash_field ) {
                $string_to_hash = $config['shared_secret'] . $approval_code . $charge_total . $currency . $txn_datetime . $config['store_id'];
            } else {
                $string_to_hash = $charge_total . $config['shared_secret'] . $currency . $txn_datetime . $config['store_id'] . $approval_code;
            }

            $expected = $this->sha256_hash( $string_to_hash, $config['hash_encoding'] );
            $valid = hash_equals( strtolower( $expected ), strtolower( $provided_hash ) );
            if ( ! $valid ) {
                $this->log( 'Hash verification failed for ' . $hash_field . ' / provider ' . $provider_id );
            }
            return $valid;
        }

        private function request_value( $key ) {
            return isset( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : '';
        }

        private function get_currency_number( $currency_code ) {
            $currencies = array(
                'GBP' => '826', 'EUR' => '978', 'USD' => '840', 'AED' => '784', 'ARS' => '032', 'AUD' => '036',
                'CAD' => '124', 'CHF' => '756', 'DKK' => '208', 'HKD' => '344', 'JPY' => '392', 'NOK' => '578',
                'NZD' => '554', 'SEK' => '752', 'SGD' => '702', 'ZAR' => '710', 'PLN' => '985', 'CZK' => '203',
                'HUF' => '348', 'RON' => '946', 'BGN' => '975', 'ISK' => '352', 'NIO' => '558', 'TRY' => '949',
            );
            return isset( $currencies[ $currency_code ] ) ? $currencies[ $currency_code ] : '';
        }

        private function log( $message ) {
            if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
                return;
            }
            $logger = wc_get_logger();
            $logger->info( $message, array( 'source' => 'wp-payment-hub' ) );
        }
    }

    add_filter( 'woocommerce_payment_gateways', 'tfa_payment_hub_add_gateway' );
    function tfa_payment_hub_add_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_TFA_Payment_Hub';
        return $gateways;
    }
}


add_action( 'woocommerce_blocks_loaded', 'tfa_payment_hub_blocks_loaded' );
function tfa_payment_hub_blocks_loaded() {
    if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    class TFA_Payment_Hub_Blocks_Support extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'universal_payments_gateway';
        private $gateway;

        public function initialize() {
            $this->settings = get_option( 'woocommerce_universal_payments_gateway_settings', array() );
            if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
                $gateways = WC()->payment_gateways()->payment_gateways();
                if ( isset( $gateways[ $this->name ] ) ) {
                    $this->gateway = $gateways[ $this->name ];
                }
            }
        }

        public function is_active() {
            if ( ! $this->gateway || 'yes' !== $this->get_setting( 'enabled', 'no' ) ) {
                return false;
            }
            $data = $this->gateway->get_blocks_payment_method_data();
            return ! empty( $data['available'] );
        }

        public function get_payment_method_script_handles() {
            $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/checkout-blocks.js';
            $style_path  = plugin_dir_path( __FILE__ ) . 'assets/css/checkout-blocks.css';
            wp_register_script(
                'wp-payment-hub-blocks',
                plugins_url( 'assets/js/checkout-blocks.js', __FILE__ ),
                array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
                file_exists( $script_path ) ? (string) filemtime( $script_path ) : '2.3.0',
                true
            );
            wp_register_style(
                'wp-payment-hub-blocks',
                plugins_url( 'assets/css/checkout-blocks.css', __FILE__ ),
                array(),
                file_exists( $style_path ) ? (string) filemtime( $style_path ) : '2.3.0'
            );
            wp_enqueue_style( 'wp-payment-hub-blocks' );
            return array( 'wp-payment-hub-blocks' );
        }

        public function get_payment_method_data() {
            if ( ! $this->gateway || ! method_exists( $this->gateway, 'get_blocks_payment_method_data' ) ) {
                return array( 'available' => false );
            }
            return $this->gateway->get_blocks_payment_method_data();
        }
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( $payment_method_registry ) {
            $payment_method_registry->register( new TFA_Payment_Hub_Blocks_Support() );
        }
    );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tfa_payment_hub_action_links' );
function tfa_payment_hub_action_links( $links ) {
    $settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=universal_payments_gateway' );
    array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wp-payment-hub' ) . '</a>' );
    return $links;
}
