import { Breadcrumb } from "@/components/common/Breadcrumb";
import { Container } from "@/components/layout/Container";
import { Section } from "@/components/layout/Section";
export function ContentPlaceholder({
  eyebrow,
  title,
  description,
}: {
  eyebrow: string;
  title: string;
  description: string;
}) {
  return (
    <>
      <Container className="pt-8">
        <Breadcrumb items={[{ label: title }]} />
      </Container>
      <Section>
        <div className="mx-auto max-w-3xl text-center">
          <p className="eyebrow text-plum">{eyebrow}</p>
          <h1 className="editorial-title mt-4 text-5xl md:text-7xl">{title}</h1>
          <p className="mx-auto mt-6 max-w-xl text-sm leading-8 text-muted">
            {description}
          </p>
          <div className="mx-auto mt-12 h-px max-w-xs bg-line" />
          <p className="mt-8 text-xs uppercase tracking-[.16em] text-muted">
            Content will be published here when available.
          </p>
        </div>
      </Section>
    </>
  );
}
