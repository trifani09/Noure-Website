export default function Loading() {
  return (
    <div className="page-shell animate-pulse py-16">
      <div className="h-8 w-40 bg-sand" />
      <div className="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
        {Array.from({ length: 8 }).map((_, index) => (
          <div key={index}>
            <div className="aspect-[4/5] bg-ivory" />
            <div className="mt-4 h-3 w-2/3 bg-sand" />
            <div className="mt-2 h-3 w-1/3 bg-sand" />
          </div>
        ))}
      </div>
    </div>
  );
}
