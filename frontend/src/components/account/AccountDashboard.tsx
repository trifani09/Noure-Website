"use client";

import Link from "next/link";
import { useAuth } from "@/components/auth/AuthProvider";
import { AccountNav } from "./AccountGuard";

export function AccountDashboard() {
  const { customer, logout } = useAuth();
  return <div className="page-shell section-space"><AccountNav /><div className="mt-14 max-w-3xl"><p className="eyebrow text-plum">Your account</p><h1 className="editorial-title mt-3 text-5xl md:text-6xl">Welcome, {customer?.first_name}.</h1><p className="mt-5 max-w-xl text-muted">A quiet place to manage your details, delivery addresses, and Noure orders.</p><div className="mt-12 grid gap-px border border-line bg-line md:grid-cols-3">{[['Profile', 'Update your personal details.', '/account/profile'], ['Addresses', 'Keep delivery details ready.', '/account/addresses'], ['Orders', 'Follow your Noure purchases.', '/account/orders']].map(([title, body, href]) => <Link href={href} key={href} className="bg-paper p-6 hover:bg-ivory"><h2 className="editorial-title text-2xl">{title}</h2><p className="mt-3 text-sm text-muted">{body}</p><span className="mt-8 block text-xs font-semibold uppercase tracking-[.16em]">Open</span></Link>)}</div><button onClick={() => void logout()} className="mt-12 border-b border-ink pb-1 text-xs font-semibold uppercase tracking-[.16em]">Log out</button></div></div>;
}