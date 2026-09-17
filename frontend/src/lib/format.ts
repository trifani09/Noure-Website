const zeroDecimalCurrencies = new Set(["IDR", "JPY", "KRW", "VND"]);

export function formatMoney(amount: number, currency: string) {
  const isZeroDecimal = zeroDecimalCurrencies.has(currency.toUpperCase());
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency,
    maximumFractionDigits: isZeroDecimal ? 0 : 2,
  }).format(isZeroDecimal ? amount : amount / 100);
}
