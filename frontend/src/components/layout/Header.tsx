"use client";

import Link from "next/link";
import { useState } from "react";
import { MobileNavigation, type NavigationItem } from "@/components/layout/MobileNavigation";
import { useCart } from "@/features/cart";
import type { Category } from "@/types/catalog";

const mainLinks: NavigationItem[] = [
  { href: "/", label: "Home" },
  { href: "/products?sort=newest", label: "New arrivals" },
  { href: "/products", label: "Best seller" },
  { href: "/products", label: "All product" },
];

function SearchIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.5]"><circle cx="10.8" cy="10.8" r="7" /><path d="m16 16 5 5" /></svg>;
}

function BagIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.35]"><path d="M4.5 7.5h15l-.7 13h-13.6l-.7-13Z" /><path d="M8.5 8V5.5a3.5 3.5 0 0 1 7 0V8" /></svg>;
}

function AccountIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.4]"><circle cx="12" cy="7" r="4" /><path d="M4.5 21v-2a7.5 7.5 0 0 1 15 0v2" /></svg>;
}

export function Header({ categories = [] }: { categories?: Category[] }) {
  const [open, setOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const { cart, openDrawer } = useCart();
  const mobileLinks: NavigationItem[] = [
    ...mainLinks.slice(0, 3),
    ...categories.map((category) => ({ href: `/category/${category.slug}`, label: category.name })),
    mainLinks[3],
    { href: "/account", label: "Account" },
  ];

  return (
    <>
      <header className="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur-md">
        <div className="page-shell flex h-[4.5rem] items-center justify-between">
          <button aria-label="Open menu" onClick={() => setOpen(true)} className="focus-ring p-2 lg:hidden">
            <span className="block h-px w-6 bg-ink" /><span className="mt-1.5 block h-px w-6 bg-ink" />
          </button>

          <nav className="hidden h-full items-center gap-9 lg:flex" aria-label="Main navigation">
            {mainLinks.slice(0, 3).map((link) => (
              <Link className="focus-ring flex h-full items-center text-[13px] uppercase tracking-[.02em] hover:text-plum" key={link.label} href={link.href}>{link.label}</Link>
            ))}
            <div className="group relative flex h-full items-center">
              <button className="focus-ring flex items-center gap-2 text-[13px] uppercase tracking-[.02em] hover:text-plum" type="button">
                Categories
                <svg aria-hidden viewBox="0 0 12 8" className="h-2 w-3 fill-none stroke-current stroke-[1.4]"><path d="m1 1.5 5 5 5-5" /></svg>
              </button>
              <div className="invisible absolute left-0 top-full w-64 translate-y-2 border border-line bg-paper p-3 opacity-0 shadow-[0_18px_45px_rgba(33,28,26,.12)] transition group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                {categories.slice(0, 8).map((category) => (
                  <Link key={category.public_id} href={`/category/${category.slug}`} className="block border-b border-line px-3 py-3 text-xs uppercase tracking-[.08em] last:border-0 hover:bg-ivory hover:text-plum">{category.name}</Link>
                ))}
                <Link href="/products" className="block px-3 py-3 text-xs font-semibold uppercase tracking-[.08em] text-plum">View all categories</Link>
              </div>
            </div>
            <Link className="focus-ring flex h-full items-center text-[13px] uppercase tracking-[.02em] hover:text-plum" href="/products">All product</Link>
          </nav>

          <Link href="/" className="focus-ring editorial-title absolute left-1/2 -translate-x-1/2 text-2xl tracking-[.18em] lg:hidden">NOURE</Link>

          <div className="flex items-center gap-5">
            <button type="button" aria-label="Search" onClick={() => setSearchOpen((value) => !value)} className="focus-ring hover:text-plum"><SearchIcon /></button>
            <button type="button" aria-label={`Bag${cart?.item_count ? `, ${cart.item_count} items` : ""}`} onClick={openDrawer} className="focus-ring relative hover:text-plum">
              <BagIcon />
              {!!cart?.item_count && <span className="absolute -right-2 -top-2 grid h-4 min-w-4 place-items-center rounded-full bg-ink px-1 text-[9px] text-paper">{cart.item_count}</span>}
            </button>
            <Link href="/account" aria-label="Account" className="focus-ring hidden hover:text-plum sm:block"><AccountIcon /></Link>
          </div>
        </div>
        {searchOpen && (
          <form action="/search" className="border-t border-line bg-paper">
            <div className="page-shell flex items-center py-4">
              <SearchIcon />
              <input autoFocus name="q" aria-label="Search products" placeholder="Search the Noure collection" className="ml-4 w-full bg-transparent py-2 text-sm outline-none" />
              <button className="text-xs font-semibold uppercase tracking-widest">Search</button>
            </div>
          </form>
        )}
      </header>
      <MobileNavigation open={open} onClose={() => setOpen(false)} links={mobileLinks} />
    </>
  );
}
