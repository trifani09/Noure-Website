import {Container} from "@/components/layout/Container";
export function Section({children,className="",contained=true}:{children:React.ReactNode;className?:string;contained?:boolean}){const content=contained?<Container>{children}</Container>:children;return <section className={`py-16 md:py-24 ${className}`}>{content}</section>}
