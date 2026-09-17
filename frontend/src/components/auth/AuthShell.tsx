import Link from "next/link";

export function AuthShell({ eyebrow, title, children, footer }: { eyebrow: string; title: string; children: React.ReactNode; footer?: React.ReactNode }) {
  return <div className="page-shell grid min-h-[calc(100vh-8rem)] place-items-center py-16"><section className="w-full max-w-lg border border-line bg-paper p-7 shadow-[0_24px_80px_rgba(58,45,41,.08)] sm:p-12"><p className="eyebrow text-plum">{eyebrow}</p><h1 className="editorial-title mt-3 text-5xl">{title}</h1><div className="mt-9">{children}</div>{footer&&<div className="mt-8 border-t border-line pt-6 text-center text-sm text-muted">{footer}</div>}<Link href="/" className="mt-8 block text-center text-[10px] uppercase tracking-[.18em] text-muted">Return to Noure</Link></section></div>;
}
