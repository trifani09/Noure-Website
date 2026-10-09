import type { Metadata } from "next";
import { CartPageClient } from "@/features/cart/CartPageClient";
export const metadata: Metadata = {
  title: "Keranjang Belanja",
  description: "Keranjang belanja Noure Anda.",
};
export default function CartPage() {
  return <CartPageClient />;
}
