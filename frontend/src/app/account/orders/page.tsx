import type {Metadata} from "next";import {ContentPlaceholder} from "@/features/content";
export const metadata:Metadata={title:"Order history",description:"Noure customer order-history route foundation."};
export default function OrdersPage(){return <ContentPlaceholder eyebrow="Account foundation" title="Your orders" description="Order history will become available when customer authentication and order services are connected."/>}
