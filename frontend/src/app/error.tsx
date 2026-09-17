"use client";
export default function ErrorPage({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="page-shell py-32 text-center" role="alert">
      <p className="eyebrow text-plum">A quiet pause</p>
      <h1 className="editorial-title mt-4 text-5xl">
        We couldn’t load this page
      </h1>
      <p className="mt-4 text-sm text-muted">
        Please check your connection and try again in a moment.
      </p>
      <button onClick={reset} className="button-primary focus-ring mt-8">
        Try again
      </button>
    </div>
  );
}
