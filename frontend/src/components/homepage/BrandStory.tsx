import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import type { HomepageSection } from "@/types/catalog";
export function BrandStory({
  section,
  imageUrl,
}: {
  section: HomepageSection;
  imageUrl?: string;
}) {
  return (
    <section id="story" className="bg-ivory section-space">
      <div className="page-shell grid items-center gap-12 md:grid-cols-2">
        <div className="relative mx-auto aspect-[4/5] w-full max-w-lg overflow-hidden">
          <SafeImage
            fill
            sizes="(max-width:768px) 100vw, 50vw"
            className="object-cover"
            src={imageUrl ?? "/images/editorial/noure-brand-story.png"}
            alt="The Noure point of view"
          />
          <div className="absolute inset-x-5 bottom-5 border border-paper/40 bg-ink/55 px-5 py-4 text-paper backdrop-blur-sm">
            <p className="eyebrow text-paper/65">Designed with intention</p>
            <p className="editorial-title mt-1 text-2xl">Quiet confidence</p>
          </div>
        </div>
        <div className="max-w-xl">
          <p className="eyebrow text-plum">Our point of view</p>
          <h2 className="editorial-title mt-4 text-5xl md:text-6xl">
            {section.configuration.heading ?? section.name}
          </h2>
          {section.configuration.body && (
            <p className="mt-6 whitespace-pre-line text-sm leading-8 text-muted">
              {section.configuration.body}
            </p>
          )}
          {section.configuration.cta_label && section.configuration.cta_url && (
            <Link
              href={section.configuration.cta_url}
              className="mt-8 inline-block border-b border-ink pb-1 text-xs font-semibold uppercase tracking-widest"
            >
              {section.configuration.cta_label}
            </Link>
          )}
        </div>
      </div>
    </section>
  );
}
