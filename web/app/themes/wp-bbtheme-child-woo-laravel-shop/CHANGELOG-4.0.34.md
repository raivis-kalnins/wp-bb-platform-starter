# Child theme 4.0.34 - mini-cart layout repair

Based on the supplied 4.0.33 child theme; Latvian language-continuity changes remain.

- Use explicit component-scoped thumbnail/copy grid placement in the slide-out cart.
- Ignore structural line breaks introduced around cart markup; compact only the cart shortcode output, not all page content.
- Use a div action wrapper and an explicit 10px gap between View cart and Checkout.
- Show formatted variation attributes and keep the cart heading clear of the WordPress toolbar.
- No homepage hero, global typography, product inventory or translation database replacement.

Test on staging with the coordinated WP Theme Woo Support 3.8.6 and WP BBuilder 5.9.0 update. The browser tests are isolated fixtures, not live-site verification.
