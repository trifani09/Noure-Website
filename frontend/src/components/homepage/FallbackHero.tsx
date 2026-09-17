import Image from "next/image";
import Link from "next/link";
import type { ProductSummary } from "@/types/catalog";

export function FallbackHero({ product }: { product?: ProductSummary }) {
  return <section className="relative min-h-[76vh] overflow-hidden bg-espresso md:min-h-[86vh]">
    {product?.primary_image && <Image fill priority sizes="100vw" className="object-cover object-center opacity-85" src={product.primary_image.url} alt={product.primary_image.alt_text ?? product.name}/>} 
    <div className="absolute inset-0 bg-gradient-to-r from-ink/75 via-ink/30 to-ink/5"/>
    <div className="page-shell relative flex min-h-[76vh] items-end py-16 md:min-h-[86vh] md:items-center">
      <div className="max-w-2xl text-paper"><p className="eyebrow text-paper/70">The Noure collection</p><h1 className="editorial-title mt-5 text-6xl leading-[.9] md:text-8xl">Modern essentials,<br/>thoughtfully designed</h1><p className="mt-7 max-w-md text-sm leading-7 text-paper/80">Considered silhouettes for a quietly expressive wardrobe.</p><Link href="/products" className="focus-ring mt-9 inline-flex bg-paper px-8 py-4 text-xs font-semibold uppercase tracking-[.18em] text-ink transition hover:bg-ivory">Shop collection</Link></div>
    </div>
  </section>;
}
