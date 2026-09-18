import type { Payment } from "./types";
const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").replace(/\/$/, "");
type Envelope<T> = { data: T; message?: string | null; meta?: { errors?: Array<{ code: string; message: string }> } };
export class PaymentApiError extends Error { constructor(public status: number, public code: string, message: string) { super(message); } }
function token() { const value = document.cookie.split("; ").find((item) => item.startsWith("XSRF-TOKEN=")); return value ? decodeURIComponent(value.split("=").slice(1).join("=")) : undefined; }
async function request(path: string, init: RequestInit = {}) {
  const response = await fetch(`${API_URL}/v1${path}`, { ...init, credentials: "include", headers: { Accept: "application/json", ...(token() ? { "X-XSRF-TOKEN": token()! } : {}), ...init.headers } });
  const payload = await response.json().catch(() => null) as Envelope<Payment> | null;
  if (!response.ok) { const error = payload?.meta?.errors?.[0]; throw new PaymentApiError(response.status, error?.code ?? "payment_error", error?.message ?? payload?.message ?? "Payment could not be loaded."); }
  return payload!.data;
}
export async function createPayment(orderId: string) {
  await fetch(`${API_URL.replace(/\/api$/, "")}/sanctum/csrf-cookie`, { credentials: "include" });
  return request(`/orders/${orderId}/payment`, { method: "POST", headers: { "Idempotency-Key": `order:${orderId}:payment` } });
}
export const getPayment = (orderId: string) => request(`/orders/${orderId}/payment`);
