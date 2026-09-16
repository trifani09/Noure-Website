export function PageHeader({ title, description }: { title: string; description?: string }) {
  return (
    <div className="border-b border-stone-200 pb-6">
      <h1 className="text-2xl font-semibold tracking-tight text-stone-950 sm:text-3xl">{title}</h1>
      {description && <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-600">{description}</p>}
    </div>
  )
}
