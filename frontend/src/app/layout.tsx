import type { Metadata } from "next";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"),
  title: { default: "Noure — Considered womenswear", template: "%s | Noure" },
  description: "Modern, feminine pieces designed with a quiet point of view.",
  openGraph: { siteName: "Noure", type: "website", locale: "en_US" }, twitter: { card: "summary_large_image" },
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en" className="antialiased"><body><Header /><main className="min-h-[70vh]">{children}</main><Footer /></body></html>
  );
}
