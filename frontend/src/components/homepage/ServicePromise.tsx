const promises = [
  ["Considered design", "Thoughtful silhouettes made to live beyond a single season."],
  ["Secure checkout", "A clear, protected checkout with order updates at every step."],
  ["Client care", "Personal support before, during, and after your purchase."],
];

export function ServicePromise() {
  return (
    <section className="border-y border-line bg-paper">
      <div className="page-shell grid md:grid-cols-3">
        {promises.map(([title, copy], index) => (
          <div
            key={title}
            className={`px-4 py-9 text-center md:px-10 ${index ? "border-t border-line md:border-l md:border-t-0" : ""}`}
          >
            <p className="eyebrow text-plum">0{index + 1}</p>
            <h2 className="editorial-title mt-2 text-2xl">{title}</h2>
            <p className="mx-auto mt-2 max-w-xs text-xs leading-6 text-muted">{copy}</p>
          </div>
        ))}
      </div>
    </section>
  );
}
