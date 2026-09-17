import type { Metadata } from "next";
import { CartPageClient } from "@/features/cart/CartPageClient";
export const metadata: Metadata = { title: "Shopping bag", description: "Your Noure shopping bag." };
export default function CartPage() { return <CartPageClient />; }