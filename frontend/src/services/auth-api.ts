import type { ApiValidationError, Customer } from "@/types/auth";

const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
).replace(/\/$/, "");
const BACKEND_URL = API_URL.replace(/\/api$/, "");

type Envelope<T> = {
  data: T;
  meta: { errors?: ApiValidationError[] };
  message: string | null;
};

export class AuthApiError extends Error {
  constructor(
    public status: number,
    public errors: ApiValidationError[],
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
  await fetch(`${BACKEND_URL}/sanctum/csrf-cookie`, { credentials: "include" });
}

async function request<T>(
  path: string,
  init: RequestInit = {},
): Promise<Envelope<T>> {
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
    throw new AuthApiError(
      response.status,
      payload?.meta?.errors ?? [],
      payload?.message ?? "Unable to complete the request.",
    );
  }
  return payload as Envelope<T>;
}

export async function loginCustomer(input: {
  email: string;
  password: string;
  remember: boolean;
}) {
  await csrf();
  return (
    await request<Customer>("/auth/login", {
      method: "POST",
      body: JSON.stringify(input),
    })
  ).data;
}

export async function registerCustomer(input: Record<string, string>) {
  await csrf();
  return (
    await request<Customer>("/auth/register", {
      method: "POST",
      body: JSON.stringify(input),
    })
  ).data;
}

export async function currentCustomer() {
  return (await request<Customer>("/auth/me", { cache: "no-store" })).data;
}

export async function logoutCustomer() {
  await csrf();
  await request<null>("/auth/logout", { method: "POST" });
}

export async function updateCustomerProfile(
  input: Pick<Customer, "first_name" | "last_name" | "phone">,
) {
  await csrf();
  return (
    await request<Customer>("/customer/profile", {
      method: "PUT",
      body: JSON.stringify(input),
    })
  ).data;
}
