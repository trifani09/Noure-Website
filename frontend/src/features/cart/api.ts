import type { Cart } from "./types";

const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
).replace(/\/$/, "");
type Envelope<T> = {
  data: T;
  message?: string | null;
  meta?: { errors?: { message: string }[] };
};

export class CartApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
  }
}

function csrfToken() {
  if (typeof document === "undefined") return undefined;
  const cookie = document.cookie
    .split("; ")
    .find((item) => item.startsWith("XSRF-TOKEN="));
  return cookie
    ? decodeURIComponent(cookie.split("=").slice(1).join("="))
    : undefined;
}

async function csrf() {
  await fetch(`${API_URL.replace(/\/api$/, "")}/sanctum/csrf-cookie`, {
    credentials: "include",
  });
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = csrfToken();
  const response = await fetch(`${API_URL}/v1${path}`, {
    ...init,
    credentials: "include",
    headers: {
      Accept: "application/json",
      ...(init.body ? { "Content-Type": "application/json" } : {}),
      ...(token ? { "X-XSRF-TOKEN": token } : {}),
      ...init.headers,
    },
  });
  const payload = (await response
    .json()
    .catch(() => null)) as Envelope<T> | null;
  if (!response.ok)
    throw new CartApiError(
      response.status,
      payload?.meta?.errors?.[0]?.message ??
        payload?.message ??
        "The cart could not be updated.",
    );
  return payload?.data as T;
}

export const getCart = () => request<Cart>("/cart");
export async function addCartItem(variantPublicId: string, quantity: number) {
  await csrf();
  return request<Cart>("/cart/items", {
    method: "POST",
    body: JSON.stringify({ variant_public_id: variantPublicId, quantity }),
  });
}
export async function updateCartItem(id: number, quantity: number) {
  await csrf();
  return request<Cart>(`/cart/items/${id}`, {
    method: "PUT",
    body: JSON.stringify({ quantity }),
  });
}
export async function removeCartItem(id: number) {
  await csrf();
  return request<void>(`/cart/items/${id}`, { method: "DELETE" });
}
export async function applyCartDiscount(code: string) {
  await csrf();
  return request<Cart>("/cart/discount", {
    method: "POST",
    body: JSON.stringify({ code }),
  });
}
export async function removeCartDiscount() {
  await csrf();
  return request<Cart>("/cart/discount", { method: "DELETE" });
}
