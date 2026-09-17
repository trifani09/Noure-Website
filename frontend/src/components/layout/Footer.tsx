import Link from "next/link";
export function Footer() {
  return (
    <footer className="mt-24 bg-espresso text-ivory">
      <div className="page-shell grid gap-12 py-16 md:grid-cols-[1.6fr_1fr_1fr_1.4fr]">
        <div>
          <p className="editorial-title text-3xl tracking-[.18em]">NOURE</p>
          <p className="mt-5 max-w-xs text-sm leading-7 text-ivory/65">
            Considered silhouettes for a quietly expressive wardrobe.
          </p>
          <div className="mt-7 flex gap-5 text-xs uppercase tracking-widest">
            <span>Instagram</span>
            <span>Pinterest</span>
          </div>
        </div>
        <div>
          <p className="eyebrow text-ivory/50">Explore</p>
          <div className="mt-5 flex flex-col gap-3 text-sm">
            <Link href="/products">Shop all</Link>
            <Link href="/products?sort=newest">New arrivals</Link>
            <Link href="/#categories">Categories</Link>
            <Link href="/about">About</Link>
          </div>
        </div>
        <div>
          <p className="eyebrow text-ivory/50">Client care</p>
          <div className="mt-5 flex flex-col gap-3 text-sm">
            <Link href="/account">My account</Link>
            <Link href="/contact">Contact</Link>
            <Link href="/faq">FAQ</Link>
            <Link href="/policies/privacy">Privacy</Link>
            <Link href="/policies/terms">Terms</Link>
          </div>
        </div>
        <div>
          <p className="eyebrow text-ivory/50">Private notes</p>
          <p className="mt-5 text-sm leading-6 text-ivory/65">
            Receive new collection stories and considered edits.
          </p>
          <form className="mt-5 flex border-b border-ivory/40">
            <input
              type="email"
              aria-label="Email address"
              placeholder="Email address"
              className="w-full bg-transparent py-3 text-sm outline-none placeholder:text-ivory/40"
            />
            <button type="button" className="text-xs uppercase tracking-widest">
              Join
            </button>
          </form>
        </div>
      </div>
      <div className="border-t border-ivory/10 py-5 text-center text-[10px] uppercase tracking-[.2em] text-ivory/40">
        © {new Date().getFullYear()} Noure. All rights reserved.
      </div>
    </footer>
  );
}
