"use client";

import Link from "next/link";
import { useState } from "react";

const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
).replace(/\/$/, "");

export function NewsletterForm({
  source,
  dark = false,
}: {
  source: "homepage" | "footer";
  dark?: boolean;
}) {
  const [email, setEmail] = useState("");
  const [pending, setPending] = useState(false);
  const [status, setStatus] = useState<{ kind: "success" | "error"; message: string } | null>(null);

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setPending(true);
    setStatus(null);
    try {
      const response = await fetch(`${API_URL}/v1/newsletter/subscriptions`, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ email, source }),
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok) {
        throw new Error(
          response.status === 429
            ? "Too many attempts. Please wait a moment and try again."
            : payload?.meta?.errors?.[0]?.message ?? payload?.message ?? "We could not subscribe you right now.",
        );
      }
      setEmail("");
      setStatus({ kind: "success", message: "You're on the list. Welcome to Noure." });
    } catch (error) {
      setStatus({ kind: "error", message: error instanceof Error ? error.message : "We could not subscribe you right now." });
    } finally {
      setPending(false);
    }
  }

  return (
    <div>
      <form onSubmit={submit} className={`flex border-b ${dark ? "border-ivory/40" : "border-ink"}`}>
        <input
          required
          type="email"
          autoComplete="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          aria-label="Email address"
          placeholder="Email address"
          className={`w-full bg-transparent py-3 text-sm outline-none ${dark ? "placeholder:text-ivory/40" : "placeholder:text-muted"}`}
        />
        <button disabled={pending} className="focus-ring px-3 text-xs font-semibold uppercase tracking-widest disabled:opacity-50">
          {pending ? "Joining…" : "Join"}
        </button>
      </form>
      <p className={`mt-3 text-[10px] leading-5 ${dark ? "text-ivory/45" : "text-muted"}`}>
        By joining, you agree to our{" "}
        <Link href="/policies/privacy" className="underline underline-offset-2">privacy policy</Link>.
      </p>
      {status && (
        <p role="status" aria-live="polite" className={`mt-3 text-xs ${status.kind === "error" ? "text-red-600" : dark ? "text-rose" : "text-plum"}`}>
          {status.message}
        </p>
      )}
    </div>
  );
}
