import { BENEFICIOS, DIFERENCIAIS } from "@/lib/site";
import { Reveal } from "@/components/reveal";

export function Benefits() {
  return (
    <section id="beneficios" className="bg-brand-50 py-16 md:py-20">
      <div className="container-page">
        <div className="grid items-start gap-12 lg:grid-cols-12 lg:gap-16">
          <Reveal className="lg:col-span-5">
            <p className="eyebrow text-brand-600">
              <span className="h-px w-8 bg-brand-600" aria-hidden />
              Benefícios
            </p>
            <h2 className="titulo-secao mt-4 text-brand-900">
              O que muda no seu condomínio
            </h2>
            <p className="mt-5 leading-relaxed text-slate-600">
              Resultado concreto para o síndico e para os moradores, sem promessa
              genérica.
            </p>

            <ul className="mt-9 space-y-5">
              {DIFERENCIAIS.map((d) => (
                <li key={d.titulo} className="flex items-start gap-3.5">
                  <span className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-brand-600 shadow-sm">
                    <i className={`fa-solid ${d.icon} text-sm`} aria-hidden />
                  </span>
                  <div>
                    <h3 className="text-sm font-semibold text-brand-900">
                      {d.titulo}
                    </h3>
                    <p className="mt-0.5 text-sm leading-relaxed text-slate-600">
                      {d.texto}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          </Reveal>

          <div className="grid gap-4 sm:grid-cols-2 lg:col-span-7">
            {BENEFICIOS.map((b, i) => (
              <Reveal key={b.titulo} atraso={i * 60} as="article">
                <div className="h-full rounded-2xl border border-brand-100 bg-white p-6">
                  <div className="flex items-center gap-3">
                    <i className={`fa-solid ${b.icon} text-brand-600`} aria-hidden />
                    <h3 className="text-base font-semibold text-brand-900">
                      {b.titulo}
                    </h3>
                  </div>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    {b.texto}
                  </p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
