import { WHATSAPP_NUMERO } from "@/lib/site";
import { Reveal } from "@/components/reveal";
import { QuoteForm } from "@/components/quote-form";

export function CtaFinal() {
  return (
    <section id="orcamento" className="bg-brand-600 py-16 md:py-20">
      <div className="container-page">
        <div className="grid items-start gap-12 lg:grid-cols-2 lg:gap-16">
          <div>
            <Reveal>
              <p className="eyebrow text-white/90">
                <span className="h-px w-8 bg-white/70" aria-hidden />
                Pronto para modernizar a portaria
              </p>
              <h2 className="titulo-secao mt-4 text-white">
                Receba uma proposta sob medida para o seu condomínio
              </h2>
              <p className="mt-5 leading-relaxed text-white/90">
                Conte como está a portaria hoje. Em poucos minutos você recebe
                uma proposta clara, sem compromisso.
              </p>
            </Reveal>

            <Reveal atraso={60}>
              <ul className="mt-8 space-y-3.5 text-white/95">
                <li className="flex items-start gap-3">
                  <i className="fa-solid fa-circle-check mt-0.5" aria-hidden />
                  <span>Avaliação sem compromisso</span>
                </li>
                <li className="flex items-start gap-3">
                  <i className="fa-solid fa-circle-check mt-0.5" aria-hidden />
                  <span>Proposta clara e objetiva</span>
                </li>
                <li className="flex items-start gap-3">
                  <i className="fa-solid fa-circle-check mt-0.5" aria-hidden />
                  <span>Adapta ao perfil e orçamento do condomínio</span>
                </li>
              </ul>
            </Reveal>

            <Reveal atraso={80}>
              <a
                href={`https://wa.me/${WHATSAPP_NUMERO}`}
                className="mt-8 inline-flex items-center gap-2 rounded-2xl bg-white px-7 py-3.5 text-base font-bold text-brand-900 shadow-sm transition hover:bg-white/95"
              >
                <i className="fa-brands fa-whatsapp text-lg" aria-hidden />
                Falar no WhatsApp
              </a>
            </Reveal>
          </div>

          <Reveal atraso={100}>
            <div className="rounded-2xl bg-white p-6 shadow-xl md:p-8">
              <h3 className="text-xl font-bold text-brand-900">
                Solicite seu orçamento
              </h3>
              <p className="mt-2 text-sm leading-relaxed text-slate-600">
                Preencha os dados e nossa equipe entra em contato para fornecer
                uma cotação específica para seu condomínio.
              </p>

              <div className="mt-7">
                <QuoteForm />
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
