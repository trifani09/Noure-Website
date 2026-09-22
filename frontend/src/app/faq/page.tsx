import type { Metadata } from "next";
import Link from "next/link";
import { Breadcrumb } from "@/components/common/Breadcrumb";

export const metadata: Metadata = { title: "Frequently asked questions", description: "Answers to common questions about shopping with Noure." };

const questions = [
  ["How can I find the right size?", "Use the product description and available variant details before adding an item to your bag. If you are between sizes, contact client care before ordering."],
  ["Can I check my order status?", "Signed-in customers can review their current and previous order details from the Orders area in My Account."],
  ["What happens after I place an order?", "We create your order and show its latest payment and fulfilment status. You can safely return to the order page to refresh its status."],
  ["Can an item become unavailable at checkout?", "Yes. Availability is validated again when the order is created so your bag always reflects the latest backend inventory."],
  ["How are prices calculated?", "Product prices and totals are recalculated securely by the backend during checkout; browser totals are never treated as authoritative."],
];

export default function FaqPage() {
  return (
    <main className="page-shell py-8 md:py-14">
      <Breadcrumb items={[{ label: "FAQ" }]} />
      <div className="mx-auto mt-12 max-w-4xl text-center"><p className="eyebrow text-plum">Client care</p><h1 className="editorial-title mt-4 text-6xl md:text-7xl">Questions, answered.</h1><p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-muted">Everything you need for a smoother Noure experience.</p></div>
      <section className="mx-auto mt-14 max-w-4xl border-t border-line">
        {questions.map(([question, answer], index) => (
          <details key={question} className="group border-b border-line py-1">
            <summary className="focus-ring flex cursor-pointer list-none items-center justify-between gap-6 py-6 text-left"><span className="flex items-baseline gap-5"><span className="text-[10px] text-plum">0{index + 1}</span><span className="editorial-title text-2xl">{question}</span></span><span className="text-2xl font-light group-open:rotate-45">+</span></summary>
            <p className="max-w-2xl pb-7 pl-10 text-sm leading-7 text-muted">{answer}</p>
          </details>
        ))}
      </section>
      <div className="mx-auto mt-16 max-w-4xl bg-ivory p-8 text-center md:p-12"><p className="editorial-title text-3xl">Still need a hand?</p><p className="mt-3 text-sm text-muted">Visit client care for the available support channels.</p><Link href="/contact" className="button-primary focus-ring mt-6">Contact us</Link></div>
    </main>
  );
}
