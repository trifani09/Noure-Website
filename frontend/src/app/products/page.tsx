import type { Metadata } from "next";
import { Breadcrumb } from "@/components/common/Breadcrumb";
import { FilterSidebar } from "@/components/products/FilterSidebar";
import { Pagination } from "@/components/products/Pagination";
import { ProductGrid } from "@/components/products/ProductGrid";
import { SortDropdown } from "@/components/products/SortDropdown";
import { getCategories } from "@/features/categories";
import { getProductFilters, getProducts } from "@/features/products";
export const dynamic = "force-dynamic";
type Params = Record<string, string | string[] | undefined>;
const one = (value: string | string[] | undefined) =>
  Array.isArray(value) ? value[0] : value;
export async function generateMetadata({
  searchParams,
}: {
  searchParams: Promise<Params>;
}): Promise<Metadata> {
  const sort = one((await searchParams).sort);
  const title =
    sort === "best_selling"
      ? "Best sellers"
      : sort === "newest"
        ? "New arrivals"
        : "Shop";

  return {
    title,
    description: "Discover the Noure collection of considered modern womenswear.",
    alternates: { canonical: "/products" },
  };
}
export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<Params>;
}) {
  const raw = await searchParams;
  const values = {
    search: one(raw.search),
    category: one(raw.category),
    min_price: one(raw.min_price),
    max_price: one(raw.max_price),
    availability: one(raw.availability),
    color: one(raw.color),
    size: one(raw.size),
    discounted: one(raw.discounted),
    sort: one(raw.sort) ?? "newest",
    page: one(raw.page) ?? "1",
  };
  const [{ data: products, pagination }, { data: categories }, filters] =
    await Promise.all([
      getProducts({ ...values, per_page: 20 }),
      getCategories({ per_page: 100 }),
      getProductFilters(),
    ]);
  const collectionCopy =
    values.sort === "best_selling"
      ? {
          eyebrow: "Most loved",
          title: "Best sellers",
          description: "The Noure pieces our customers choose most often.",
        }
      : one(raw.sort) === "newest" && !values.search && !values.category
        ? {
            eyebrow: "Just in",
            title: "New arrivals",
            description: "Discover the latest additions to the Noure wardrobe.",
          }
        : {
            eyebrow: "The collection",
            title: "Shop Noure",
            description: "Explore every considered Noure piece.",
          };
  const requestedSort = one(raw.sort);
  const clearHref = requestedSort
    ? `/products?sort=${encodeURIComponent(requestedSort)}`
    : "/products";
  return (
    <div className="page-shell py-8 md:py-14">
      <Breadcrumb items={[{ label: "Shop" }]} />
      <div className="mt-10 flex flex-wrap items-end justify-between gap-6 border-b border-line pb-9">
        <div>
          <p className="eyebrow text-plum">{collectionCopy.eyebrow}</p>
          <h1 className="editorial-title mt-3 text-5xl md:text-6xl">
            {collectionCopy.title}
          </h1>
          <p className="mt-4 max-w-xl text-sm leading-6 text-muted">
            {collectionCopy.description}
          </p>
          <p className="mt-3 text-sm text-muted">
            {pagination.total} considered piece
            {pagination.total === 1 ? "" : "s"}
          </p>
        </div>
        <SortDropdown value={values.sort} params={values} />
      </div>
      <div className="mt-10 grid gap-10 lg:grid-cols-[15rem_1fr]">
        <FilterSidebar
          categories={categories}
          values={values}
          filters={filters}
          clearHref={clearHref}
        />
        <div>
          <ProductGrid products={products} />
          <Pagination pagination={pagination} params={values} />
        </div>
      </div>
    </div>
  );
}
