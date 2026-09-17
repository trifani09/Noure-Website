import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import { SectionHeading } from "../common/SectionHeading";
import type { HomepageSection } from "@/types/catalog";
export function FeaturedCategories({ section }: { section: HomepageSection }) {
  if (!section.categories.length) return null;
  return (
    <section id="categories" className="page-shell section-space">
      <SectionHeading
        eyebrow="The edit"
        title={section.configuration.heading ?? section.name}
        body={section.configuration.body}
      />
      <div className="mt-12 grid gap-5 md:grid-cols-3">
        {section.categories.map((category, index) => (
          <Link
            href={`/category/${category.slug}`}
            key={category.public_id}
            className={`focus-ring group relative overflow-hidden bg-ivory ${index === 0 ? "md:col-span-2" : ""}`}
          >
            <div
              className={`relative ${index === 0 ? "aspect-[16/10] md:aspect-[16/9]" : "aspect-[4/5]"}`}
            >
              {category.image_url ? (
                <SafeImage
                  fill
                  sizes={
                    index === 0
                      ? "(max-width:768px) 100vw, 66vw"
                      : "(max-width:768px) 100vw, 33vw"
                  }
                  className="object-cover transition duration-700 group-hover:scale-[1.025]"
                  src={category.image_url}
                  alt={category.name}
                />
              ) : (
                <div className="h-full bg-gradient-to-br from-sand to-taupe" />
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-ink/60 via-transparent" />
              <div className="absolute inset-x-0 bottom-0 p-6 text-paper">
                <p className="eyebrow text-paper/70">Collection</p>
                <h3 className="editorial-title mt-2 text-3xl">
                  {category.name}
                </h3>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}
