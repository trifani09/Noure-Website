type Props = { authenticated: boolean; name: string; email: string; phone: string; onChange: (field: "name" | "email" | "phone", value: string) => void };
const fieldClass = "focus-ring w-full border border-line bg-paper px-4 py-3 text-sm";
export function CustomerInformation({ authenticated, name, email, phone, onChange }: Props) {
  return <section>
    <h2 className="editorial-title text-3xl">Customer information</h2>
    <div className="mt-5 grid gap-4 sm:grid-cols-2">
      <label className="sm:col-span-2 text-xs font-semibold uppercase tracking-widest">Name<input className={`${fieldClass} mt-2`} value={name} onChange={(event) => onChange("name", event.target.value)} required disabled={authenticated} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Email<input className={`${fieldClass} mt-2`} type="email" value={email} onChange={(event) => onChange("email", event.target.value)} required disabled={authenticated} /></label>
      <label className="text-xs font-semibold uppercase tracking-widest">Phone<input className={`${fieldClass} mt-2`} type="tel" value={phone} onChange={(event) => onChange("phone", event.target.value)} required disabled={authenticated} /></label>
    </div>
  </section>;
}
