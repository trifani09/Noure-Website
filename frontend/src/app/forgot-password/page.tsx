"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { AuthShell } from "@/components/auth/AuthShell";
import { FormField } from "@/components/auth/FormField";
import { AuthApiError, requestCustomerPasswordReset } from "@/services/auth-api";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");
    setError("");
    setSaving(true);

    try {
      const response = await requestCustomerPasswordReset(email);
      setMessage(response.message ?? "If the account exists, a reset link has been sent.");
    } catch (caught) {
      setError(
        caught instanceof AuthApiError
          ? caught.message
          : "Unable to request a reset link. Please try again.",
      );
    } finally {
      setSaving(false);
    }
  }

  return (
    <AuthShell eyebrow="Account assistance" title="Forgot password">
      <form onSubmit={submit} className="space-y-5">
        {message && <p role="status" className="text-sm leading-7 text-muted">{message}</p>}
        {error && <p role="alert" className="border border-rose/60 bg-ivory p-3 text-sm text-plum">{error}</p>}
        {!message && (
          <>
            <FormField label="Email" name="email" type="email" autoComplete="email" required value={email} onChange={(event) => setEmail(event.target.value)} />
            <button disabled={saving} className="focus-ring w-full bg-ink px-5 py-4 text-xs font-semibold uppercase tracking-[.18em] text-paper disabled:opacity-50">
              {saving ? "Sending…" : "Send reset link"}
            </button>
          </>
        )}
        <Link href="/login" className="block text-center text-sm underline underline-offset-4">
          Return to sign in
        </Link>
      </form>
    </AuthShell>
  );
}
