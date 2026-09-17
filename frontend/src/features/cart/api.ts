import type { Cart } from "./types";

const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").replace(/\/$/, "");
type Envelope<T> = { data: T; message?: string | null; meta?: { errors?: { message: string }[] } };

export class CartApiError extends Error {
  constructor(public status: number, message: string) { super(message); }
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}/v1${path}`, {
    ...init,
    credentials: "include",
    headers: { Accept: "application/json", "Content-Type": "application/json", ...init?.headers },
  });
  const payload = await response.json().catch(() => null) as Envelope<T> | null;
  if (!response.ok) throw new CartApiError(response.status, payload?.meta?.errors?.[0]?.message ?? payload?.message ?? "The cart could not be updated.");
  return payload?.data as T;
}

export const getCart = () => request<Cart>("/cart");
export const addCartItem = (variantPublicId: string, quantity: number) => request<Cart>("/cart/items", { method: "POST", body: JSON.stringify({ variant_public_id: variantPublicId, quantity }) });
export const updateCartItem = (id: number, quantity: number) => request<Cart>(`/cart/items/${id}`, { method: "PUT", body: JSON.stringify({ quantity }) });
export const removeCartItem = (id: number) => request<void>(`/cart/items/${id}`, { method: "DELETE" });