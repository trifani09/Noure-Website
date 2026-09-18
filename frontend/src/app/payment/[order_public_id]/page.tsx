import type { Metadata } from "next";
import { PaymentPage } from "@/features/checkout/PaymentPage";
export const metadata: Metadata = { title: "Payment", description: "Complete and review your Noure payment." };
export default async function PaymentRoute({ params }: PageProps<"/payment/[order_public_id]">) { const { order_public_id } = await params; return <PaymentPage orderId={order_public_id} />; }
