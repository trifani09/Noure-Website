import type { Metadata } from "next";
import Link from "next/link";
import { Breadcrumb } from "@/components/common/Breadcrumb";
import { SafeImage } from "@/components/common/SafeImage";

export const metadata: Metadata = {
  title: "About Noure",
  description: "Discover Noure's approach to considered, quietly expressive womenswear.",
};

const values = [
  ["Intentional", "Every line, layer, and detail begins with how a piece will feel in real life."],
  ["Enduring", "We favour versatile forms and a restrained palette that move beyond a single season."],
  ["Expressive", "Quiet does not mean invisible. Noure is designed to feel distinctly your own."],
];

export default function AboutPage() {
  return (
    <main>
      <div className="page-shell pt-8"><Breadcrumb items={[{ label: "About" }]} /></div>
      <section className="page-shell grid items-center gap-12 py-12 md:grid-cols-[.9fr_1.1fr] md:py-20">
        <div className="order-2 max-w-xl md:order-1">
          <p className="eyebrow text-plum">The house of Noure</p>
          <h1 className="editorial-title mt-5 text-6xl leading-[.95] md:text-8xl">Clothes with a quiet point of view.</h1>
          <p className="mt-7 text-sm leading-8 text-muted">Noure is built around thoughtful silhouettes, graceful movement, and the confidence that comes from feeling at ease in what you wear.</p>
          <Link href="/products" className="button-primary focus-ring mt-9">Explore the collection</Link>
        </div>
        <div className="relative order-1 aspect-[4/5] overflow-hidden md:order-2">
          <SafeImage fill priority sizes="(max-width: 768px) 100vw, 55vw" className="object-cover" src="/images/editorial/noure-brand-story.png" alt="Noure editorial collection" />
        </div>
      </section>
      <section className="bg-espresso py-20 text-ivory md:py-28">
        <div className="page-shell">
          <p className="eyebrow text-rose">Our principles</p>
          <div className="mt-10 grid gap-px bg-ivory/15 md:grid-cols-3">
            {values.map(([title, copy], index) => (
              <article key={title} className="bg-espresso p-8 md:p-10">
                <span className="text-xs text-rose">0{index + 1}</span>
                <h2 className="editorial-title mt-12 text-4xl">{title}</h2>
                <p className="mt-4 text-sm leading-7 text-ivory/65">{copy}</p>
              </article>
            ))}
          </div>
        </div>
      </section>
    </main>
  );
}
