import type { Metadata } from "next";
import Link from "next/link";
import { Breadcrumb } from "@/components/common/Breadcrumb";

export const metadata: Metadata = { title: "Contact", description: "Contact Noure client care." };

export default function ContactPage() {
  return (
    <main className="page-shell py-8 md:py-14">
      <Breadcrumb items={[{ label: "Contact" }]} />
      <section className="mt-10 grid overflow-hidden border border-line lg:grid-cols-[1.1fr_.9fr]">
        <div className="bg-ivory p-8 md:p-14 lg:p-20">
          <p className="eyebrow text-plum">Client care</p>
          <h1 className="editorial-title mt-4 text-6xl md:text-7xl">How can we help?</h1>
          <p className="mt-6 max-w-lg text-sm leading-8 text-muted">Questions about a piece, sizing, or an existing order? Choose the topic closest to what you need and our team will help point you in the right direction.</p>
          <Link href="/faq" className="mt-9 inline-block border-b border-ink pb-1 text-xs font-semibold uppercase tracking-[.16em]">Browse common questions</Link>
        </div>
        <div className="bg-espresso p-8 text-ivory md:p-14 lg:p-20">
          <p className="eyebrow text-rose">Get in touch</p>
          <div className="mt-8 divide-y divide-ivory/15 border-y border-ivory/15">
            <div className="py-7"><p className="text-xs uppercase tracking-widest text-ivory/45">Orders & products</p><p className="mt-2 text-sm">Use your account for live order details.</p><Link href="/account/orders" className="mt-3 inline-block text-xs underline underline-offset-4">View my orders</Link></div>
            <div className="py-7"><p className="text-xs uppercase tracking-widest text-ivory/45">General enquiries</p><p className="mt-2 text-sm text-ivory/75">Official email and WhatsApp details will appear here once configured.</p></div>
            <div className="py-7"><p className="text-xs uppercase tracking-widest text-ivory/45">Availability</p><p className="mt-2 text-sm text-ivory/75">Monday–Friday · 09:00–17:00 WIB</p></div>
          </div>
        </div>
      </section>
    </main>
  );
}
