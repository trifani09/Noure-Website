import type { Metadata } from "next";
import { ContentPlaceholder } from "@/features/content";
export const metadata: Metadata = {
  title: "Terms and conditions",
  description: "Noure storefront terms and conditions.",
};
export default function TermsPage() {
  return (
    <ContentPlaceholder
      eyebrow="Policies"
      title="Terms and conditions"
      description="The approved Noure terms have not yet been supplied. This route is ready for the final legal content."
    />
  );
}
