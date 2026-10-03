import { SiteHeader } from "@/components/site-header";
import { Hero } from "@/components/hero";
import { HowItWorks } from "@/components/how-it-works";
import { Services } from "@/components/services";
import { Demo } from "@/components/demo";
import { Security } from "@/components/security";
import { Benefits } from "@/components/benefits";
import { Faq } from "@/components/faq";
import { CtaFinal } from "@/components/cta-final";
import { SiteFooter } from "@/components/site-footer";

export default function Page() {
  return (
    <>
      <SiteHeader />
      <main>
        <Hero />
        <HowItWorks />
        <Services />
        <Demo />
        <Security />
        <Benefits />
        <Faq />
        <CtaFinal />
      </main>
      <SiteFooter />
    </>
  );
}
