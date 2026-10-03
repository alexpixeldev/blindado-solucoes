import { TECNOLOGIAS, STATUS_SISTEMA } from "@/lib/site";
import { Reveal } from "@/components/reveal";

export function Security() {
  return (
    <section id="seguranca" className="relative overflow-x-clip bg-white py-16 md:py-20">
      <div className="container-page">
        <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
          <div>
            <Reveal>
              <p className="eyebrow text-brand-600">
                <span className="h-px w-8 bg-brand-600" aria-hidden />
                Segurança e tecnologia
              </p>
              <h2 className="titulo-secao mt-4 text-brand-900">
                Monitoramento contínuo, com tecnologia integrada
              </h2>
              <p className="mt-5 leading-relaxed text-slate-600">
                Hardware, software e operadores na mesma central. Tudo integrado
                para entregar mais segurança, com menos complexidade para o
                condomínio.
              </p>
            </Reveal>

            <Reveal atraso={60}>
              <ul className="mt-9 space-y-4">
                {TECNOLOGIAS.map((t) => (
                  <li key={t.titulo} className="flex items-start gap-4">
                    <span className="mt-0.5 grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm">
                      <i className={`fa-solid ${t.icon} text-lg`} aria-hidden />
                    </span>
                    <div>
                      <h3 className="text-base font-semibold text-brand-900">
                        {t.titulo}
                      </h3>
                      <p className="mt-1 text-sm leading-relaxed text-slate-600">
                        {t.texto}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            </Reveal>
          </div>

          <Reveal atraso={80}>
            <div className="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm md:p-8">
              <h3 className="text-sm font-bold tracking-widest text-brand-600 uppercase">
                Status dos sistemas
              </h3>

              <ul className="mt-6 space-y-4">
                {STATUS_SISTEMA.map((s) => (
                  <li
                    key={s.rotulo}
                    className="flex items-center justify-between gap-4 rounded-xl bg-brand-50/70 px-4 py-3.5"
                  >
                    <div>
                      <p className="text-sm font-semibold text-brand-900">
                        {s.rotulo}
                      </p>
                      <p className="text-xs text-slate-600">{s.valor}</p>
                    </div>
                    <span className="flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 shadow-sm">
                      <span className="relative flex h-2 w-2">
                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
                        <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                      </span>
                      Operando
                    </span>
                  </li>
                ))}
              </ul>

              <p className="mt-7 rounded-xl border-l-4 border-brand-600 bg-brand-50 px-4 py-3 text-sm leading-relaxed text-brand-900">
                Toda a operação fica registrada e é auditável, com trilha
                completa de acessos.
              </p>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
