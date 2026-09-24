import { NewsletterForm } from "@/components/common/NewsletterForm";

export function NewsletterSection() {
  return (
    <section className="page-shell py-20 md:py-28">
      <div className="border border-line bg-ivory px-6 py-16 text-center md:px-12 md:py-24">
        <p className="eyebrow text-plum">Notes from Noure</p>
        <h2 className="editorial-title mx-auto mt-4 max-w-2xl text-5xl md:text-6xl">
          A considered edit, delivered quietly
        </h2>
        <p className="mx-auto mt-5 max-w-lg text-sm leading-7 text-muted">
          Be the first to discover new arrivals and collection stories.
        </p>
        <div className="mx-auto mt-9 max-w-md text-left">
          <NewsletterForm source="homepage" />
        </div>
      </div>
    </section>
  );
}
