import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import type { HomepageBanner } from "@/types/catalog";
export function HeroBanner({ banner }: { banner: HomepageBanner }) {
  return (
    <section className="relative min-h-[72vh] overflow-hidden bg-espresso md:min-h-[82vh]">
      {" "}
      <SafeImage
        fill
        priority
        sizes="100vw"
        className="object-cover opacity-90"
        src={banner.desktop_image_url}
        fallbackSrc="/images/editorial/noure-campaign-hero.png"
        alt={banner.alt_text ?? banner.headline ?? "Noure collection"}
      />
      <div className="absolute inset-0 bg-gradient-to-r from-ink/60 via-ink/15 to-transparent" />
      <div className="page-shell relative flex min-h-[72vh] items-end py-14 md:min-h-[82vh] md:items-center">
        <div className="max-w-xl text-paper">
          <p className="eyebrow text-paper/75">Noure collection</p>
          {banner.headline && (
            <h1 className="editorial-title mt-5 text-5xl leading-[.95] md:text-7xl">
              {banner.headline}
            </h1>
          )}
          {banner.subheading && (
            <p className="mt-6 max-w-md text-sm leading-7 text-paper/85 md:text-base">
              {banner.subheading}
            </p>
          )}
          {banner.cta_label && banner.cta_url && (
            <Link
              href={banner.cta_url}
              className="focus-ring mt-8 inline-flex bg-paper px-7 py-3.5 text-xs font-semibold uppercase tracking-[.17em] text-ink transition hover:bg-ivory"
            >
              {banner.cta_label}
            </Link>
          )}
        </div>
      </div>
    </section>
  );
}
