import type { Metadata } from "next";
import { ContentPlaceholder } from "@/features/content";
export const metadata: Metadata = {
  title: "Frequently asked questions",
  description: "Answers to common questions about shopping with Noure.",
};
export default function FaqPage() {
  return (
    <ContentPlaceholder
      eyebrow="Client care"
      title="Frequently asked questions"
      description="Approved guidance about products, delivery, and returns will be published here when available."
    />
  );
}
