"use client";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { useAuth } from "@/components/auth/AuthProvider";
import { AuthShell } from "@/components/auth/AuthShell";
import { FormField } from "@/components/auth/FormField";
import { AuthApiError } from "@/services/auth-api";
export default function RegisterPage() {
  const { customer, loading: sessionLoading, register } = useAuth();
  const router = useRouter();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState("");
  const [saving, setSaving] = useState(false);
  useEffect(() => {
    if (!sessionLoading && customer) router.replace("/account");
  }, [customer, sessionLoading, router]);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setErrors({});
    setMessage("");
    setSaving(true);
    const data = Object.fromEntries(
      new FormData(event.currentTarget).entries(),
    ) as Record<string, string>;
    try {
      await register(data);
      router.replace("/account");
    } catch (caught) {
      if (caught instanceof AuthApiError) {
        setMessage(caught.message);
        setErrors(
          Object.fromEntries(
            caught.errors
              .filter((item) => item.field)
              .map((item) => [item.field!, item.message]),
          ),
        );
      } else setMessage("Unable to create your account.");
    } finally {
      setSaving(false);
    }
  }
  return (
    <AuthShell
      eyebrow="Join Noure"
      title="Create an account"
      footer={
        <>
          Already registered?{" "}
          <Link className="text-ink underline underline-offset-4" href="/login">
            Sign in
          </Link>
        </>
      }
    >
      <form onSubmit={submit} className="grid gap-5 sm:grid-cols-2">
        {message && (
          <div
            role="alert"
            className="border border-rose/60 bg-ivory p-3 text-sm text-plum sm:col-span-2"
          >
            {message}
          </div>
        )}
        <FormField
          label="First name"
          name="first_name"
          autoComplete="given-name"
          required
          error={errors.first_name}
        />
        <FormField
          label="Last name"
          name="last_name"
          autoComplete="family-name"
          required
          error={errors.last_name}
        />
        <div className="sm:col-span-2">
          <FormField
            label="Email"
            name="email"
            type="email"
            autoComplete="email"
            required
            error={errors.email}
          />
        </div>
        <div className="sm:col-span-2">
          <FormField
            label="Phone"
            name="phone"
            type="tel"
            autoComplete="tel"
            required
            error={errors.phone}
          />
        </div>
        <FormField
          label="Password"
          name="password"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
          error={errors.password}
        />
        <FormField
          label="Confirm password"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
        />
        <p className="text-xs leading-5 text-muted sm:col-span-2">
          Use at least eight characters with uppercase, lowercase, and a number.
        </p>
        <button
          disabled={saving || sessionLoading}
          className="focus-ring bg-ink px-5 py-4 text-xs font-semibold uppercase tracking-[.18em] text-paper disabled:opacity-50 sm:col-span-2"
        >
          {saving ? "Creating account…" : "Create account"}
        </button>
      </form>
    </AuthShell>
  );
}
