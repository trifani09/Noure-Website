import Link from "next/link";

type Props = { authenticated: boolean; name: string; email: string; phone: string; onChange: (field: "name" | "email" | "phone", value: string) => void };
const fieldClass = "focus-ring w-full border border-line bg-paper px-4 py-3 text-sm";
export function CustomerInformation({ authenticated, name, email, phone, onChange }: Props) {
  return <section>
    <h2 className="editorial-title text-3xl">{authenticated ? "Customer information" : "Guest checkout"}</h2>
    {!authenticated && <><p className="mt-2 text-sm text-muted">No account is required. We will use these details for your order and delivery updates.</p><p className="mt-4 border-y border-line py-3 text-sm">Already have a Noure account? <Link href="/login?next=/checkout" className="font-semibold underline underline-offset-4">Sign in for a faster checkout</Link>.</p></>}
    <div className="mt-5 grid gap-4 sm:grid-cols-2">
      <label className="sm:col-span-2 text-xs font-semibold uppercase tracking-widest">Name<input className={`${fieldClass} mt-2`} value={name} onChange={(event) => onChange("name", event.target.value)} required disabled={authenticated} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Email<input className={`${fieldClass} mt-2`} type="email" value={email} onChange={(event) => onChange("email", event.target.value)} required disabled={authenticated} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Phone<input className={`${fieldClass} mt-2`} type="tel" value={phone} onChange={(event) => onChange("phone", event.target.value)} required disabled={authenticated} /></label>
    </div>
  </section>;
}
