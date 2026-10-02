"use client";

import Link from "next/link";
import { Suspense, FormEvent, useState } from "react";
import { useSearchParams } from "next/navigation";
import { AuthShell } from "@/components/auth/AuthShell";
import { FormField } from "@/components/auth/FormField";
import { AuthApiError, resetCustomerPassword } from "@/services/auth-api";

export default function ResetPasswordPage() {
  return (
    <Suspense
      fallback={
        <AuthShell eyebrow="Account assistance" title="Reset password">
          <p className="text-sm text-muted">Loading reset link…</p>
        </AuthShell>
      }
    >
      <ResetPasswordForm />
    </Suspense>
  );
}

function ResetPasswordForm() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const email = searchParams.get("email") ?? "";
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");
    setError("");
    setSaving(true);
    const form = new FormData(event.currentTarget);
    const submittedEmail = String(form.get("email") ?? "");
    const password = String(form.get("password") ?? "");

    try {
      const response = await resetCustomerPassword({
        email: submittedEmail,
        token,
        password,
        password_confirmation: String(form.get("password_confirmation") ?? ""),
      });
      setMessage(response.message ?? "Your password has been reset successfully.");
    } catch (caught) {
      setError(
        caught instanceof AuthApiError
          ? caught.message
          : "Unable to reset your password. Please try again.",
      );
    } finally {
      setSaving(false);
    }
  }

  return (
    <AuthShell eyebrow="Account assistance" title="Reset password">
      {message ? (
        <div role="status" className="space-y-6 text-sm leading-7 text-muted">
          <p>{message}</p>
          <Link href="/login" className="inline-block text-ink underline underline-offset-4">
            Return to sign in
          </Link>
        </div>
      ) : (
        <form onSubmit={submit} className="space-y-5">
          {error && <p role="alert" className="border border-rose/60 bg-ivory p-3 text-sm text-plum">{error}</p>}
          <FormField label="Email" name="email" type="email" autoComplete="email" required defaultValue={email} />
          <FormField label="New password" name="password" type="password" autoComplete="new-password" minLength={8} required />
          <FormField label="Confirm new password" name="password_confirmation" type="password" autoComplete="new-password" minLength={8} required />
          <button disabled={saving || !token} className="focus-ring w-full bg-ink px-5 py-4 text-xs font-semibold uppercase tracking-[.18em] text-paper disabled:opacity-50">
            {saving ? "Resetting…" : "Update password"}
          </button>
          {!token && <p className="text-sm text-plum">This reset link is missing its token. Request a new one.</p>}
          <Link href="/forgot-password" className="block text-center text-sm underline underline-offset-4">
            Request a new reset link
          </Link>
        </form>
      )}
    </AuthShell>
  );
}