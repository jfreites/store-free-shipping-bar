# Store Free Shipping Bar for WooCommerce

A reusable WooCommerce plugin that displays a dynamic "free shipping" progress bar to encourage higher cart values and improve conversion rates.

The plugin is designed to stay generic and portable, so it can be reused across different WordPress + WooCommerce sites without depending on a specific theme.

## Features

- Calculates the current cart subtotal without shipping.
- Compares the subtotal against a configurable free shipping threshold.
- Displays dynamic customer-facing messages:
  - `You're only $X away from free shipping`
  - `You already have free shipping`
- Updates automatically without a full page reload.
- Works with WooCommerce cart fragments.
- Can be rendered via:
  - WooCommerce hooks
  - shortcode
  - reusable PHP function
- Includes a settings page under WooCommerce.
- Includes optional milestone support for bonus thresholds such as gifts or perks.
- Includes an empty-cart fallback state.

## Plugin Structure

```text
store-free-shipping-bar/
├── assets/
│   ├── css/free-shipping-bar.css
│   └── js/free-shipping-bar.js
├── includes/
│   ├── class-sfsb-plugin.php
│   ├── class-sfsb-progress.php
│   ├── class-sfsb-renderer.php
│   └── class-sfsb-settings.php
├── README.md
└── store-free-shipping-bar.php
```

## Installation

1. Copy the `store-free-shipping-bar` folder into:

   ```text
   wp-content/plugins/
   ```

2. In WordPress admin, go to `Plugins`.
3. Activate `Store Free Shipping Bar for WooCommerce`.
4. Go to:

   ```text
   WooCommerce > Free Shipping Bar
   ```

5. Set the minimum amount required for free shipping.

## Settings

The plugin stores its settings in `wp_options` under:

```text
sfsb_settings
```

Available settings include:

- Free shipping threshold
- In-progress message
- Completed message
- Empty cart message
- Display on cart page
- Display on mini cart hook
- Optional milestone definitions

### Milestones Format

Milestones are optional bonus thresholds. Add one milestone per line using this format:

```text
80|Free gift
120|VIP sample pack
```

The amount appears as a marker on the bar and becomes active once the subtotal reaches that threshold.

## Rendering Methods

### 1. WooCommerce Hook

The plugin renders automatically in these default WooCommerce locations:

- `woocommerce_before_cart`
- `woocommerce_before_mini_cart`

These can be toggled from the plugin settings page.

### 2. Shortcode

Use the shortcode anywhere in WordPress content, widgets, templates, or builder blocks:

```shortcode
[free_shipping_bar]
```

You can also pass optional attributes:

```shortcode
[free_shipping_bar context="drawer" threshold="75" class="custom-free-shipping-bar"]
```

Supported attributes:

- `context`
- `threshold`
- `class`

### 3. PHP Function

Use this helper function inside theme or plugin templates:

```php
<?php
if ( function_exists( 'wc_free_shipping_bar' ) ) {
	wc_free_shipping_bar(
		array(
			'context' => 'drawer',
			'class'   => 'cart-drawer-free-shipping',
		)
	);
}
?>
```

## Example: Cart Drawer Integration

If your theme uses a custom cart drawer/sidebar, insert the function call inside the drawer template where you want the bar to appear.

Example:

```php
<?php
if ( function_exists( 'wc_free_shipping_bar' ) ) {
	wc_free_shipping_bar(
		array(
			'context' => 'drawer',
			'class'   => 'cart-drawer-free-shipping',
		)
	);
}
?>
```

A common placement is:

- above the subtotal
- above the checkout button
- near the top of the cart drawer content

## JavaScript Behavior

The plugin updates itself dynamically using AJAX and listens to WooCommerce-related frontend events, including:

- `added_to_cart`
- `removed_from_cart`
- `updated_cart_totals`
- `wc_fragments_loaded`
- `wc_fragments_refreshed`
- `updated_wc_div`

This allows the bar to stay in sync with cart changes without forcing a full page refresh.

## Styling and Theme Overrides

The plugin includes neutral default styles that are intentionally easy to override from a theme.

### CSS Variables

You can customize the look from your theme by overriding these variables:

```css
.sfsb-free-shipping-bar {
	--sfsb-bg: #f6f2ee;
	--sfsb-border: #dfd5cb;
	--sfsb-text: #201a17;
	--sfsb-muted: #6e625a;
	--sfsb-track: #e9e1da;
	--sfsb-fill: #1f8f63;
	--sfsb-fill-complete: #0f6f4b;
	--sfsb-marker: #201a17;
	--sfsb-radius: 16px;
	--sfsb-height: 10px;
}
```

### Theme Override Example

```css
.sfsb-free-shipping-bar {
	--sfsb-bg: #111111;
	--sfsb-border: #2b2b2b;
	--sfsb-text: #f5f1ea;
	--sfsb-muted: #b7aa9a;
	--sfsb-track: #2a241f;
	--sfsb-fill: #c49b6c;
	--sfsb-fill-complete: #8ecf7a;
	--sfsb-marker: #f5f1ea;
}
```

To target only a specific location such as a cart drawer:

```css
.cart-drawer .sfsb-free-shipping-bar,
.cart-drawer-free-shipping {
	--sfsb-bg: #181818;
	--sfsb-fill: #d4af37;
}
```

## Messages

Default messages can be changed from the settings page.

Notes:

- The "remaining amount" message should include `%s` so the amount can be injected dynamically.
- Example:

  ```text
  You're only %s away from free shipping
  ```

## Empty Cart Fallback

When the cart is empty, the plugin displays the configured empty-cart message and resets the progress bar state.

## Developer Notes

- Main settings option key: `sfsb_settings`
- Main shortcode: `[free_shipping_bar]`
- Main PHP function: `wc_free_shipping_bar()`
- Main CSS class: `.sfsb-free-shipping-bar`

## Requirements

- WordPress
- WooCommerce active

## Validation

Recommended checks after installation:

1. Add a product via AJAX on shop or product page.
2. Confirm the bar updates automatically.
3. Increase or decrease quantity in cart.
4. Confirm the progress bar and message update correctly.
5. Confirm the completed state appears once the threshold is reached.
6. Confirm the empty state appears when the cart is cleared.

## License

GPL-2.0-or-later
