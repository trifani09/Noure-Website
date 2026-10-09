"use client";

import { useState } from "react";
import Link from "next/link";
import { SafeImage } from "@/components/common/SafeImage";
import { formatMoney } from "@/lib/format";
import { QuantitySelector } from "./QuantitySelector";
import type { CartItem as CartItemType } from "./types";

export function CartItem({ item, onUpdate, onRemove }: {
  item: CartItemType;
  onUpdate: (quantity: number) => Promise<void>;
  onRemove: () => Promise<void>;
}) {
  const [pending, setPending] = useState(false);
  const [confirmRemove, setConfirmRemove] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  async function run(action: () => Promise<void>) {
    setPending(true);
    setActionError(null);
    try {
      await action();
    } catch (error) {
      setActionError(error instanceof Error ? error.message : "Perubahan tidak dapat disimpan.");
    } finally {
      setPending(false);
    }
  }

  const stockText = item.variant.inventory_status === "out_of_stock"
    ? "Stok habis"
    : item.variant.inventory_status === "low_stock"
      ? `Stok terbatas: ${item.variant.available_quantity} tersisa`
      : "Stok tersedia";

  return (
    <article className="flex gap-4 border-b border-line py-5">
      <Link href={`/product/${item.product.slug}`} className="relative aspect-[4/5] w-24 shrink-0 overflow-hidden bg-ivory">
        {item.image && <SafeImage fill sizes="96px" className="object-cover" src={item.image.url} alt={item.image.alt_text ?? item.product.name} />}
      </Link>
      <div className="min-w-0 flex-1">
        <div className="flex justify-between gap-3">
          <div>
            <Link href={`/product/${item.product.slug}`} className="font-serif text-lg hover:text-plum">{item.product.name}</Link>
            <p className="mt-1 text-xs text-muted">
              {item.variant.selected_options.map((option) => `${option.option_name}: ${option.value_label}`).join(" · ") || item.variant.title}
            </p>
            <p className={`mt-2 text-xs ${item.variant.inventory_status === "in_stock" ? "text-muted" : "text-plum"}`}>{stockText}</p>
          </div>
          {!confirmRemove ? (
            <button type="button" disabled={pending} onClick={() => setConfirmRemove(true)} className="focus-ring self-start text-xs uppercase tracking-widest text-muted hover:text-ink disabled:opacity-40">Hapus</button>
          ) : (
            <div className="text-right text-xs">
              <p>Hapus produk ini?</p>
              <div className="mt-2 flex justify-end gap-3">
                <button type="button" disabled={pending} onClick={() => setConfirmRemove(false)} className="focus-ring underline underline-offset-4">Batal</button>
                <button type="button" disabled={pending} onClick={() => void run(onRemove)} className="focus-ring font-semibold text-plum underline underline-offset-4">{pending ? "Menghapus…" : "Ya, hapus"}</button>
              </div>
            </div>
          )}
        </div>
        <p className="mt-4 text-xs text-muted">{formatMoney(item.unit_price_amount, item.currency)} / item</p>
        <div className="mt-2 flex items-center justify-between gap-3">
          <QuantitySelector quantity={item.quantity} max={item.variant.available_quantity} disabled={pending || item.variant.inventory_status === "out_of_stock"} onChange={(quantity) => void run(() => onUpdate(quantity))} />
          <p className="text-sm font-medium">{formatMoney(item.subtotal_amount, item.currency)}</p>
        </div>
        {actionError && <p role="alert" className="mt-2 text-xs text-red-700">{actionError}</p>}
      </div>
    </article>
  );
}
