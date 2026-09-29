# Large WooCommerce and custom database systems

WooCommerce should own commerce concepts: products, carts, checkout, customers, orders, coupons, tax and payment integrations.

Do not turn WooCommerce or ACF into a warehouse/ERP database when the project needs high-volume transactional data.

## Example warehouse domain

Use tables such as:

```text
wp_bb_warehouses
wp_bb_inventory
wp_bb_inventory_movements
wp_bb_suppliers
wp_bb_purchase_orders
wp_bb_purchase_order_items
wp_bb_batches
wp_bb_serial_numbers
```

Create them with Acorn/Laravel migrations and access them through Eloquent/repositories/services. Expose only the necessary management screens in wp-admin.

## Medicine/pharmacy

Editorial provider/clinic content can live in normal WordPress/ACF structures. Inventory, pharmacy catalogue integrations and operational workflows should live in domain plugins/custom tables when necessary.

Do not use a normal WordPress installation as a clinical patient-record or prescription-record system without a separate security/compliance architecture designed for that data.
