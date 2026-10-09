import type { Metadata } from "next";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
import { AuthProvider } from "@/components/auth/AuthProvider";
import { CartDrawer, CartProvider } from "@/features/cart";
import { getStorefrontSettings } from "@/services/api";
import type { StorefrontSettings } from "@/types/catalog";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL(
    process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000",
  ),
  title: { default: "Noure — Considered womenswear", template: "%s | Noure" },
  description: "Modern, feminine pieces designed with a quiet point of view.",
  openGraph: { siteName: "Noure", type: "website", locale: "en_US" },
  twitter: { card: "summary_large_image" },
};

export default async function RootLayout({ children }: LayoutProps<"/">) {
  let settings: StorefrontSettings = {
    store_name: "Noure",
    announcement_text: "Dapatkan harga eksklusif hanya di website",
    announcement_url: "/products",
    announcement_is_active: true,
  };
  try {
    settings = await getStorefrontSettings();
  } catch {
    // Navigation remains usable while the catalog API is unavailable.
  }

  return (
    <html lang="id" className="antialiased">
      <body suppressHydrationWarning>
        <AuthProvider>
          <CartProvider>
            <Header settings={settings} />
            <CartDrawer />
            <main className="min-h-[70vh]">{children}</main>
            <Footer />
          </CartProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
