# Noure storefront sitemap

The storefront uses the Next.js App Router. Global `loading.tsx`, `error.tsx`, and `not-found.tsx` provide loading, recovery, and missing-page foundations. Commerce and customer routes are structural placeholders until their APIs are in scope.

| Route | Purpose | API dependency | Primary components |
| --- | --- | --- | --- |
| `/` | Editorial homepage | Homepage CMS, categories, and products | Homepage section components |
| `/products` | Full filtered catalog | Products and categories | FilterSidebar, SortDropdown, ProductGrid, Pagination |
| `/category/[slug]` | Category product listing | Category detail and products | ProductGrid, Pagination |
| `/product/[slug]` | Product detail | Product detail and related products | ProductGallery, VariantSelector, ProductGrid |
| `/search` | Product-name search | Products with `search` query | Breadcrumb, ProductGrid, Pagination, EmptyState |
| `/about` | Brand-story foundation | Future CMS content | ContentPlaceholder |
| `/contact` | Contact foundation | Future contact content | ContentPlaceholder |
| `/faq` | FAQ foundation | Future FAQ content | ContentPlaceholder |
| `/policies/privacy` | Privacy foundation | Approved legal content | ContentPlaceholder |
| `/policies/terms` | Terms foundation | Approved legal content | ContentPlaceholder |
| `/account` | Account placeholder | Future customer authentication | ContentPlaceholder |
| `/account/orders` | Order-history placeholder | Future customer/order APIs | ContentPlaceholder |
| `/account/profile` | Profile placeholder | Future customer API | ContentPlaceholder |
| `/cart` | Shopping-bag placeholder | Future cart API | ContentPlaceholder |
| `/checkout` | Checkout placeholder | Future checkout/payment APIs | ContentPlaceholder |

## Shared layout dependencies

- `Header` and `MobileNavigation` provide storefront navigation.
- `Footer` exposes catalog, content, account, and policy destinations.
- `Container`, `Section`, and `Breadcrumb` provide reusable page structure.

## Domain boundaries

- `features/homepage`: homepage orchestration.
- `features/products`: product service boundary.
- `features/categories`: category service boundary.
- `features/content`: static and future CMS presentation.
- `services`: API clients and domain adapters.
- `types`: API contract types.
- `lib`: framework-independent utilities.
