"use client";
export function QuantitySelector({
  quantity,
  onChange,
  disabled = false,
}: {
  quantity: number;
  onChange: (quantity: number) => void;
  disabled?: boolean;
}) {
  return (
    <div
      className="inline-flex items-center border border-line"
      aria-label="Quantity"
    >
      <button
        type="button"
        disabled={disabled || quantity <= 1}
        onClick={() => onChange(quantity - 1)}
        className="focus-ring h-10 w-10 text-lg disabled:opacity-30"
        aria-label="Decrease quantity"
      >
        −
      </button>
      <span className="grid h-10 w-10 place-items-center border-x border-line text-sm">
        {quantity}
      </span>
      <button
        type="button"
        disabled={disabled}
        onClick={() => onChange(quantity + 1)}
        className="focus-ring h-10 w-10 text-lg disabled:opacity-30"
        aria-label="Increase quantity"
      >
        +
      </button>
    </div>
  );
}
