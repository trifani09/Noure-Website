export function FormField({
  label,
  error,
  ...props
}: React.InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
}) {
  return (
    <label className="block text-xs font-semibold uppercase tracking-[.12em]">
      <span>{label}</span>
      <input
        {...props}
        className="focus-ring mt-2 w-full border border-line bg-transparent px-4 py-3 text-sm font-normal normal-case tracking-normal outline-none focus:border-plum"
      />
      {error && (
        <span className="mt-1.5 block text-xs font-normal normal-case tracking-normal text-plum">
          {error}
        </span>
      )}
    </label>
  );
}
