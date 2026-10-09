import { getCategories, getHomepage, getProducts } from "@/services/api";

export async function getHomepageExperience() {
  const homepage = await getHomepage();
  const featuredCategorySlugs = ["kerudung", "pashmina"];
  const configuredCategorySlugs = new Set(
    homepage.sections
      .filter((section) => section.type === "featured_categories")
      .flatMap((section) => section.categories.map((category) => category.slug)),
  );
  const needsCategories = featuredCategorySlugs.some(
    (slug) => !configuredCategorySlugs.has(slug),
  );
  const needsProducts = !homepage.sections.some(
    (section) => section.type === "featured_products" && section.products.length > 0,
  );
  const [categories, products] = await Promise.all([
    needsCategories
      ? getCategories({ per_page: 12, sort: "position" })
      : Promise.resolve({ data: [] }),
    needsProducts
      ? getProducts({ per_page: 12, sort: "newest" })
      : Promise.resolve({ data: [] }),
  ]);

  return { homepage, categories: categories.data, products: products.data };
}
