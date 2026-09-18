export function PlaceOrderButton({ pending }: { pending: boolean }) {
  return <button type="submit" disabled={pending} className="button-primary mt-8 w-full disabled:cursor-not-allowed disabled:opacity-50">{pending ? "Placing order..." : "Place order"}</button>;
}
