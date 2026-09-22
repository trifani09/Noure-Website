import { AccountGuard } from "@/components/account/AccountGuard";

export default function AccountLayout({ children }: { children: React.ReactNode }) {
  return <AccountGuard>{children}</AccountGuard>;
}