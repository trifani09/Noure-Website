import Link from "next/link";
import type { Category } from "@/types/catalog";

export function CategoryTabs({ categories, activeSlug }: { categories: Category[]; activeSlug?: string }) {
  const primaryCategories = ["kerudung", "pashmina"]
    .map((slug) => categories.find((category) => category.slug === slug))
    .filter((category): category is Category => Boolean(category));
  return (
    <nav aria-label="Kategori produk" className="border-y border-line">
      <div className="page-shell flex gap-7 overflow-x-auto py-4">
        <Link
          href="/products"
          className={`shrink-0 text-xs font-medium uppercase tracking-[.08em] ${!activeSlug ? "text-ink underline underline-offset-8" : "text-muted hover:text-plum"}`}
        >
          Semua Produk
        </Link>
        {primaryCategories.map((category) => (
          <Link
            key={category.public_id}
            href={`/category/${encodeURIComponent(category.slug)}`}
            className={`shrink-0 text-xs font-medium uppercase tracking-[.08em] ${activeSlug === category.slug ? "text-ink underline underline-offset-8" : "text-muted hover:text-plum"}`}
          >
            {category.name}
          </Link>
        ))}
      </div>
    </nav>
  );
}
