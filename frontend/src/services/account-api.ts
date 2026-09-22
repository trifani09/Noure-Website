import type { ApiValidationError, Customer } from "@/types/auth";
import type { Address, AddressInput, OrderDetail, OrderSummary } from "@/types/account";

const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").replace(/\/$/, "");
type Envelope<T> = { data: T; meta: { errors?: ApiValidationError[]; pagination?: Pagination }; message: string | null };
type Pagination = { total: number; per_page: number; current_page: number; last_page: number };

export class AccountApiError extends Error {
  constructor(public status: number, public errors: ApiValidationError[], message: string) { super(message); }
}

function csrfToken() {
  if (typeof document === "undefined") return undefined;
  const cookie = document.cookie.split("; ").find((item) => item.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.split("=").slice(1).join("=")) : undefined;
}

async function request<T>(path: string, init: RequestInit = {}) {
  const token = csrfToken();
  const response = await fetch(`${API_URL}/v1${path}`, {
    ...init,
    credentials: "include",
    headers: { Accept: "application/json", ...(init.body ? { "Content-Type": "application/json" } : {}), ...(token ? { "X-XSRF-TOKEN": token } : {}), ...init.headers },
  });
  const payload = (await response.json().catch(() => null)) as Envelope<T> | null;
  if (!response.ok) throw new AccountApiError(response.status, payload?.meta?.errors ?? [], payload?.message ?? "Unable to complete the request.");
  return payload as Envelope<T>;
}

export async function getProfile() { return (await request<Customer>("/customer/profile")).data; }
export async function updateProfile(input: Pick<Customer, "first_name" | "last_name" | "phone">) { return (await request<Customer>("/customer/profile", { method: "PUT", body: JSON.stringify(input) })).data; }
export async function getAddresses() { return (await request<Address[]>("/customer/addresses")).data; }
export async function createAddress(input: AddressInput) { return (await request<Address>("/customer/addresses", { method: "POST", body: JSON.stringify(input) })).data; }
export async function updateAddress(id: string, input: AddressInput) { return (await request<Address>(`/customer/addresses/${id}`, { method: "PUT", body: JSON.stringify(input) })).data; }
export async function deleteAddress(id: string) { await request<null>(`/customer/addresses/${id}`, { method: "DELETE" }); }
export async function getOrders(page = 1) { const result = await request<OrderSummary[]>(`/customer/orders?page=${page}`); return { data: result.data, pagination: result.meta.pagination! }; }
export async function getOrder(id: string) { return (await request<OrderDetail>(`/customer/orders/${id}`)).data; }