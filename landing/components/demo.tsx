import { Reveal } from "@/components/reveal";

const ITENS = [
  "Identificação do visitante em tempo real",
  "Autoriza quem entra com um toque no celular",
  "Histórico completo e auditable de acessos",
  "Portaria remota ou híbrida, conforme o seu orçamento",
];

export function Demo() {
  return (
    <section id="aplicativo" className="relative overflow-x-clip bg-brand-50 py-16 md:py-20">
      <div
        aria-hidden
        className="pointer-events-none absolute -bottom-40 -left-40 hidden h-[28rem] w-[28rem] rounded-full bg-brand-100/50 blur-[120px] lg:block"
      />

      <div className="container-page relative">
        <div className="grid items-center gap-16 lg:grid-cols-2 lg:gap-20">
          <Reveal>
            <p className="eyebrow text-brand-600">
              <span className="h-px w-8 bg-brand-600" aria-hidden />
              Experiência prática
            </p>
            <h2 className="titulo-secao mt-4 text-brand-900">
              Veja como a experiência é simples para todos
            </h2>
            <p className="mt-5 leading-relaxed text-slate-600">
              Moradores autorizam em segundos, visitantes são atendidos com
              cortesia e o síndico tem o controle completo, sem complicação.
            </p>

            <ul className="mt-8 space-y-3.5">
              {ITENS.map((i) => (
                <li key={i} className="flex items-start gap-3">
                  <i
                    className="fa-solid fa-circle-check mt-0.5 text-brand-600"
                    aria-hidden
                  />
                  <span className="leading-snug text-slate-700">{i}</span>
                </li>
              ))}
            </ul>

            <a
              href="#orcamento"
              className="mt-8 inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-7 py-3.5 text-base font-bold text-white shadow-sm transition hover:bg-brand-700"
            >
              Solicitar orçamento
              <i className="fa-solid fa-arrow-right text-sm" aria-hidden />
            </a>
          </Reveal>
          <Reveal atraso={80}>
            <div className="relative mx-auto w-full max-w-[340px]">
              <div
                aria-hidden
                className="absolute -inset-6 -z-10 rounded-[3rem] bg-brand-100/40 blur-2xl"
              />
              <div className="rounded-[2.75rem] border border-slate-200 bg-white p-2 shadow-lg">
                <div className="overflow-hidden rounded-[2.25rem] border border-slate-100 bg-slate-50">
                  <div className="flex items-center justify-between px-5 pt-5">
                    <span className="text-[10px] font-semibold text-slate-500">
                      14:32
                    </span>
                    <div className="flex items-center gap-1.5 text-slate-400">
                      <i className="fa-solid fa-signal text-[9px]" aria-hidden />
                      <i className="fa-solid fa-wifi text-[9px]" aria-hidden />
                      <i className="fa-solid fa-battery-three-quarters text-[9px]" aria-hidden />
                    </div>
                  </div>

                  <div className="px-5 pt-5">
                    <div className="flex items-center justify-between">
                      <p className="text-xs font-semibold text-slate-500">
                        Portão principal
                      </p>
                      <span className="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        Ao vivo
                      </span>
                    </div>

                    <div className="relative mt-3 aspect-4/3 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-200 via-slate-100 to-white">
                      <div className="absolute inset-0 grid place-items-center">
                        <i className="fa-solid fa-user text-3xl text-slate-400" aria-hidden />
                      </div>
                    </div>

                    <div className="mt-4 rounded-2xl bg-white p-4 shadow-sm">
                      <p className="text-xs text-slate-500">Visitante</p>
                      <p className="text-sm font-semibold text-brand-900">
                        Carlos M. · Bloco B
                      </p>
                      <div className="mt-3 grid grid-cols-2 gap-2">
                        <span className="rounded-xl border border-slate-200 py-2.5 text-center text-[11px] font-semibold text-slate-600">
                          Recusar
                        </span>
                        <span className="rounded-xl bg-brand-600 py-2.5 text-center text-[11px] font-bold text-white">
                          Liberar acesso
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 border-t border-slate-200 bg-white px-5 py-3.5">
                    <div className="flex items-center justify-between">
                      <p className="text-xs font-semibold text-brand-900">
                        Histórico
                      </p>
                      <span className="text-[10px] text-slate-500">
                        Últimas entradas
                      </span>
                    </div>
                    <div className="mt-3 space-y-2">
                      <div className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span className="text-[11px] text-slate-600">
                          Entregador
                        </span>
                        <span className="text-[10px] text-slate-400">14:20</span>
                      </div>
                      <div className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span className="text-[11px] text-slate-600">
                          Visitante · Apto 302
                        </span>
                        <span className="text-[10px] text-slate-400">13:47</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="absolute -bottom-4 -right-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg sm:-right-6">
                <div className="flex items-center gap-2.5">
                  <span className="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i className="fa-solid fa-shield-halved text-sm" aria-hidden />
                  </span>
                  <div>
                    <p className="text-xs font-semibold text-brand-900">
                      Tudo registrado
                    </p>
                    <p className="text-[10px] text-slate-500">
                      Histórico auditável
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
