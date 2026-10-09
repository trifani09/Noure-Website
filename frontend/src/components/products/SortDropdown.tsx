export function SortDropdown({
  value,
  params,
}: {
  value: string;
  params: Record<string, string | undefined>;
}) {
  return (
    <form action="/products" className="flex items-center gap-2">
      <input type="hidden" name="search" value={params.search ?? ""} />
      <input type="hidden" name="category" value={params.category ?? ""} />
      <input type="hidden" name="min_price" value={params.min_price ?? ""} />
      <input type="hidden" name="max_price" value={params.max_price ?? ""} />
      <input type="hidden" name="availability" value={params.availability ?? ""} />
      <input type="hidden" name="color" value={params.color ?? ""} />
      <input type="hidden" name="size" value={params.size ?? ""} />
      <input type="hidden" name="discounted" value={params.discounted ?? ""} />
      <label className="sr-only" htmlFor="product-sort">
        Urutkan produk
      </label>
      <select
        id="product-sort"
        name="sort"
        defaultValue={value}
        className="focus-ring border border-line bg-paper px-3 py-2.5 text-xs uppercase tracking-wider sm:px-4"
      >
        <option value="best_selling">Terpopuler</option>
        <option value="newest">Terbaru</option>
        <option value="oldest">Terlama</option>
        <option value="price_asc">Harga terendah</option>
        <option value="price_desc">Harga tertinggi</option>
        <option value="name_asc">Nama A–Z</option>
        <option value="name_desc">Nama Z–A</option>
      </select>
      <button className="focus-ring border-b border-ink pb-1 text-xs uppercase tracking-wider hover:text-plum">
        Terapkan
      </button>
    </form>
  );
}
