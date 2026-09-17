import { cache } from "react";
import type {
  Category,
  Homepage,
  Pagination,
  ProductDetail,
  ProductSummary,
} from "@/types/catalog";
const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
).replace(/\/$/, "");
type Envelope<T> = {
  data: T;
  meta: Record<string, unknown>;
  message: string | null;
};
export class StorefrontApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
  }
}
async function request<T>(path: string): Promise<Envelope<T>> {
  const response = await fetch(`${API_URL}/v1${path}`, {
    headers: { Accept: "application/json" },
    next: { revalidate: 60 },
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok)
    throw new StorefrontApiError(
      response.status,
      payload?.meta?.errors?.[0]?.message ??
        payload?.message ??
        "The storefront could not load this content.",
    );
  return payload as Envelope<T>;
}
export const getHomepage = cache(
  async () => (await request<Homepage>("/homepage")).data,
);
export async function getProducts(
  params: Record<string, string | number | undefined> = {},
) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") query.set(key, String(value));
  });
  const response = await request<ProductSummary[]>(`/products?${query}`);
  return {
    data: response.data,
    pagination: (response.meta as { pagination: Pagination }).pagination,
  };
}
export const getProduct = cache(
  async (slug: string) =>
    (await request<ProductDetail>(`/products/${encodeURIComponent(slug)}`))
      .data,
);
export async function getCategories(
  params: Record<string, string | number> = {},
) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) =>
    query.set(key, String(value)),
  );
  const response = await request<Category[]>(`/categories?${query}`);
  return {
    data: response.data,
    pagination: (response.meta as { pagination: Pagination }).pagination,
  };
}
export const getCategory = cache(
  async (slug: string) =>
    (await request<Category>(`/categories/${encodeURIComponent(slug)}`)).data,
);
