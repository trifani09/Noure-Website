import type { Metadata } from "next";
import { AccountDashboard } from "@/components/account/AccountDashboard";
export const metadata: Metadata = {
  title: "Account",
  description: "Manage your Noure profile, addresses, and orders.",
};
export default function AccountPage() {
  return <AccountDashboard />;
}
