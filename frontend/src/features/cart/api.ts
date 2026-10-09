import type { Cart } from "./types";

const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
).replace(/\/$/, "");
type Envelope<T> = {
  data: T;
  message?: string | null;
  meta?: { errors?: { code?: string; message: string }[] };
};

const cartErrorMessages: Record<string, string> = {
  variant_not_found: "Varian produk tidak ditemukan.",
  variant_unavailable: "Varian ini sedang tidak tersedia.",
  insufficient_stock: "Jumlah yang dipilih melebihi stok yang tersedia.",
  cart_item_not_found: "Produk tidak ditemukan di keranjang.",
  currency_conflict: "Produk dengan mata uang berbeda tidak dapat digabungkan.",
  discount_not_found: "Kode promo tidak ditemukan.",
  discount_inactive: "Kode promo sedang tidak aktif.",
  discount_expired: "Kode promo sudah kedaluwarsa.",
  discount_minimum_not_met: "Nilai belanja belum memenuhi minimum promo.",
  discount_usage_limit_reached: "Kuota penggunaan promo sudah habis.",
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
  if (!response.ok) {
    const error = payload?.meta?.errors?.[0];
    throw new CartApiError(
      response.status,
      (error?.code ? cartErrorMessages[error.code] : null) ??
        error?.message ??
        payload?.message ??
        "Keranjang tidak dapat diperbarui.",
    );
  }
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
