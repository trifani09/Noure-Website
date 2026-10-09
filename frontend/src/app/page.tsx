import type { Metadata } from "next";
import { FallbackHero } from "@/components/homepage/FallbackHero";
import { FeaturedCategories } from "@/components/homepage/FeaturedCategories";
import { FeaturedProducts } from "@/components/homepage/FeaturedProducts";
import { HeroBanner } from "@/components/homepage/HeroBanner";
import { getHomepageExperience } from "@/services/homepageService";
import type { HomepageSection } from "@/types/catalog";

export const dynamic = "force-dynamic";
export const metadata: Metadata = {
  title: "Modern essentials, thoughtfully designed",
  description:
    "Discover Noure's considered collection of modern, feminine wardrobe essentials.",
  openGraph: {
    title: "Noure — Modern essentials",
    description: "Considered silhouettes for a quietly expressive wardrobe.",
  },
};

export default async function Home() {
  const { homepage, categories, products } = await getHomepageExperience();
  const hasCmsContent =
    homepage.hero_banners.length > 0 || homepage.sections.length > 0;
  const hero = homepage.hero_banners[0];
  const cmsCategorySection = homepage.sections.find(
    (section) =>
      section.type === "featured_categories" &&
      section.categories.some((category) =>
        ["kerudung", "pashmina"].includes(category.slug),
      ),
  );
  const cmsCategories = homepage.sections
    .filter((section) => section.type === "featured_categories")
    .flatMap((section) => section.categories);
  const categorySlugs = ["kerudung", "pashmina"];
  const categoryBySlug = new Map(
    [...categories, ...cmsCategories]
      .filter((category) => categorySlugs.includes(category.slug))
      .map((category) => [category.slug, category]),
  );
  const cmsProductSections = homepage.sections.filter(
    (section) => section.type === "featured_products" && section.products.length > 0,
  );
  const categorySection: HomepageSection = cmsCategorySection
    ? { ...cmsCategorySection, categories: [...categoryBySlug.values()] }
    : {
        public_id: "catalog-categories",
        type: "featured_categories",
        name: "Shop by collection",
        sort_order: 1,
        configuration: {
      heading: "Pilih Kategori",
          body: "Temukan koleksi Noure sesuai gaya favoritmu.",
        },
        banners: [],
        categories: [...categoryBySlug.values()],
        products: [],
      };
  const arrivalSection: HomepageSection = {
    public_id: "new-arrivals",
    type: "featured_products",
    name: "New arrivals",
    sort_order: 2,
    configuration: {
      heading: "New arrivals",
      body: "The latest additions to the Noure wardrobe.",
      cta_label: "Shop all new arrivals",
      cta_url: "/products?sort=newest",
    },
    banners: [],
    categories: [],
    products: products.slice(0, 4),
  };
  const signatureSection: HomepageSection = {
    public_id: "signature",
    type: "featured_products",
    name: "Signature collection",
    sort_order: 3,
    configuration: {
      heading: "The signature collection",
      body: "A focused selection of Noure pieces designed to work beautifully together.",
      cta_label: "Explore the collection",
      cta_url: "/products",
    },
    banners: [],
    categories: [],
    products: products.slice(4, 8),
  };
  const productSections = cmsProductSections.length > 0
    ? cmsProductSections.slice(0, 2)
    : [arrivalSection, signatureSection].filter((section) => section.products.length > 0);

  return (
    <>
      {hero ? (
        <HeroBanner banner={hero} />
      ) : (
        !hasCmsContent && (
          <FallbackHero />
        )
      )}
      <FeaturedCategories section={categorySection} />
      {productSections.map((section) => (
        <FeaturedProducts key={section.public_id} section={section} />
      ))}
    </>
  );
}
