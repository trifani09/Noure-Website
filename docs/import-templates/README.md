# Product import templates

Upload the three related tables through Noure Admin. `product_reference` is an import-only key joining product, variant, and image rows. It is never stored as a database relationship.

- `products.csv`: one row per product. `category` is an existing category slug.
- `variants.csv`: one or more rows per product. `option_values` uses `option=value` pairs separated by `|`, for example `color=cream|size=m`. Money is an integer in minor units.
- `images.csv`: optional image rows. `file_path` is either an existing safe path under the public `products/` disk or the exact filename of an image included with the upload. `variant_sku` may be blank for product-level media.

CSV and XLSX files use the same headers. XLSX imports read the first worksheet. Boolean values accept `true`, `yes`, `1`, or `active`.
