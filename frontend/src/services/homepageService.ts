import { getCategories, getHomepage, getProducts } from "@/services/api";

export async function getHomepageExperience() {
  const homepage = await getHomepage();
  if (homepage.hero_banners.length || homepage.sections.length) {
    return { homepage, categories: [], products: [] };
  }

  const [categories, products] = await Promise.all([
    getCategories({ per_page: 8, sort: "position" }),
    getProducts({ per_page: 12, sort: "newest" }),
  ]);

  return { homepage, categories: categories.data, products: products.data };
}
