import type { Metadata } from "next";
import { OrderSuccess } from "@/features/checkout/OrderSuccess";
export const metadata: Metadata = { title: "Order confirmed", description: "Your Noure order confirmation." };
export default function CheckoutSuccessPage() { return <OrderSuccess />; }
