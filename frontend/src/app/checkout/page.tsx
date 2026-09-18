import type { Metadata } from "next";
import { CheckoutForm } from "@/features/checkout/CheckoutForm";
export const metadata: Metadata = {
  title: "Checkout",
  description: "Complete your Noure order.",
};
export default function CheckoutPage() {
  return <CheckoutForm />;
}
