"use client";

export function QuantitySelector({ quantity, onChange, disabled = false, max }: {
  quantity: number;
  onChange: (quantity: number) => void;
  disabled?: boolean;
  max?: number;
}) {
  const atMaximum = max !== undefined && quantity >= max;
  return (
    <div className="inline-flex items-center border border-line" aria-label="Jumlah produk">
      <button type="button" disabled={disabled || quantity <= 1} onClick={() => onChange(quantity - 1)} className="focus-ring h-10 w-10 text-lg disabled:opacity-30" aria-label="Kurangi jumlah">−</button>
      <span className="grid h-10 w-10 place-items-center border-x border-line text-sm" aria-live="polite">{quantity}</span>
      <button type="button" disabled={disabled || atMaximum} onClick={() => onChange(quantity + 1)} className="focus-ring h-10 w-10 text-lg disabled:opacity-30" aria-label={atMaximum ? "Jumlah sudah mencapai stok tersedia" : "Tambah jumlah"}>+</button>
    </div>
  );
}
