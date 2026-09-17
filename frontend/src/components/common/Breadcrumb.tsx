import Link from "next/link";
export type BreadcrumbItem={label:string;href?:string};
export function Breadcrumb({items}:{items:BreadcrumbItem[]}){return <nav aria-label="Breadcrumb" className="flex flex-wrap items-center gap-2 text-[10px] uppercase tracking-[.16em] text-muted"><Link href="/">Home</Link>{items.map(item=><span className="flex items-center gap-2" key={`${item.label}-${item.href??"current"}`}><span aria-hidden>／</span>{item.href?<Link href={item.href}>{item.label}</Link>:<span aria-current="page" className="text-ink">{item.label}</span>}</span>)}</nav>}
