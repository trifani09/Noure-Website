import type { MetadataRoute } from "next";
import { getCategories, getProducts } from "@/services/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl = (
    process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"
  ).replace(/\/$/, "");
  const staticRoutes: MetadataRoute.Sitemap = [
    { url: siteUrl, changeFrequency: "daily", priority: 1 },
    { url: `${siteUrl}/products`, changeFrequency: "daily", priority: 0.9 },
    { url: `${siteUrl}/about`, changeFrequency: "monthly", priority: 0.6 },
    { url: `${siteUrl}/contact`, changeFrequency: "monthly", priority: 0.5 },
    { url: `${siteUrl}/faq`, changeFrequency: "monthly", priority: 0.5 },
    {
      url: `${siteUrl}/policies/privacy`,
      changeFrequency: "yearly",
      priority: 0.3,
    },
    {
      url: `${siteUrl}/policies/terms`,
      changeFrequency: "yearly",
      priority: 0.3,
    },
  ];
  try {
    const [products, categories] = await Promise.all([
      getProducts({ per_page: 100 }),
      getCategories({ per_page: 100 }),
    ]);
    return [
      ...staticRoutes,
      ...categories.data.map((category) => ({
        url: `${siteUrl}/category/${category.slug}`,
        changeFrequency: "weekly" as const,
        priority: 0.7,
      })),
      ...products.data.map((product) => ({
        url: `${siteUrl}/product/${product.slug}`,
        lastModified: product.published_at,
        changeFrequency: "weekly" as const,
        priority: 0.8,
      })),
    ];
  } catch {
    return staticRoutes;
  }
}
