"use client";

import Link from "next/link";
import { useState } from "react";
import { MobileNavigation, type NavigationItem } from "@/components/layout/MobileNavigation";
import { useCart } from "@/features/cart";
import type { StorefrontSettings } from "@/types/catalog";

const mainLinks: NavigationItem[] = [
  { href: "/products", label: "Semua Produk" },
  { href: "/category/kerudung", label: "Kerudung" },
  { href: "/category/pashmina", label: "Pashmina" },
  { href: "/products?sort=newest", label: "Produk Terbaru" },
  { href: "/products?sort=best_selling", label: "Terlaris" },
];

const desktopNavItemClass =
  "desktop-nav-item focus-ring flex h-full items-center hover:text-plum";

function SearchIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.5]"><circle cx="10.8" cy="10.8" r="7" /><path d="m16 16 5 5" /></svg>;
}

function BagIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.35]"><path d="M4.5 7.5h15l-.7 13h-13.6l-.7-13Z" /><path d="M8.5 8V5.5a3.5 3.5 0 0 1 7 0V8" /></svg>;
}

function AccountIcon() {
  return <svg aria-hidden viewBox="0 0 24 24" className="h-6 w-6 fill-none stroke-current stroke-[1.4]"><circle cx="12" cy="7" r="4" /><path d="M4.5 21v-2a7.5 7.5 0 0 1 15 0v2" /></svg>;
}

export function Header({ settings }: { settings?: StorefrontSettings }) {
  const [open, setOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const { cart, openDrawer } = useCart();
  const mobileLinks: NavigationItem[] = [
    ...mainLinks,
    { href: "/account", label: "Akun Saya" },
  ];

  return (
    <>
      <header className="sticky top-0 z-40 bg-paper/95 backdrop-blur-md">
        {settings?.announcement_is_active && settings.announcement_text && (
          <Link
            href={settings.announcement_url ?? "/products"}
            className="block bg-ink px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-[.16em] text-paper hover:text-rose"
          >
            {settings.announcement_text}
          </Link>
        )}
        <div className="border-b border-line">
          <div className="page-shell flex h-[4.5rem] items-center justify-between">
          <button aria-label="Buka menu" onClick={() => setOpen(true)} className="focus-ring p-2 xl:hidden">
            <span className="block h-px w-6 bg-ink" /><span className="mt-1.5 block h-px w-6 bg-ink" />
          </button>

          <nav className="hidden h-full items-center gap-6 xl:flex" aria-label="Navigasi utama">
            {mainLinks.map((link) => (
              <Link className={desktopNavItemClass} key={link.label} href={link.href}>{link.label}</Link>
            ))}
          </nav>

          <Link href="/" aria-label="Noure home" className="focus-ring editorial-title absolute left-1/2 -translate-x-1/2 text-2xl tracking-[.18em]">NOURE</Link>

          <div className="flex items-center gap-5">
            <button type="button" aria-label="Cari" onClick={() => setSearchOpen((value) => !value)} className="focus-ring hover:text-plum"><SearchIcon /></button>
            <button type="button" aria-label={`Keranjang${cart?.item_count ? `, ${cart.item_count} produk` : ""}`} onClick={openDrawer} className="focus-ring relative hover:text-plum">
              <BagIcon />
              {!!cart?.item_count && <span className="absolute -right-2 -top-2 grid h-4 min-w-4 place-items-center rounded-full bg-ink px-1 text-[9px] text-paper">{cart.item_count}</span>}
            </button>
            <Link href="/account" aria-label="Akun saya" className="focus-ring hidden hover:text-plum sm:block"><AccountIcon /></Link>
          </div>
          </div>
        </div>
        {searchOpen && (
          <form action="/search" className="border-t border-line bg-paper">
            <div className="page-shell flex items-center py-4">
              <SearchIcon />
              <input autoFocus name="q" aria-label="Cari produk" placeholder="Cari produk Noure" className="ml-4 w-full bg-transparent py-2 text-sm outline-none" />
              <button className="text-xs font-semibold uppercase tracking-widest">Cari</button>
            </div>
          </form>
        )}
      </header>
      <MobileNavigation open={open} onClose={() => setOpen(false)} links={mobileLinks} />
    </>
  );
}
