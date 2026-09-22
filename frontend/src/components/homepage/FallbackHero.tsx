import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
export function FallbackHero() {
  return (
    <section className="relative min-h-[76vh] overflow-hidden bg-espresso md:min-h-[86vh]">
      <SafeImage
        fill
        priority
        sizes="100vw"
        className="object-cover object-[68%_center] opacity-95 md:object-center"
        src="/images/editorial/noure-campaign-hero.png"
        alt="Noure campaign collection"
      />
      <div className="absolute inset-0 bg-gradient-to-r from-ink/80 via-ink/30 to-transparent" />
      <div className="page-shell relative flex min-h-[76vh] items-end py-16 md:min-h-[86vh] md:items-center">
        <div className="max-w-2xl text-paper">
          <p className="eyebrow text-paper/75">The Noure edit · 2026</p>
          <h1 className="editorial-title mt-5 text-6xl leading-[.9] md:text-8xl">
            Modern essentials,
            <br />
            thoughtfully designed
          </h1>
          <p className="mt-7 max-w-md text-sm leading-7 text-paper/80">
            Refined layers, fluid silhouettes, and enduring pieces created for
            the way you move through every day.
          </p>
          <Link
            href="/products"
            className="button-primary focus-ring mt-9 bg-paper text-ink hover:bg-ivory"
          >
            Shop collection
          </Link>
          <Link
            href="/about"
            className="focus-ring ml-7 inline-block border-b border-paper/60 pb-1 text-xs font-semibold uppercase tracking-[.16em] text-paper"
          >
            Our story
          </Link>
        </div>
      </div>
    </section>
  );
}
