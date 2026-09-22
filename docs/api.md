# Noure catalog API contract

## Status and scope

This document defines the version 1 HTTP/JSON contract for Noure's existing public and admin catalog APIs. [`database.md`](./database.md) is authoritative for the data model. This phase is documentation only: it does not implement application code, authentication, storage, or UI.

The running v1 application also exposes authenticated customer account endpoints described below.

Covered here are public category/product reads, admin category/product management, variants, and inventory. Cart, checkout, orders, payments, discounts, banners, uploads, restore, and permanent purge are outside this contract.

## Versioning and transport

All routes begin with `/api/v1`. Public routes use `/api/v1/...`; admin routes use `/api/v1/admin/...`. Breaking changes require a new major URL version; additive fields may be introduced within v1.

## Implemented commerce lifecycle extension

The running v1 application also exposes checkout/order/payment endpoints beyond the original catalog-only scope of this document:

- `POST /api/v1/orders` creates an order and reserves inventory.
- `POST /api/v1/orders/{order_public_id}/payment` and `GET /api/v1/orders/{order_public_id}/payment` manage the customer's Midtrans payment attempt.
- `POST /api/v1/payments/webhook` verifies and applies Midtrans notifications. `paid` converts reservations to sale; `failed`, `expired`, and `cancelled` release reservations.
- `PUT /api/v1/admin/orders/{order_public_id}/fulfillment` accepts the forward-only sequence `processing`, `shipped`, `fulfilled`. It requires `payment_status=paid`; the corresponding order statuses become `processing`, `shipped`, and `completed`.

Order, payment, and fulfillment statuses remain separate. Cancellation after inventory has been converted to sale returns `409 inventory_already_sold` because returns/refunds are outside the current scope.

Clients send `Accept: application/json` and use `Content-Type: application/json` for JSON bodies. JSON and query names use `snake_case`. Unknown request properties return `422` rather than being silently ignored.

## Shared conventions

### Identifiers

| Identifier | Contract |
| --- | --- |
| `slug` | Case-insensitive storefront lookup for category/product detail. Human-readable and mutable; not a durable relationship key. |
| `public_id` | Opaque ULID used in admin paths and resource relationships for categories, products, and variants. |
| option/value `code` | Product-scoped key because the schema has no option/value `public_id`. Option codes are unique per product; value codes per option. |
| location `code` | Inventory-location key because locations have a unique code but no `public_id`. |
| numeric `id` | Internal database detail; MUST NOT appear in URLs, requests, or responses. |

Write relationships use `public_id`, not mutable slugs. Slugs are lowercase kebab-case, at most the database field length, and unique among non-deleted records of their type. If omitted on create, the server derives one from `name` and resolves collisions with `-2`, `-3`, and so on. Changing a slug changes its URL; v1 has no redirect history.

### Money

Amounts are non-negative integers in minor units. Currency is an uppercase ISO 4217 code:

```json
{ "price_amount": 399000, "compare_at_amount": 449000, "currency": "IDR" }
```

Decimals, formatted strings, and floating-point values are invalid. A non-null `compare_at_amount` MUST exceed `price_amount`. All variants of one product use one currency in v1.

### Time

Timestamps are ISO 8601 UTC strings, normally at second precision: `2026-09-16T08:30:00Z`. Accepted offsets are normalized to UTC.

### Visibility and availability

A public product is non-deleted, has `status=active`, has `published_at <= now`, and has at least one active non-deleted variant. Public categories are active and non-deleted. Public payloads omit inactive/deleted categories and variants.

Variant available-to-sell at a location is `max(on_hand - reserved - safety_stock, 0)`. A variant is publicly `available` when it is active, non-deleted, and summed available-to-sell across active locations is positive. A product is available when any public variant is available. Missing inventory means unavailable; backorders are not supported.

Public APIs expose only boolean `available`. They MUST NOT expose exact quantities, `on_hand`, `reserved`, `safety_stock`, or inventory versions.

## Standard response format

### Success

Single resource:

```json
{ "data": { "public_id": "01K5B3B8X8R5J2G7T6Q9Y0M4NP" }, "meta": {}, "message": null }
```

Bounded, non-paginated collection:

```json
{ "data": [], "meta": {}, "message": null }
```

Paginated collection:

```json
{
  "data": [],
  "meta": {
    "pagination": { "total": 125, "per_page": 20, "current_page": 2, "last_page": 7 }
  },
  "message": null
}
```

Deletes return `204 No Content` with no body. Other commands return a resource/result in the single-resource envelope.

### Errors

```json
{
  "data": null,
  "meta": {
    "errors": [
      { "code": "required", "field": "variants.0.sku", "message": "The SKU field is required." }
    ]
  },
  "message": "Validation failed."
}
```

`field` is omitted for errors not tied to an input. Clients branch on HTTP status and stable `code`, not message text.

| Status | Meaning | Example code |
| --- | --- | --- |
| `400` | Malformed JSON/query syntax | `invalid_request` |
| `401` | Missing/invalid admin credentials | `unauthenticated` |
| `403` | Insufficient permission | `forbidden` |
| `404` | Missing or invisible resource | `product_not_found` |
| `409` | Uniqueness, state, or concurrency conflict | `sku_conflict` |
| `422` | Field validation failed | field-specific codes |
| `500` | Unexpected failure | `internal_error` |

Errors never expose SQL, stack traces, paths, secrets, or numeric IDs.

## Pagination, filtering, and sorting

Top-level collections are paginated with `page` (integer, default/minimum `1`) and `per_page` (integer, default `20`, minimum `1`, maximum `100`). Invalid values return `422`. `total` is post-filter/pre-pagination. `last_page` is at least `1`; pages beyond it return an empty array and accurate metadata. Ordering includes an internal deterministic tie-breaker that is not exposed.

Product collection parameters:

| Parameter | Behavior |
| --- | --- |
| `category` | Exact case-insensitive category slug; direct membership only, not descendants. |
| `search` | Trimmed, case-insensitive name substring, 1–200 characters. |
| `min_price` | Non-negative integer minor units; an eligible variant must be at least this value. |
| `max_price` | Non-negative integer minor units; an eligible variant must be at most this value. |
| `availability` | `available` or `unavailable`. |
| `sort` | `newest`, `oldest`, `price_asc`, `price_desc`, `name_asc`, or `name_desc`. |

`min_price` cannot exceed `max_price`. Public filters inspect public variants; admin filters inspect non-deleted variants. Price sorts use each product's lowest eligible variant price and return each product once. `newest` (default) and `oldest` use `created_at`; name sorts use database collation. Unsupported filters/sorts return `422`.

## Public categories

### `GET /api/v1/categories`

Returns a flat paginated collection. Flat pagination avoids partial nested trees; clients can reconstruct them from `parent.public_id`.

| Query | Default | Rules |
| --- | --- | --- |
| `page`, `per_page` | `1`, `20` | Shared pagination |
| `parent` | omitted | Category `public_id`, or `root`; omit for all |
| `search` | omitted | Name substring, 1–160 characters |
| `include_product_count` | `false` | Boolean |
| `sort` | `position` | `position`, `name_asc`, `name_desc` |

`position` orders by parent, `sort_order`, name. Unknown `parent` returns `404 category_not_found`. `product_count` counts distinct public products directly assigned to the category, not descendants, and is omitted unless requested. `children_count` counts active direct children.

Example category listing:

```json
{
  "data": [
    {
      "public_id": "01K5B2T8GSX9A6GJ3P4CMQ1R7V",
      "name": "Dresses",
      "slug": "dresses",
      "description": "Everyday and occasion dresses.",
      "image_url": "https://cdn.noure.example/categories/dresses.webp",
      "parent": null,
      "product_count": 18,
      "children_count": 2
    },
    {
      "public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT",
      "name": "Midi Dresses",
      "slug": "midi-dresses",
      "description": null,
      "image_url": null,
      "parent": {
        "public_id": "01K5B2T8GSX9A6GJ3P4CMQ1R7V",
        "name": "Dresses",
        "slug": "dresses"
      },
      "product_count": 7,
      "children_count": 0
    }
  ],
  "meta": { "pagination": { "total": 2, "per_page": 20, "current_page": 1, "last_page": 1 } },
  "message": null
}
```

`image_url` is resolved from `image_path`; public storage keys are not exposed.

### `GET /api/v1/categories/{slug}`

Returns the same category schema plus `ancestors` (summary objects ordered root-first) and `children` (direct summaries ordered by `sort_order`, name). Children are one bounded, non-paginated level, not recursive. Products are fetched with `/api/v1/products?category={slug}`.

Query `include_product_count` defaults to `true` and controls counts on the category and children. An absent, inactive, or deleted category returns `404 category_not_found`.

## Public products

### `GET /api/v1/products`

Returns public products and supports shared pagination, `category`, `search`, `min_price`, `max_price`, `availability`, and `sort`. Unknown/non-public category slugs return `404 category_not_found` instead of an empty collection.

Example product listing:

```json
{
  "data": [
    {
      "public_id": "01K5B40QFNDM5YR82J7G9S3XWP",
      "name": "Luna Linen Midi Dress",
      "slug": "luna-linen-midi-dress",
      "short_description": "A breathable linen-blend midi dress.",
      "primary_image": {
        "url": "https://cdn.noure.example/products/luna/front.webp",
        "alt_text": "Luna dress in cream",
        "width": 1200,
        "height": 1500
      },
      "primary_category": {
        "public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT",
        "name": "Midi Dresses",
        "slug": "midi-dresses"
      },
      "price": { "price_amount": 399000, "compare_at_amount": 449000, "currency": "IDR" },
      "price_range": { "min_price_amount": 399000, "max_price_amount": 429000, "currency": "IDR" },
      "available": true,
      "published_at": "2026-09-10T03:00:00Z"
    }
  ],
  "meta": { "pagination": { "total": 36, "per_page": 20, "current_page": 1, "last_page": 2 } },
  "message": null
}
```

`price` is the active default variant's price; `price_range` spans active variants. Stored data must maintain one active default.

### `GET /api/v1/products/{slug}`

Returns one public product. An absent, unpublished, draft, archived, or deleted product returns `404 product_not_found` without disclosing hidden existence.

Example product detail:

```json
{
  "data": {
    "public_id": "01K5B40QFNDM5YR82J7G9S3XWP",
    "name": "Luna Linen Midi Dress",
    "slug": "luna-linen-midi-dress",
    "short_description": "A breathable linen-blend midi dress.",
    "description": "Cut with a softly structured waist and side pockets.",
    "brand": "Noure",
    "categories": [
      {
        "public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT",
        "name": "Midi Dresses",
        "slug": "midi-dresses",
        "is_primary": true
      }
    ],
    "images": [
      {
        "url": "https://cdn.noure.example/products/luna/front.webp",
        "alt_text": "Luna dress in cream",
        "width": 1200,
        "height": 1500,
        "mime_type": "image/webp",
        "sort_order": 0,
        "is_primary": true,
        "variant_public_id": null
      }
    ],
    "options": [
      {
        "name": "Color",
        "code": "color",
        "sort_order": 0,
        "values": [
          { "label": "Cream", "code": "cream", "swatch_value": "#F4E9D8", "sort_order": 0 },
          { "label": "Black", "code": "black", "swatch_value": "#111111", "sort_order": 1 }
        ]
      },
      {
        "name": "Size",
        "code": "size",
        "sort_order": 1,
        "values": [
          { "label": "S", "code": "s", "swatch_value": null, "sort_order": 0 },
          { "label": "M", "code": "m", "swatch_value": null, "sort_order": 1 }
        ]
      }
    ],
    "variants": [
      {
        "public_id": "01K5B4A26A2H7YCQ8NFPV3W9KS",
        "sku": "NOU-LUNA-CRM-S",
        "title": "Cream / S",
        "selected_options": [
          { "option_code": "color", "option_name": "Color", "value_code": "cream", "value_label": "Cream" },
          { "option_code": "size", "option_name": "Size", "value_code": "s", "value_label": "S" }
        ],
        "price_amount": 399000,
        "compare_at_amount": 449000,
        "currency": "IDR",
        "available": true,
        "is_default": true
      }
    ],
    "available": true,
    "published_at": "2026-09-10T03:00:00Z",
    "created_at": "2026-09-01T02:00:00Z",
    "updated_at": "2026-09-14T11:20:00Z"
  },
  "meta": {},
  "message": null
}
```

Public variants omit barcode, weight, internal `combination_key`, raw inventory, and inactive variants. Variant images refer to `variant_public_id`.

## Admin conventions

Admin endpoints require authentication and authorization. The mechanism is an open decision, so this contract fixes `401`/`403` behavior but not token format. Normal admin reads exclude soft-deleted records; v1 defines no `with_deleted`, restore, or purge.

## Admin categories

Admin category resource:

```json
{
  "public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT",
  "parent": { "public_id": "01K5B2T8GSX9A6GJ3P4CMQ1R7V", "name": "Dresses", "slug": "dresses" },
  "name": "Midi Dresses",
  "slug": "midi-dresses",
  "description": null,
  "image_path": "categories/midi-dresses.webp",
  "image_url": "https://cdn.noure.example/categories/midi-dresses.webp",
  "sort_order": 10,
  "is_active": true,
  "direct_product_count": 7,
  "children_count": 0,
  "created_at": "2026-09-01T02:00:00Z",
  "updated_at": "2026-09-12T09:15:00Z"
}
```

### `GET /api/v1/admin/categories`

Flat paginated non-deleted categories. Supports `page`, `per_page`, `parent` (`public_id` or `root`), name `search`, `is_active` boolean, and `sort`: `position` (default), `name_asc`, `name_desc`, `newest`, `oldest`.

### `POST /api/v1/admin/categories`

Creates a category; returns `201` and the resource.

```json
{
  "parent_public_id": "01K5B2T8GSX9A6GJ3P4CMQ1R7V",
  "name": "Midi Dresses",
  "slug": "midi-dresses",
  "description": "Midi-length silhouettes for day and evening.",
  "image_path": "categories/midi-dresses.webp",
  "sort_order": 10,
  "is_active": true
}
```

Validation:

- `parent_public_id`: nullable existing, non-deleted category; no self/descendant cycles;
- `name`: required trimmed string, 1–160 characters;
- `slug`: optional normalized URL-safe string, maximum 180, unique among non-deleted categories;
- `description`: nullable string;
- `image_path`: nullable string, maximum 2048; allowed path/URL policy depends on media decision;
- `sort_order`: optional non-negative integer, default `0`;
- `is_active`: optional boolean, default `true`.

### `GET /api/v1/admin/categories/{public_id}`

Returns one resource or `404 category_not_found` for absent/deleted records.

### `PUT /api/v1/admin/categories/{public_id}`

Uses patch semantics: supplied fields change; omitted fields remain. At least one writable field is required. Create rules apply. `parent_public_id: null` moves to root. `name` cannot be null. Cycles are rejected with `422`; uniqueness races return `409 slug_conflict`.

### `DELETE /api/v1/admin/categories/{public_id}`

Sets `deleted_at` and returns `204`; never hard-deletes. Returns `409 category_has_children` for non-deleted children and `409 category_has_products` for memberships. Children must be reparented and products detached first. Unknown/already deleted returns `404`.

## Admin products

### `GET /api/v1/admin/products`

Paginated non-deleted products. Supports shared product filters plus `status=draft|active|archived`; admin category filtering includes inactive categories. Items contain public ID, name, slug, status, short description, brand, primary image/category, variant count, lowest price/currency, availability, publication and audit timestamps.

### `POST /api/v1/admin/products`

Creates the product, memberships, image metadata, options, values, variants, and selections atomically; it does not create inventory. Returns `201` and full admin detail.

Example admin product create request:

```json
{
  "name": "Luna Linen Midi Dress",
  "slug": "luna-linen-midi-dress",
  "short_description": "A breathable linen-blend midi dress.",
  "description": "Cut with a softly structured waist and side pockets.",
  "brand": "Noure",
  "status": "active",
  "published_at": "2026-09-10T03:00:00Z",
  "metadata": null,
  "categories": [
    { "category_public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT", "is_primary": true, "sort_order": 0 }
  ],
  "images": [
    {
      "path": "products/luna/front.webp",
      "alt_text": "Luna dress in cream",
      "width": 1200,
      "height": 1500,
      "mime_type": "image/webp",
      "sort_order": 0,
      "is_primary": true,
      "variant_sku": null
    }
  ],
  "options": [
    {
      "name": "Color", "code": "color", "sort_order": 0,
      "values": [
        { "label": "Cream", "code": "cream", "swatch_value": "#F4E9D8", "sort_order": 0 },
        { "label": "Black", "code": "black", "swatch_value": "#111111", "sort_order": 1 }
      ]
    },
    {
      "name": "Size", "code": "size", "sort_order": 1,
      "values": [
        { "label": "S", "code": "s", "swatch_value": null, "sort_order": 0 },
        { "label": "M", "code": "m", "swatch_value": null, "sort_order": 1 }
      ]
    }
  ],
  "variants": [
    {
      "sku": "NOU-LUNA-CRM-S",
      "title": "Cream / S",
      "option_values": { "color": "cream", "size": "s" },
      "price_amount": 399000,
      "compare_at_amount": 449000,
      "currency": "IDR",
      "barcode": "8990000010012",
      "weight_grams": 420,
      "is_active": true,
      "is_default": true
    }
  ]
}
```

Validation:

- `name`: required, 1–200 characters; `slug`: optional, max 220 and unique;
- `short_description`: nullable, max 500; `description`: nullable; `brand`: nullable, max 160;
- `status`: required `draft`, `active`, or `archived`; `published_at`: nullable timestamp;
- `metadata`: nullable JSON object for non-critical presentation metadata only;
- category IDs must exist and be unique; `sort_order >= 0`; if categories exist exactly one is primary;
- image `path` is required/max 2048; dimensions and order are non-negative; MIME max 100; at most one product-level and one per-variant primary; `variant_sku` belongs to this request;
- option name/code are required, max 100; option code is normalized and unique per product;
- value label/code are required, max 120; value code is normalized and unique per option; swatch max 100; order non-negative;
- at least one variant is required, even without visible options;
- SKU is required/max 100/globally unique; barcode nullable/max 100; weight nullable/non-negative;
- each variant selects exactly one value for every option, all from this product; no options means `{}` and only one empty combination;
- combinations are unique per product; price is non-negative; compare-at exceeds price; all currencies match;
- `is_active`/`is_default` are booleans; exactly one variant is default and it must be active.

Any nested failure rolls back the request. Validation returns `422`; uniqueness races return `409`.

### `GET /api/v1/admin/products/{public_id}`

Returns complete non-deleted aggregate or `404 product_not_found`.

Example admin product detail response:

```json
{
  "data": {
    "public_id": "01K5B40QFNDM5YR82J7G9S3XWP",
    "name": "Luna Linen Midi Dress",
    "slug": "luna-linen-midi-dress",
    "short_description": "A breathable linen-blend midi dress.",
    "description": "Cut with a softly structured waist and side pockets.",
    "brand": "Noure",
    "status": "active",
    "published_at": "2026-09-10T03:00:00Z",
    "metadata": null,
    "categories": [
      { "public_id": "01K5B2W4EPQX0BD7MNH9AK8CZT", "name": "Midi Dresses", "slug": "midi-dresses", "is_primary": true, "sort_order": 0 }
    ],
    "images": [
      {
        "path": "products/luna/front.webp", "url": "https://cdn.noure.example/products/luna/front.webp",
        "alt_text": "Luna dress in cream", "width": 1200, "height": 1500,
        "mime_type": "image/webp", "sort_order": 0, "is_primary": true, "variant_public_id": null
      }
    ],
    "options": [
      {
        "name": "Color", "code": "color", "sort_order": 0,
        "values": [{ "label": "Cream", "code": "cream", "swatch_value": "#F4E9D8", "sort_order": 0 }]
      },
      {
        "name": "Size", "code": "size", "sort_order": 1,
        "values": [{ "label": "S", "code": "s", "swatch_value": null, "sort_order": 0 }]
      }
    ],
    "variants": [
      {
        "public_id": "01K5B4A26A2H7YCQ8NFPV3W9KS",
        "sku": "NOU-LUNA-CRM-S", "title": "Cream / S",
        "option_values": { "color": "cream", "size": "s" },
        "price_amount": 399000, "compare_at_amount": 449000, "currency": "IDR",
        "barcode": "8990000010012", "weight_grams": 420,
        "is_active": true, "is_default": true, "available": true,
        "created_at": "2026-09-01T02:00:00Z", "updated_at": "2026-09-14T11:20:00Z"
      }
    ],
    "created_at": "2026-09-01T02:00:00Z",
    "updated_at": "2026-09-14T11:20:00Z"
  },
  "meta": {},
  "message": null
}
```

### `PUT /api/v1/admin/products/{public_id}`

Transactional patch semantics: omitted top-level sections are unchanged. Scalar fields use create rules. Supplied `categories`, `images`, `options`, or `variants` arrays are complete replacements for that relationship. Existing variants carry `public_id`; new variants omit it; omitted variants are soft-deleted. Image replacement changes database metadata only, not stored files.

Reject with `409 variant_combination_conflict` if replacement creates duplicate combinations, cross-product values, missing/extra option selections, removes a value still used by a retained variant, or produces invalid defaults. Disabling/removing the default requires selecting another active default in the same transaction. Operationally protected variants may return `409 variant_in_use`. Focused endpoints below are preferred for routine variant work.

### `DELETE /api/v1/admin/products/{public_id}`

Soft-deletes product and sellable variants transactionally and returns `204`. It does not delete orders, snapshots, inventory levels/movements, media files, or audit records. Unknown/already deleted returns `404`. No hard-purge API exists.

## Variant management

### Create option

`POST /api/v1/admin/products/{product_public_id}/options`

```json
{
  "name": "Size",
  "code": "size",
  "sort_order": 1,
  "values": [
    { "label": "S", "code": "s", "swatch_value": null, "sort_order": 0 },
    { "label": "M", "code": "m", "swatch_value": null, "sort_order": 1 }
  ]
}
```

Returns `201`. If variants exist, returns `409 variants_require_regeneration`; adding an option cannot silently make existing variants invalid.

### Add option value

`POST /api/v1/admin/products/{product_public_id}/options/{option_code}/values`

```json
{ "label": "L", "code": "l", "swatch_value": null, "sort_order": 2 }
```

Returns `201`; it does not automatically generate variants.

### Generate combinations

`POST /api/v1/admin/products/{product_public_id}/variants/generate`

```json
{
  "option_values": { "color": ["cream", "black"], "size": ["s", "m"] },
  "defaults": {
    "price_amount": 399000,
    "compare_at_amount": null,
    "currency": "IDR",
    "weight_grams": 420,
    "is_active": false
  },
  "sku_template": "NOU-LUNA-{color}-{size}"
}
```

Every product option appears exactly once; values belong to their option/product. The server computes the Cartesian product and creates only missing combinations. Generated SKUs must be globally unique and within length limits. Any conflict rolls back everything. Existing combinations remain unchanged and unrequested combinations are not deleted. Generated titles follow option order/value labels. Set the default separately; `defaults.is_default` is invalid.

Returns `201` if anything was created, otherwise `200`:

```json
{ "data": { "created": [], "existing": [] }, "meta": {}, "message": null }
```

### Edit or enable/disable variant

`PUT /api/v1/admin/products/{product_public_id}/variants/{variant_public_id}`

Patch semantics. Writable: `sku`, `title`, `option_values`, price, compare-at, currency, barcode, weight, `is_active`. Create rules apply. Changing selections recomputes internal `combination_key`; duplicates return `409 variant_combination_conflict`. Setting `is_active:false` disables selling without deleting. A default cannot be disabled until another active default is selected.

### Set default

`PUT /api/v1/admin/products/{product_public_id}/variants/{variant_public_id}/default`

No body. Transactionally sets the target and clears the old default; returns updated variant. Disabled target returns `409 inactive_variant_cannot_be_default`.

Nested routes return `404 product_not_found`; a missing or cross-product variant returns `404 variant_not_found` without disclosing ownership.

## Inventory management

### `GET /api/v1/admin/variants/{variant_public_id}/inventory`

Returns levels ordered by location code:

```json
{
  "data": [
    {
      "variant_public_id": "01K5B4A26A2H7YCQ8NFPV3W9KS",
      "location": { "code": "MAIN", "name": "Main warehouse", "is_active": true },
      "on_hand": 24,
      "reserved": 3,
      "safety_stock": 2,
      "available": 19,
      "version": 7,
      "updated_at": "2026-09-16T07:45:00Z"
    }
  ],
  "meta": {},
  "message": null
}
```

Admin `available` is derived/read-only. `version` supports optimistic concurrency.

### `POST /api/v1/admin/variants/{variant_public_id}/inventory/adjustments`

```json
{
  "location_code": "MAIN",
  "quantity_delta": 12,
  "reason": "Purchase order PO-2026-0916 received",
  "reference_type": "purchase_order",
  "reference_id": "PO-2026-0916",
  "expected_version": 7
}
```

`location_code` identifies an active location; `quantity_delta` is a required non-zero signed integer; `reason` is required; reference type/id are both null or both present; `expected_version` is required/non-negative. The resulting physical and available stock cannot be negative under the default policy.

Atomically lock/version-check the level, apply the delta to `on_hand`, increment version, and append an `adjustment` movement with actor. A missing level may be initialized at zero in the transaction. Stale version returns `409 inventory_version_conflict`; invalid resulting stock returns `409 insufficient_stock`. Returns `201` with updated level and movement summary.

This endpoint MUST reject writable `available`, `on_hand`, `reserved`, or `safety_stock`. Reservations, releases, sales, returns, and safety-stock changes require purpose-specific workflows outside this catalog contract.

### `GET /api/v1/admin/variants/{variant_public_id}/inventory/movements`

Paginated movement history. Parameters: `page`, `per_page`, exact `location_code`, `movement_type` (`receipt`, `adjustment`, `reservation`, `release`, `sale`, `return`), inclusive ISO timestamps `from`/`to` (`to >= from`), and `sort=newest|oldest` (default newest).

Each row contains `variant_public_id`, location code/name, signed `quantity_delta`, movement type, nullable reference type/id, reason, an actor representation permitted by the future auth model, and `created_at`. It exposes no numeric IDs. Movements are append-only and have no update/delete endpoints.

## Complete error examples

Validation:

```json
{
  "data": null,
  "meta": { "errors": [{ "code": "invalid", "field": "min_price", "message": "The minimum price must not exceed the maximum price." }] },
  "message": "Validation failed."
}
```

Not found:

```json
{
  "data": null,
  "meta": { "errors": [{ "code": "product_not_found", "message": "The requested product was not found." }] },
  "message": "The requested resource was not found."
}
```

Unauthorized:

```json
{ "data": null, "meta": { "errors": [{ "code": "unauthenticated", "message": "Authentication is required." }] }, "message": "Authentication is required." }
```

Forbidden:

```json
{ "data": null, "meta": { "errors": [{ "code": "forbidden", "message": "You do not have permission to perform this action." }] }, "message": "Access is forbidden." }
```

Conflict:

```json
{ "data": null, "meta": { "errors": [{ "code": "sku_conflict", "field": "sku", "message": "The SKU is already in use." }] }, "message": "The request conflicts with the current resource state." }
```

Internal error:

```json
{
  "data": null,
  "meta": {
    "errors": [{ "code": "internal_error", "message": "An unexpected error occurred." }],
    "request_id": "req_01K5C1F7SM8T0X3Q2W9Z6J4HBN"
  },
  "message": "An unexpected error occurred."
}
```

## Customer account

All customer account routes require the `customer` authentication guard. A customer can only access resources owned by their account; cross-account addresses and orders return `404`.

### Profile

- `GET /api/v1/customer/profile`
- `PUT /api/v1/customer/profile`

The update body accepts `first_name`, `last_name`, and `phone`. Email is read-only in this phase.

### Addresses

- `GET /api/v1/customer/addresses`
- `POST /api/v1/customer/addresses`
- `GET /api/v1/customer/addresses/{public_id}`
- `PUT /api/v1/customer/addresses/{public_id}`
- `DELETE /api/v1/customer/addresses/{public_id}`

Address bodies accept `label`, `recipient_name`, `phone`, `line1`, `line2`, `city`, `province`, `postal_code`, `country_code`, `is_default_shipping`, and `is_default_billing`. Default changes and replacement after deletion are transactional; at most one address per default type is active for a customer.

### Orders

- `GET /api/v1/customer/orders?page=1&per_page=20&status=pending&payment_status=unpaid&sort=newest`
- `GET /api/v1/customer/orders/{order_public_id}`

The list is paginated and returns `order_number`, `created_at`, `item_count`, total/currency, and order, payment, and fulfillment statuses. Detail additionally returns the customer contact snapshot, shipping and billing snapshots, item variant/options snapshots, line pricing, and order totals. The order detail uses the existing payment retry endpoint when payment is pending or unpaid.

## Endpoint summary

| Method | Path |
| --- | --- |
| GET | `/api/v1/categories` |
| GET | `/api/v1/categories/{slug}` |
| GET | `/api/v1/products` |
| GET | `/api/v1/products/{slug}` |
| GET, PUT | `/api/v1/customer/profile` |
| GET, POST | `/api/v1/customer/addresses` |
| GET, PUT, DELETE | `/api/v1/customer/addresses/{public_id}` |
| GET | `/api/v1/customer/orders` |
| GET | `/api/v1/customer/orders/{order_public_id}` |
| GET, POST | `/api/v1/admin/categories` |
| GET, PUT, DELETE | `/api/v1/admin/categories/{public_id}` |
| GET, POST | `/api/v1/admin/products` |
| GET, PUT, DELETE | `/api/v1/admin/products/{public_id}` |
| POST | `/api/v1/admin/products/{product_public_id}/options` |
| POST | `/api/v1/admin/products/{product_public_id}/options/{option_code}/values` |
| POST | `/api/v1/admin/products/{product_public_id}/variants/generate` |
| PUT | `/api/v1/admin/products/{product_public_id}/variants/{variant_public_id}` |
| PUT | `/api/v1/admin/products/{product_public_id}/variants/{variant_public_id}/default` |
| GET | `/api/v1/admin/variants/{variant_public_id}/inventory` |
| POST | `/api/v1/admin/variants/{variant_public_id}/inventory/adjustments` |
| GET | `/api/v1/admin/variants/{variant_public_id}/inventory/movements` |

## Open Decisions Before Implementation

1. Approve the database document's proposed ULIDs, money, global category slugs, primary-category rule, and multi-location assumptions before migrations/API code.
2. Choose admin authentication, token/session format, roles, permissions, and inventory-movement actor representation.
3. Decide whether product-scoped option/value codes are durable enough or add ULIDs; the schema gives them no `public_id`.
4. Decide whether product images need public IDs for targeted edits. Define upload, path/URL validation, URL resolution, and orphan-file cleanup.
5. Choose a database-safe or transactional strategy to enforce exactly one default variant.
6. Confirm the one-currency-per-product invariant or redesign pricing for multi-currency.
7. Define SKU case sensitivity/normalization and adopt or reject optional non-null barcode uniqueness.
8. Confirm public visibility semantics and whether activating a product auto-populates `published_at`.
9. Approve restrictive category deletion versus automatic reparent/detach behavior.
10. Define whether option/value codes may change and how referenced values retire; they have no active flag or soft delete.
11. Set maximum options, values, and generated combinations, and finalize SKU-template escaping/normalization.
12. Decide whether adjustments initialize missing levels, allowed reference types/reason length, negative on-hand policy, and a dedicated safety-stock API.
13. Add movement `public_id` if clients must address a single movement; the schema currently has none.
14. Decide whether high-write movement history needs cursor pagination instead of v1 offset pagination.
15. Approve name-substring search collation/accent behavior and performance expectations; no full-text schema exists.
16. Define restore, deleted-record audit access, retention, and authorized purge separately.
17. Decide whether category/product writes need ETags, timestamp preconditions, or version columns to prevent lost updates.
