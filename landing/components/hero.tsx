import { WHATSAPP_NUMERO } from "@/lib/site";
import { Reveal } from "@/components/reveal";
import { IntercomPanel } from "@/components/intercom-panel";

export function Hero() {
  return (
    <section id="topo" className="relative overflow-x-clip bg-white pb-14 pt-24 md:pb-20 md:pt-28">
      <div
        aria-hidden
        className="pointer-events-none absolute -right-40 top-0 hidden h-[34rem] w-[34rem] rounded-full bg-brand-50 blur-[120px] lg:block"
      />
      <div
        aria-hidden
        className="pointer-events-none absolute -left-40 top-40 hidden h-[28rem] w-[28rem] rounded-full bg-brand-50/60 blur-[120px] lg:block"
      />

      <div className="container-page relative">
        {/* min-w-0 nas duas colunas: impede que qualquer conteudo interno
            (mockup, botao, palavra longa) estique a linha do grid alem da tela */}
        <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
          <div className="min-w-0">
            <Reveal>
              <p className="eyebrow text-brand-600">
                <span className="h-px w-8 bg-brand-600" aria-hidden />
                Portaria remota para condomínios
              </p>
            </Reveal>

            <Reveal atraso={40}>
              <h1 className="mt-5 text-[2.25rem] leading-[1.08] font-bold tracking-tight text-brand-900 sm:text-5xl lg:text-[3.5rem]">
                A portaria do seu condomínio,
                <br />
                <span className="text-brand-600">sempre online</span>
              </h1>
            </Reveal>

            <Reveal atraso={80}>
              <p className="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                Operadores remotos numa central 24 horas, controle de acesso
                pelo celular e monitoramento integrado. Mais segurança,
                menos despesa para o condomínio.
              </p>
            </Reveal>

            <Reveal atraso={120}>
              <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                <a
                  href="#orcamento"
                  className="inline-flex items-center justify-center gap-2.5 rounded-2xl bg-brand-600 px-8 py-4 text-base font-bold text-white shadow-sm transition hover:bg-brand-700"
                >
                  Solicitar orçamento
                  <i className="fa-solid fa-arrow-right text-sm" aria-hidden />
                </a>
                <a
                  href={`https://wa.me/${WHATSAPP_NUMERO}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center justify-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-8 py-4 text-base font-semibold text-brand-900 transition hover:border-brand-300 hover:bg-brand-50/60"
                >
                  <i className="fa-brands fa-whatsapp text-lg" aria-hidden />
                  Falar no WhatsApp
                </a>
              </div>
            </Reveal>

            <Reveal atraso={160}>
              <div className="mt-10 flex flex-wrap items-center gap-x-6 gap-y-4 text-sm text-slate-600">
                <div className="flex min-w-0 items-center gap-2">
                  <i className="fa-solid fa-shield-halved shrink-0 text-brand-600" aria-hidden />
                  <span>Atendimento 24 horas</span>
                </div>
                <div className="flex min-w-0 items-center gap-2">
                  <i className="fa-solid fa-circle-check shrink-0 text-brand-600" aria-hidden />
                  <span>Histórico auditável</span>
                </div>
                <div className="flex min-w-0 items-center gap-2">
                  <i className="fa-solid fa-mobile-screen shrink-0 text-brand-600" aria-hidden />
                  <span>Liberação pelo app</span>
                </div>
              </div>
            </Reveal>
          </div>

          <Reveal atraso={80} className="min-w-0">
            <IntercomPanel />
          </Reveal>
        </div>
      </div>
    </section>
  );
}
