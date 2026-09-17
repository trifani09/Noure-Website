import Link from "next/link";
import { AuthShell } from "@/components/auth/AuthShell";
export default function ForgotPasswordPage() {
  return (
    <AuthShell eyebrow="Account assistance" title="Forgot password">
      <p className="text-sm leading-7 text-muted">
        Password recovery is not part of the current authentication API. Please
        contact Noure customer care for secure account recovery.
      </p>
      <Link
        href="/login"
        className="focus-ring mt-8 block bg-ink px-5 py-4 text-center text-xs font-semibold uppercase tracking-[.18em] text-paper"
      >
        Return to sign in
      </Link>
    </AuthShell>
  );
}
