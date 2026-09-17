import type { Metadata } from "next";
import { BrandStory } from "@/components/homepage/BrandStory";
import { FallbackHero } from "@/components/homepage/FallbackHero";
import { FeaturedCategories } from "@/components/homepage/FeaturedCategories";
import { FeaturedProducts } from "@/components/homepage/FeaturedProducts";
import { HeroBanner } from "@/components/homepage/HeroBanner";
import { NewsletterSection } from "@/components/homepage/NewsletterSection";
import { PromotionalBanner } from "@/components/homepage/PromotionalBanner";
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
  const categorySection: HomepageSection = {
    public_id: "catalog-categories",
    type: "featured_categories",
    name: "Shop by collection",
    sort_order: 1,
    configuration: {
      heading: "Find your new favourite",
      body: "Explore Noure through a considered edit of silhouettes and finishing touches.",
    },
    banners: [],
    categories: categories.slice(0, 4),
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
  const storySection: HomepageSection = {
    public_id: "brand-story",
    type: "brand_story",
    name: "Quietly expressive",
    sort_order: 4,
    configuration: {
      heading: "A quiet point of view",
      body: "Noure brings together considered silhouettes and thoughtful details for a wardrobe that feels distinctly your own.",
      cta_label: "Discover the collection",
      cta_url: "/products",
    },
    banners: [],
    categories: [],
    products: [],
  };

  return (
    <>
      {hero ? (
        <HeroBanner banner={hero} />
      ) : (
        !hasCmsContent && (
          <FallbackHero
            product={products.find((product) => product.primary_image)}
          />
        )
      )}
      {hasCmsContent ? (
        homepage.sections.map((section) => {
          switch (section.type) {
            case "featured_categories":
              return (
                <FeaturedCategories key={section.public_id} section={section} />
              );
            case "featured_products":
              return (
                <FeaturedProducts key={section.public_id} section={section} />
              );
            case "promotional_banner":
              return (
                <PromotionalBanner key={section.public_id} section={section} />
              );
            case "brand_story":
              return <BrandStory key={section.public_id} section={section} />;
            default:
              return null;
          }
        })
      ) : (
        <>
          <FeaturedCategories section={categorySection} />
          <FeaturedProducts section={arrivalSection} />
          <BrandStory
            section={storySection}
            imageUrl={
              products.find((product) => product.primary_image)?.primary_image
                ?.url
            }
          />
          {signatureSection.products.length > 0 && (
            <div className="bg-paper">
              <FeaturedProducts section={signatureSection} />
            </div>
          )}
        </>
      )}
      <NewsletterSection />
    </>
  );
}
