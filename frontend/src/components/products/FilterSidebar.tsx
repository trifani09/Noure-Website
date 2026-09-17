import Link from "next/link";
import type { Category } from "@/types/catalog";
export function FilterSidebar({
  categories,
  values,
}: {
  categories: Category[];
  values: Record<string, string | undefined>;
}) {
  return (
    <aside
      className="border-t border-line pt-6 lg:border-r lg:border-t-0 lg:pr-8 lg:pt-0"
      aria-label="Product filters"
    >
      <div className="mb-6 flex items-center justify-between lg:hidden">
        <p className="eyebrow text-muted">Refine collection</p>
        <Link href="/products" className="text-xs underline underline-offset-4">
          Clear
        </Link>
      </div>
      <form
        action="/products"
        className="grid gap-6 sm:grid-cols-2 lg:block lg:space-y-7"
      >
        <div>
          <label className="eyebrow text-muted" htmlFor="search">
            Search
          </label>
          <input
            id="search"
            name="search"
            defaultValue={values.search}
            placeholder="Dress, blouse…"
            className="focus-ring mt-3 w-full border-b border-line bg-transparent py-2 text-sm outline-none focus:border-plum"
          />
        </div>
        <div>
          <label className="eyebrow text-muted" htmlFor="category">
            Category
          </label>
          <select
            id="category"
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
        <div>
          <p className="eyebrow text-muted">Price</p>
          <div className="mt-3 grid grid-cols-2 gap-2">
            <input
              aria-label="Minimum price"
              name="min_price"
              defaultValue={values.min_price}
              type="number"
              min="0"
              placeholder="Min"
              className="focus-ring w-full min-w-0 border border-line bg-paper p-3 text-sm"
            />
            <input
              aria-label="Maximum price"
              name="max_price"
              defaultValue={values.max_price}
              type="number"
              min="0"
              placeholder="Max"
              className="focus-ring w-full min-w-0 border border-line bg-paper p-3 text-sm"
            />
          </div>
        </div>
        <div>
          <label className="eyebrow text-muted" htmlFor="availability">
            Availability
          </label>
          <select
            id="availability"
            name="availability"
            defaultValue={values.availability}
            className="focus-ring mt-3 w-full border border-line bg-paper p-3 text-sm"
          >
            <option value="">All pieces</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
        <input type="hidden" name="sort" value={values.sort ?? "newest"} />
        <div className="sm:col-span-2">
          <button className="button-primary focus-ring w-full">
            Apply filters
          </button>
          <Link
            href="/products"
            className="mt-4 hidden text-center text-xs text-muted underline underline-offset-4 lg:block"
          >
            Clear filters
          </Link>
        </div>
      </form>
    </aside>
  );
}
