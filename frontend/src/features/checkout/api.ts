import type { Checkout, Order, ShippingAddress } from "./types";

const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").replace(/\/$/, "");
type Envelope<T> = { data: T; message?: string | null; meta?: { errors?: Array<{ code: string; field?: string; message: string }> } };

export class CheckoutApiError extends Error {
  constructor(public status: number, public code: string, message: string) {
    super(message);
  }
}
function csrfToken() {
  const cookie = document.cookie.split("; ").find((item) => item.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.split("=").slice(1).join("=")) : undefined;
}
async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_URL}/v1${path}`, {
    ...init,
    credentials: "include",
    headers: { Accept: "application/json", ...(init.body ? { "Content-Type": "application/json" } : {}), ...(csrfToken() ? { "X-XSRF-TOKEN": csrfToken()! } : {}), ...init.headers },
  });
  const payload = (await response.json().catch(() => null)) as Envelope<T> | null;
  if (!response.ok) {
    const error = payload?.meta?.errors?.[0];
    throw new CheckoutApiError(response.status, error?.code ?? "server_error", error?.message ?? payload?.message ?? "Checkout could not be completed.");
  }
  return payload!.data;
}
export const getCheckout = () => request<Checkout>("/checkout");
export async function placeOrder(input: { name?: string; email?: string; phone?: string; address_public_id?: string; shipping_address?: ShippingAddress }) {
  await fetch(`${API_URL.replace(/\/api$/, "")}/sanctum/csrf-cookie`, { credentials: "include" });
  return request<Order>("/orders", { method: "POST", body: JSON.stringify(input) });
}
