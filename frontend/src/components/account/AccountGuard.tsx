"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { useAuth } from "@/components/auth/AuthProvider";

export function AccountGuard({ children }: { children: React.ReactNode }) {
  const { customer, loading } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  useEffect(() => { if (!loading && !customer) router.replace(`/login?next=${encodeURIComponent(pathname)}`); }, [customer, loading, pathname, router]);
  if (loading) return <div className="page-shell section-space text-center text-sm text-muted">Loading your account...</div>;
  if (!customer) return null;
  return <>{children}</>;
}

export function AccountNav() {
  return <nav className="flex flex-wrap gap-x-6 gap-y-3 border-b border-line pb-5 text-xs font-semibold uppercase tracking-[.14em] text-muted"><Link className="hover:text-ink" href="/account">Overview</Link><Link className="hover:text-ink" href="/account/profile">Profile</Link><Link className="hover:text-ink" href="/account/addresses">Addresses</Link><Link className="hover:text-ink" href="/account/orders">Orders</Link></nav>;
}