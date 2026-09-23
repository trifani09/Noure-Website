"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import type { Category, ProductFilters } from "@/types/catalog";

type FilterValues = Record<string, string | undefined>;

function FilterForm({
  categories,
  values,
  idPrefix,
  clearHref,
  filters,
}: {
  categories: Category[];
  values: FilterValues;
  idPrefix: string;
  clearHref: string;
  filters: ProductFilters;
}) {
  return (
    <form action="/products" className="space-y-7">
      <div>
        <label className="eyebrow text-muted" htmlFor={`${idPrefix}-search`}>
          Search
        </label>
        <input
          id={`${idPrefix}-search`}
          name="search"
          defaultValue={values.search}
          placeholder="Dress, blouse…"
          className="focus-ring mt-3 w-full border-b border-line bg-transparent py-2 text-sm outline-none focus:border-plum"
        />
      </div>
      {!!filters.color?.length && (
        <fieldset>
          <legend className="eyebrow text-muted">Color</legend>
          <div className="mt-3 grid grid-cols-2 gap-2">
            <label
              className={`focus-within:ring-2 focus-within:ring-plum flex cursor-pointer items-center gap-2 border px-3 py-2.5 text-xs ${!values.color ? "border-ink bg-ivory" : "border-line"}`}
            >
              <input
                className="sr-only"
                type="radio"
                name="color"
                value=""
                defaultChecked={!values.color}
              />
              <span aria-hidden className="h-4 w-4 shrink-0 rounded-full border border-ink/20 bg-paper" />
              <span>All colors</span>
            </label>
            {filters.color.map((color) => (
              <label
                key={color.code}
                className={`focus-within:ring-2 focus-within:ring-plum flex cursor-pointer items-center gap-2 border px-3 py-2.5 text-xs ${values.color === color.code ? "border-ink bg-ivory" : "border-line"}`}
              >
                <input
                  className="sr-only"
                  type="radio"
                  name="color"
                  value={color.code}
                  defaultChecked={values.color === color.code}
                />
                <span
                  aria-hidden
                  className="h-4 w-4 shrink-0 rounded-full border border-ink/15 bg-sand"
                  style={color.swatch_value ? { backgroundColor: color.swatch_value } : undefined}
                />
                <span className="truncate">{color.label}</span>
              </label>
            ))}
          </div>
        </fieldset>
      )}
      {!!filters.size?.length && (
        <div>
          <label className="eyebrow text-muted" htmlFor={`${idPrefix}-size`}>
            Size
          </label>
          <select
            id={`${idPrefix}-size`}
            name="size"
            defaultValue={values.size}
            className="focus-ring mt-3 w-full border border-line bg-paper p-3 text-sm"
          >
            <option value="">All sizes</option>
            {filters.size.map((size) => (
              <option key={size.code} value={size.code}>{size.label}</option>
            ))}
          </select>
        </div>
      )}
      <label className="flex cursor-pointer items-center justify-between gap-4 border-y border-line py-4 text-sm">
        <span>
          <span className="block font-medium">On sale</span>
          <span className="mt-1 block text-xs text-muted">Only show reduced pieces</span>
        </span>
        <input
          type="checkbox"
          name="discounted"
          value="1"
          defaultChecked={values.discounted === "1"}
          className="h-4 w-4 accent-ink"
        />
      </label>
      <div>
        <label className="eyebrow text-muted" htmlFor={`${idPrefix}-category`}>
          Category
        </label>
        <select
          id={`${idPrefix}-category`}
          name="category"
          defaultValue={values.category}
          className="focus-ring mt-3 w-full border border-line bg-paper p-3 text-sm"
        >
          <option value="">All categories</option>
          {categories.map((category) => (
            <option key={category.public_id} value={category.slug}>
              {category.name}
            </option>
          ))}
        </select>
      </div>
      <fieldset>
        <legend className="eyebrow text-muted">Price range</legend>
        <div className="mt-3 grid grid-cols-2 gap-2">
          <input
            aria-label="Minimum price"
            name="min_price"
            defaultValue={values.min_price}
            type="number"
            inputMode="numeric"
            min="0"
            placeholder="Minimum"
            className="focus-ring w-full min-w-0 border border-line bg-paper p-3 text-sm"
          />
          <input
            aria-label="Maximum price"
            name="max_price"
            defaultValue={values.max_price}
            type="number"
            inputMode="numeric"
            min="0"
            placeholder="Maximum"
            className="focus-ring w-full min-w-0 border border-line bg-paper p-3 text-sm"
          />
        </div>
        <p className="mt-2 text-[11px] text-muted">Enter prices in Indonesian rupiah.</p>
      </fieldset>
      <div>
        <label className="eyebrow text-muted" htmlFor={`${idPrefix}-availability`}>
          Availability
        </label>
        <select
          id={`${idPrefix}-availability`}
          name="availability"
          defaultValue={values.availability}
          className="focus-ring mt-3 w-full border border-line bg-paper p-3 text-sm"
        >
          <option value="">All pieces</option>
          <option value="available">In stock</option>
          <option value="unavailable">Out of stock</option>
        </select>
      </div>
      <input type="hidden" name="sort" value={values.sort ?? "newest"} />
      <div>
        <button className="button-primary focus-ring w-full">Show results</button>
        <Link
          href={clearHref}
          className="focus-ring mt-4 block text-center text-xs text-muted underline underline-offset-4"
        >
          Clear all filters
        </Link>
      </div>
    </form>
  );
}

export function FilterSidebar({
  categories,
  values,
  clearHref = "/products",
  filters,
}: {
  categories: Category[];
  values: FilterValues;
  clearHref?: string;
  filters: ProductFilters;
}) {
  const [open, setOpen] = useState(false);
  const closeButton = useRef<HTMLButtonElement>(null);
  const activeCount = [
    values.search,
    values.category,
    values.min_price || values.max_price,
    values.availability,
    values.color,
    values.size,
    values.discounted,
  ].filter(Boolean).length;

  useEffect(() => {
    if (!open) return;
    const previousOverflow = document.body.style.overflow;
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", handleKeyDown);
    closeButton.current?.focus();
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open]);

  return (
    <>
      <div className="lg:hidden">
        <button
          type="button"
          onClick={() => setOpen(true)}
          aria-haspopup="dialog"
          aria-expanded={open}
          className="focus-ring flex w-full items-center justify-between border-y border-line py-4 text-xs font-semibold uppercase tracking-[.14em]"
        >
          <span className="flex items-center gap-3">
            Filter
            {activeCount > 0 && (
              <span className="grid h-5 min-w-5 place-items-center rounded-full bg-ink px-1 text-[10px] text-paper">
                {activeCount}
              </span>
            )}
          </span>
          <span aria-hidden>＋</span>
        </button>
      </div>

      {open && (
        <div className="fixed inset-0 z-[60] lg:hidden">
          <button
            type="button"
            aria-label="Close filters"
            className="absolute inset-0 bg-ink/40"
            onClick={() => setOpen(false)}
          />
          <aside
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-filter-title"
            className="absolute inset-y-0 right-0 flex w-[min(92vw,26rem)] flex-col bg-paper shadow-2xl"
          >
            <div className="flex items-center justify-between border-b border-line px-6 py-5">
              <div>
                <p className="eyebrow text-plum">Refine</p>
                <h2 id="mobile-filter-title" className="editorial-title mt-1 text-3xl">
                  Filters
                </h2>
              </div>
              <button
                ref={closeButton}
                type="button"
                aria-label="Close filters"
                onClick={() => setOpen(false)}
                className="focus-ring p-2 text-2xl"
              >
                ×
              </button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-7">
              <FilterForm
                categories={categories}
                values={values}
                idPrefix="mobile-filter"
                clearHref={clearHref}
                filters={filters}
              />
            </div>
          </aside>
        </div>
      )}

      <aside
        className="hidden border-r border-line pr-8 lg:block"
        aria-label="Product filters"
      >
        <div className="mb-7 flex items-center justify-between">
          <p className="eyebrow text-muted">Refine collection</p>
          {activeCount > 0 && (
            <span className="text-[10px] uppercase tracking-widest text-plum">
              {activeCount} active
            </span>
          )}
        </div>
        <FilterForm
          categories={categories}
          values={values}
          idPrefix="desktop-filter"
          clearHref={clearHref}
          filters={filters}
        />
      </aside>
    </>
  );
}
