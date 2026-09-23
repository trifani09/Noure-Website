"use client";

import Link from "next/link";
import { useState } from "react";
import { useCart } from "@/features/cart";

export function ProductCardAction({
  slug,
  productName,
  variantId,
  available,
}: {
  slug: string;
  productName: string;
  variantId: string | null;
  available: boolean;
}) {
  const { addItem, openDrawer } = useCart();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(false);

  async function quickAdd() {
    if (!variantId || busy) return;
    setBusy(true);
    setError(false);
    try {
      await addItem(variantId, 1);
      openDrawer();
    } catch {
      setError(true);
    } finally {
      setBusy(false);
    }
  }

  if (!available) {
    return (
      <span className="block w-full bg-paper/95 px-3 py-3 text-center text-[10px] font-semibold uppercase tracking-[.16em] text-muted">
        Sold out
      </span>
    );
  }

  if (variantId) {
    return (
      <button
        type="button"
        disabled={busy}
        onClick={() => void quickAdd()}
        aria-label={`Quick add ${productName} to cart`}
        className="focus-ring block w-full bg-paper/95 px-3 py-3 text-center text-[10px] font-semibold uppercase tracking-[.16em] hover:bg-ink hover:text-paper disabled:cursor-wait disabled:opacity-70"
      >
        {busy ? "Adding…" : error ? "Try again" : "Quick add"}
      </button>
    );
  }

  return (
    <Link
      href={`/product/${slug}`}
      className="focus-ring block w-full bg-paper/95 px-3 py-3 text-center text-[10px] font-semibold uppercase tracking-[.16em] hover:bg-ink hover:text-paper"
      aria-label={`Choose options for ${productName}`}
    >
      Choose options
    </Link>
  );
}
