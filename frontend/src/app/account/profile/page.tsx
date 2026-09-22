import type { Metadata } from "next";
import { AccountNav } from "@/components/account/AccountGuard";
import { ProfileForm } from "@/components/account/ProfileForm";
export const metadata: Metadata = {
  title: "Customer profile",
  description: "Update your Noure customer profile.",
};
export default function ProfilePage() {
  return <div className="page-shell section-space"><AccountNav /><div className="mt-14"><p className="eyebrow text-plum">Personal details</p><h1 className="editorial-title mt-3 text-5xl">Your profile</h1><ProfileForm /></div></div>;
}
