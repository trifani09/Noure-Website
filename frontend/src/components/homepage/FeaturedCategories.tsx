import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import type { HomepageSection } from "@/types/catalog";
export function FeaturedCategories({ section }: { section: HomepageSection }) {
  const categories = ["kerudung", "pashmina"]
    .map((slug) => section.categories.find((category) => category.slug === slug))
    .filter((category): category is HomepageSection["categories"][number] => Boolean(category));
  if (!categories.length) return null;
  return (
    <section id="categories" className="page-shell py-14 md:py-20">
      <div className="text-center">
        <h2 className="text-lg font-semibold uppercase tracking-[.12em] md:text-xl">
          {section.configuration.heading ?? "Pilih Kategori"}
        </h2>
        {section.configuration.body && (
          <p className="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted">
            {section.configuration.body}
          </p>
        )}
      </div>
      <div className="mx-auto mt-8 grid max-w-6xl gap-4 sm:grid-cols-2 md:gap-7">
        {categories.map((category) => (
          <Link
            href={`/category/${category.slug}`}
            key={category.public_id}
            className="focus-ring group block"
          >
            <div className="relative aspect-[4/3] overflow-hidden bg-ivory md:aspect-[5/4]">
              {category.image_url ? (
                <SafeImage
                  fill
                  sizes="(max-width: 640px) 100vw, 50vw"
                  className="object-cover transition duration-500 group-hover:scale-[1.03]"
                  src={category.image_url}
                  alt={category.name}
                />
              ) : (
                <div className="h-full bg-gradient-to-br from-sand to-taupe" />
              )}
            </div>
            <h3 className="mt-4 text-center text-base font-medium uppercase tracking-[.08em] group-hover:text-plum">
              {category.name}
            </h3>
          </Link>
        ))}
      </div>
    </section>
  );
}
