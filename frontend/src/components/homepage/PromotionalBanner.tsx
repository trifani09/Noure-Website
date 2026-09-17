import Image from "next/image";
import Link from "next/link";
import type { HomepageSection } from "@/types/catalog";
export function PromotionalBanner({ section }: { section: HomepageSection }) {
  const banner = section.banners[0];
  if (!banner) return null;
  return (
    <section className="page-shell py-12">
      <div className="relative min-h-[28rem] overflow-hidden bg-espresso">
        <Image
          fill
          sizes="100vw"
          className="object-cover opacity-80"
          src={banner.desktop_image_url}
          alt={banner.alt_text ?? banner.headline ?? section.name}
        />
        <div className="absolute inset-0 bg-ink/35" />
        <div className="relative flex min-h-[28rem] items-center justify-center p-8 text-center text-paper">
          <div className="max-w-2xl">
            {banner.headline && (
              <h2 className="editorial-title text-5xl md:text-6xl">
                {banner.headline}
              </h2>
            )}
            {banner.subheading && (
              <p className="mx-auto mt-5 max-w-xl text-sm leading-7">
                {banner.subheading}
              </p>
            )}
            {banner.cta_label && banner.cta_url && (
              <Link
                href={banner.cta_url}
                className="mt-7 inline-block border-b border-paper pb-1 text-xs uppercase tracking-widest"
              >
                {banner.cta_label}
              </Link>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
