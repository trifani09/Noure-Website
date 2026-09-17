"use client";
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
              <span className="font-normal normal-case tracking-normal text-muted">
                {option.values.find(
                  (value) => value.code === selected[option.code],
                )?.label ?? "Choose"}
              </span>
            </legend>
            <div className="mt-3 flex flex-wrap gap-2">
              {[...option.values]
                .sort((a, b) => a.sort_order - b.sort_order)
                .map((value) => {
                  const state = valueState(option.code, value.code);
                  const active = selected[option.code] === value.code;
                  return (
                    <button
                      type="button"
                      disabled={!state.exists}
                      key={value.code}
                      onClick={() =>
                        setSelected((current) => ({
                          ...current,
                          [option.code]: value.code,
                        }))
                      }
                      className={`focus-ring border px-4 py-2 text-xs ${active ? "border-ink bg-ink text-paper" : "border-line"} ${!state.available ? "opacity-45" : ""}`}
                    >
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
      {feedback && (
        <p role="status" className="mt-4 text-sm text-plum">
          {feedback}
        </p>
      )}
    </div>
  );
}
