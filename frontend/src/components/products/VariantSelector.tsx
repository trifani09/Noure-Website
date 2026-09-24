"use client";
import Link from "next/link";
import { useMemo, useState } from "react";
import { formatMoney } from "@/lib/format";
import { QuantitySelector, useCart } from "@/features/cart";
import type { ProductDetail } from "@/types/catalog";

export function VariantSelector({ product }: { product: ProductDetail }) {
  const fallback =
    product.variants.find((item) => item.is_default) ?? product.variants[0];
  const initial = Object.fromEntries(
    fallback?.selected_options.map((item) => [
      item.option_code,
      item.value_code,
    ]) ?? [],
  );
  const [selected, setSelected] = useState<Record<string, string>>(initial);
  const [quantity, setQuantity] = useState(1);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [sizeGuideOpen, setSizeGuideOpen] = useState(false);
  const { addItem, openDrawer } = useCart();
  const options = [...product.options].sort(
    (a, b) => a.sort_order - b.sort_order,
  );
  const variant = useMemo(
    () =>
      product.variants.find(
        (item) =>
          item.selected_options.length === options.length &&
          item.selected_options.every(
            (option) => selected[option.option_code] === option.value_code,
          ),
      ),
    [product.variants, options.length, selected],
  );
  const valueState = (optionCode: string, valueCode: string) => {
    const matches = product.variants.filter(
      (item) =>
        item.selected_options.some(
          (value) =>
            value.option_code === optionCode && value.value_code === valueCode,
        ) &&
        item.selected_options.every(
          (value) =>
            value.option_code === optionCode ||
            !selected[value.option_code] ||
            selected[value.option_code] === value.value_code,
        ),
    );
    return {
      exists: matches.length > 0,
      available: matches.some((item) => item.available),
    };
  };
  async function handleAdd() {
    if (!variant || !variant.available) return;
    setBusy(true);
    setFeedback(null);
    try {
      await addItem(variant.public_id, quantity);
      setFeedback("Added to your bag.");
      openDrawer();
    } catch {
      setFeedback("We could not add this piece right now.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <div>
      <div className="flex flex-wrap items-baseline gap-3">
        <span className="text-xl">
          {variant
            ? formatMoney(variant.price_amount, variant.currency)
            : "Select options"}
        </span>
        {variant?.compare_at_amount && (
          <span className="text-sm text-muted line-through">
            {formatMoney(variant.compare_at_amount, variant.currency)}
          </span>
        )}
      </div>
      <div className="mt-8 space-y-7">
        {options.map((option) => (
          <fieldset key={option.code}>
            <legend className="flex w-full justify-between text-xs font-semibold uppercase tracking-[.16em]">
              <span>{option.name}</span>
              <span className="flex items-center gap-3 font-normal normal-case tracking-normal text-muted">
                {option.code === "size" && (
                  <button
                    type="button"
                    onClick={() => setSizeGuideOpen(true)}
                    className="focus-ring underline underline-offset-4 hover:text-ink"
                  >
                    Size guide
                  </button>
                )}
                <span>
                  {option.values.find(
                    (value) => value.code === selected[option.code],
                  )?.label ?? "Choose"}
                </span>
              </span>
            </legend>
            <div className="mt-3 flex flex-wrap gap-2">
              {[...option.values]
                .sort((a, b) => a.sort_order - b.sort_order)
                .map((value) => {
                  const state = valueState(option.code, value.code);
                  const active = selected[option.code] === value.code;
                  const showSwatch =
                    option.code === "color" || Boolean(value.swatch_value);
                  return (
                    <button
                      type="button"
                      disabled={!state.exists}
                      aria-pressed={active}
                      title={!state.available ? `${value.label} is currently unavailable` : value.label}
                      key={value.code}
                      onClick={() =>
                        setSelected((current) => ({
                          ...current,
                          [option.code]: value.code,
                        }))
                      }
                      className={`focus-ring relative flex min-h-10 items-center gap-2 border px-3 py-2 text-xs ${active ? "border-ink bg-ink text-paper" : "border-line"} ${!state.available ? "opacity-45 after:absolute after:left-2 after:right-2 after:top-1/2 after:h-px after:-rotate-12 after:bg-current" : ""}`}
                    >
                      {showSwatch && (
                        <span
                          aria-hidden
                          className="h-4 w-4 rounded-full border border-current/20 bg-sand"
                          style={value.swatch_value ? { backgroundColor: value.swatch_value } : undefined}
                        />
                      )}
                      {value.label}
                    </button>
                  );
                })}
            </div>
          </fieldset>
        ))}
      </div>
      <div className="mt-8 flex flex-wrap items-center gap-4">
        <QuantitySelector
          quantity={quantity}
          onChange={setQuantity}
          disabled={busy}
        />
        <button
          type="button"
          disabled={!variant?.available || busy}
          onClick={() => void handleAdd()}
          className="button-primary flex-1 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {busy
            ? "Adding..."
            : variant?.available
              ? "Add to cart"
              : "Unavailable"}
        </button>
      </div>
      {variant && (
        <div className="mt-5 flex items-center gap-2 text-xs" role="status">
          <span
            className={`h-2 w-2 rounded-full ${variant.inventory_status === "out_of_stock" ? "bg-muted" : variant.inventory_status === "low_stock" ? "bg-rose" : "bg-green-700"}`}
          />
          <span className={variant.inventory_status === "low_stock" ? "font-semibold text-plum" : "text-muted"}>
            {variant.inventory_status === "out_of_stock"
              ? "Currently out of stock"
              : variant.inventory_status === "low_stock"
                ? "Low stock — order soon"
                : "In stock and ready to order"}
          </span>
        </div>
      )}
      <div className="mt-6 grid grid-cols-2 gap-px border border-line bg-line text-center text-[10px] uppercase tracking-[.12em]">
        <div className="bg-paper px-3 py-4">Secure payment</div>
        <div className="bg-paper px-3 py-4">Shipping at checkout</div>
      </div>
      {feedback && (
        <p role="status" className="mt-4 text-sm text-plum">
          {feedback}
        </p>
      )}
      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-paper/95 p-3 shadow-[0_-8px_30px_rgba(33,28,26,.1)] backdrop-blur lg:hidden">
        <div className="mx-auto flex max-w-lg items-center gap-4">
          <div className="min-w-0 flex-1">
            <p className="truncate text-[10px] uppercase tracking-widest text-muted">
              {variant?.title ?? "Select options"}
            </p>
            <p className="mt-0.5 text-sm font-semibold">
              {variant ? formatMoney(variant.price_amount, variant.currency) : "Choose a variant"}
            </p>
          </div>
          <button
            type="button"
            disabled={!variant?.available || busy}
            onClick={() => void handleAdd()}
            className="button-primary min-w-36 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {busy ? "Adding…" : variant?.available ? "Add to cart" : "Unavailable"}
          </button>
        </div>
      </div>
      {sizeGuideOpen && (
        <div
          className="fixed inset-0 z-[70] grid place-items-center bg-ink/45 p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="size-guide-title"
          onClick={() => setSizeGuideOpen(false)}
        >
          <div
            className="w-full max-w-lg bg-paper p-7 shadow-2xl md:p-10"
            onClick={(event) => event.stopPropagation()}
          >
            <div className="flex items-start justify-between gap-6">
              <div>
                <p className="eyebrow text-plum">Fit assistance</p>
                <h2 id="size-guide-title" className="editorial-title mt-2 text-4xl">Size guide</h2>
              </div>
              <button
                type="button"
                aria-label="Close size guide"
                onClick={() => setSizeGuideOpen(false)}
                className="focus-ring text-2xl"
              >
                ×
              </button>
            </div>
            <p className="mt-6 text-sm leading-7 text-muted">
              Available sizes for this piece are shown below. Check the product description for garment-specific measurements and fit notes.
            </p>
            <div className="mt-6 flex flex-wrap gap-2">
              {product.options.find((option) => option.code === "size")?.values.map((value) => (
                <span key={value.code} className="border border-line px-4 py-2 text-sm">{value.label}</span>
              ))}
            </div>
            <Link href="/contact" className="mt-7 inline-block text-xs font-semibold uppercase tracking-widest underline underline-offset-4">
              Ask for sizing help
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}
