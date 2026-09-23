import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import { formatMoney } from "@/lib/format";
import type { ProductSummary } from "@/types/catalog";
import { ProductCardAction } from "./ProductCardAction";

export function ProductCard({
  product,
  priority = false,
}: {
  product: ProductSummary;
  priority?: boolean;
}) {
  const hasDiscount =
    product.price.compare_at_amount !== null &&
    product.price.compare_at_amount > product.price.price_amount;
  const discountPercentage = hasDiscount
    ? Math.round(
        ((product.price.compare_at_amount! - product.price.price_amount) /
          product.price.compare_at_amount!) *
          100,
      )
    : 0;
  const productHref = `/product/${product.slug}`;

  return (
    <article className="group min-w-0">
      <div className="relative aspect-[4/5] overflow-hidden bg-ivory">
        <Link
          href={productHref}
          className="focus-ring absolute inset-0 block"
          aria-label={`View ${product.name}`}
        >
          {product.primary_image ? (
            <>
              <SafeImage
                fill
                priority={priority}
                sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
                className="object-cover transition duration-700 ease-out group-hover:scale-[1.025]"
                src={product.primary_image.url}
                alt={product.primary_image.alt_text ?? product.name}
              />
              {product.secondary_image && (
                <SafeImage
                  fill
                  sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
                  className="object-cover opacity-0 transition duration-500 group-hover:opacity-100 group-focus-within:opacity-100"
                  src={product.secondary_image.url}
                  alt={product.secondary_image.alt_text ?? `${product.name} alternate view`}
                />
              )}
            </>
          ) : (
            <div className="grid h-full place-items-center text-xs uppercase tracking-widest text-taupe">
              Noure
            </div>
          )}
        </Link>

        <div className="pointer-events-none absolute left-2 top-2 flex max-w-[calc(100%-1rem)] flex-col items-start gap-1.5 sm:left-3 sm:top-3">
          {hasDiscount && (
            <span className="bg-plum px-2 py-1 text-[8px] font-semibold uppercase tracking-wider text-paper sm:text-[9px]">
              {discountPercentage}% off
            </span>
          )}
          {product.is_best_seller && (
            <span className="bg-ink px-2 py-1 text-[8px] uppercase tracking-wider text-paper sm:text-[9px]">
              Best seller
            </span>
          )}
          {product.is_new && !product.is_best_seller && (
            <span className="bg-paper px-2 py-1 text-[8px] uppercase tracking-wider sm:text-[9px]">
              New
            </span>
          )}
        </div>

        <div className="absolute inset-x-0 bottom-0 translate-y-0 transition-transform duration-300 lg:translate-y-full lg:group-hover:translate-y-0 lg:group-focus-within:translate-y-0">
          <ProductCardAction
            slug={product.slug}
            productName={product.name}
            variantId={product.quick_add_variant_id}
            available={product.available}
          />
        </div>
      </div>

      <div className="pt-4">
        <p className="text-[9px] uppercase tracking-[.16em] text-muted sm:text-[10px] sm:tracking-[.18em]">
          {product.primary_category?.name ?? "Noure"}
        </p>
        <Link href={productHref} className="focus-ring inline-block">
          <h3 className="mt-1 line-clamp-2 font-serif text-base leading-tight hover:text-plum sm:text-xl">
            {product.name}
          </h3>
        </Link>
        <div className="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs sm:text-sm">
          <span className={hasDiscount ? "font-semibold text-plum" : undefined}>
            {formatMoney(product.price.price_amount, product.price.currency)}
          </span>
          {hasDiscount && (
            <span className="text-muted line-through">
              {formatMoney(
                product.price.compare_at_amount!,
                product.price.currency,
              )}
            </span>
          )}
        </div>
        {product.colors.length > 0 && (
          <div className="mt-3 flex items-center gap-1.5" aria-label={`${product.colors.length} colors available`}>
            {product.colors.slice(0, 5).map((color) => (
              <span
                key={color.code}
                title={color.label}
                className="h-3.5 w-3.5 rounded-full border border-ink/20 bg-sand"
                style={color.swatch_value ? { backgroundColor: color.swatch_value } : undefined}
              />
            ))}
            {product.colors.length > 5 && (
              <span className="ml-0.5 text-[10px] text-muted">
                +{product.colors.length - 5}
              </span>
            )}
          </div>
        )}
      </div>
    </article>
  );
}
