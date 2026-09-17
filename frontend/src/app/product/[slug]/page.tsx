import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Breadcrumb } from "@/components/common/Breadcrumb";
import { ProductGallery } from "@/components/products/ProductGallery";
import { ProductGrid } from "@/components/products/ProductGrid";
import { VariantSelector } from "@/components/products/VariantSelector";
import { getProduct, getProducts, StorefrontApiError } from "@/services/api";
export const dynamic = "force-dynamic";
type Props = { params: Promise<{ slug: string }> };
async function product(slug: string) {
  try {
    return await getProduct(slug);
  } catch (error) {
    if (error instanceof StorefrontApiError && error.status === 404) notFound();
    throw error;
  }
}
export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const item = await product((await params).slug);
  const image = item.images.find((entry) => entry.is_primary) ?? item.images[0];
  return {
    title: item.name,
    description: item.short_description ?? item.description ?? undefined,
    alternates: { canonical: `/product/${item.slug}` },
    openGraph: {
      title: item.name,
      description: item.short_description ?? undefined,
      type: "website",
      images: image
        ? [
            {
              url: image.url,
              width: image.width ?? 1200,
              height: image.height ?? 1500,
              alt: image.alt_text ?? item.name,
            },
          ]
        : [],
    },
    twitter: {
      card: "summary_large_image",
      title: item.name,
      description: item.short_description ?? undefined,
      images: image ? [image.url] : [],
    },
  };
}
const schemaAmount = (amount: number, currency: string) =>
  ["IDR", "JPY", "KRW", "VND"].includes(currency)
    ? String(amount)
    : (amount / 100).toFixed(2);
export default async function ProductPage({ params }: Props) {
  const item = await product((await params).slug);
  const category =
    item.categories.find((entry) => entry.is_primary) ?? item.categories[0];
  const related = category
    ? await getProducts({ category: category.slug, per_page: 5 })
    : {
        data: [],
        pagination: { total: 0, per_page: 5, current_page: 1, last_page: 1 },
      };
  const relatedProducts = related.data
    .filter((entry) => entry.public_id !== item.public_id)
    .slice(0, 4);
  const defaultVariant =
    item.variants.find((entry) => entry.is_default) ?? item.variants[0];
  const schema = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: item.name,
    description: item.short_description ?? item.description,
    image: item.images.map((image) => image.url),
    sku: defaultVariant?.sku,
    brand: item.brand ? { "@type": "Brand", name: item.brand } : undefined,
    offers: item.variants.map((variant) => ({
      "@type": "Offer",
      sku: variant.sku,
      priceCurrency: variant.currency,
      price: schemaAmount(variant.price_amount, variant.currency),
      availability: variant.available
        ? "https://schema.org/InStock"
        : "https://schema.org/OutOfStock",
      url: `${process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"}/product/${item.slug}`,
    })),
  };
  const detailSections = [
    { title: "Description", body: item.description },
    { title: "Material", body: item.material },
    { title: "Care instructions", body: item.care_instructions },
    { title: "Shipping information", body: item.shipping_information },
  ].filter((section) => section.body);
  return (
    <div className="page-shell py-8 md:py-14">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(schema).replace(/</g, "\\u003c"),
        }}
      />
      <Breadcrumb
        items={[
          { label: "Shop", href: "/products" },
          ...(category
            ? [{ label: category.name, href: `/category/${category.slug}` }]
            : []),
          { label: item.name },
        ]}
      />
      <div className="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(22rem,.65fr)] lg:gap-16">
        <ProductGallery product={item} />
        <div className="lg:sticky lg:top-28 lg:self-start">
          <p className="eyebrow text-plum">{item.brand ?? "Noure"}</p>
          <h1 className="editorial-title mt-3 text-5xl leading-none md:text-6xl">
            {item.name}
          </h1>
          {item.short_description && (
            <p className="mt-5 text-sm leading-7 text-muted">
              {item.short_description}
            </p>
          )}
          <div className="mt-7 border-t border-line pt-7">
            <VariantSelector product={item} />
          </div>
          {detailSections.length > 0 && (
            <div className="mt-8 divide-y divide-line border-y border-line">
              {detailSections.map((section, index) => (
                <details
                  key={section.title}
                  open={index === 0}
                  className="group py-5"
                >
                  <summary className="focus-ring cursor-pointer list-none text-xs font-semibold uppercase tracking-widest">
                    {section.title}
                    <span
                      aria-hidden
                      className="float-right transition group-open:rotate-45"
                    >
                      ＋
                    </span>
                  </summary>
                  <p className="mt-4 whitespace-pre-line text-sm leading-7 text-muted">
                    {section.body}
                  </p>
                </details>
              ))}
              {(item.brand || item.options.length > 0) && (
                <details className="group py-5">
                  <summary className="focus-ring cursor-pointer list-none text-xs font-semibold uppercase tracking-widest">
                    Product details
                    <span
                      aria-hidden
                      className="float-right transition group-open:rotate-45"
                    >
                      ＋
                    </span>
                  </summary>
                  <dl className="mt-4 space-y-3 text-sm text-muted">
                    {item.brand && (
                      <div className="flex justify-between gap-6">
                        <dt>Brand</dt>
                        <dd>{item.brand}</dd>
                      </div>
                    )}
                    {item.options.map((option) => (
                      <div
                        className="flex justify-between gap-6"
                        key={option.code}
                      >
                        <dt>{option.name}</dt>
                        <dd className="text-right">
                          {option.values.map((value) => value.label).join(", ")}
                        </dd>
                      </div>
                    ))}
                  </dl>
                </details>
              )}
            </div>
          )}
          <Link
            href="/contact"
            className="mt-6 inline-block text-xs text-muted underline underline-offset-4 hover:text-ink"
          >
            Need help with this piece?
          </Link>
        </div>
      </div>
      {relatedProducts.length > 0 && (
        <section className="section-space">
          <p className="eyebrow text-plum">Continue exploring</p>
          <h2 className="editorial-title mb-10 mt-3 text-4xl md:text-5xl">
            You may also like
          </h2>
          <ProductGrid products={relatedProducts} />
        </section>
      )}
    </div>
  );
}
