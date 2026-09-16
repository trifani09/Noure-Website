export function LoadingScreen() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-stone-100" aria-live="polite">
      <div className="flex items-center gap-3 text-sm font-medium text-stone-600">
        <span className="h-4 w-4 animate-spin rounded-full border-2 border-stone-300 border-t-stone-800" aria-hidden="true" />
        Checking your session…
      </div>
    </main>
  )
}
