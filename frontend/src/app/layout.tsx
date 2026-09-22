import type { Metadata } from "next";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
import { AuthProvider } from "@/components/auth/AuthProvider";
import { CartDrawer, CartProvider } from "@/features/cart";
import { getCategories } from "@/features/categories";
import type { Category } from "@/types/catalog";
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
  let categories: Category[] = [];
  try {
    categories = (await getCategories({ per_page: 12, sort: "position" })).data;
  } catch {
    // Navigation remains usable while the catalog API is unavailable.
  }

  return (
    <html lang="en" className="antialiased">
      <body>
        <AuthProvider>
          <CartProvider>
            <Header categories={categories} />
            <CartDrawer />
            <main className="min-h-[70vh]">{children}</main>
            <Footer />
          </CartProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
