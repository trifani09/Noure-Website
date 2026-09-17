import type {Metadata} from "next";import {ContentPlaceholder} from "@/features/content";
export const metadata:Metadata={title:"Customer profile",description:"Noure customer-profile route foundation."};
export default function ProfilePage(){return <ContentPlaceholder eyebrow="Account foundation" title="Your profile" description="Profile management will become available when customer authentication is connected."/>}
