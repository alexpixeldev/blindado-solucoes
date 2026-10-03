import { COMO_FUNCIONA } from "@/lib/site";
import { Reveal } from "@/components/reveal";

export function HowItWorks() {
  return (
    <section id="como-funciona" className="bg-white py-16 md:py-20">
      <div className="container-page">
        <Reveal>
          <div className="max-w-2xl">
            <p className="eyebrow text-brand-600">
              <span className="h-px w-8 bg-brand-600" aria-hidden />
              Como funciona
            </p>
            <h2 className="titulo-secao mt-4 text-brand-900">
              O processo é simples para todos
            </h2>
            <p className="mt-5 leading-relaxed text-slate-600">
              Explicado em 4 passos, sem complicação. O visitante é atendido com
              cortesia e o síndico autoriza em segundos.
            </p>
          </div>
        </Reveal>

        <div className="relative mt-12">
          <div
            aria-hidden
            className="absolute top-7 right-12 left-12 hidden h-px bg-gradient-to-r from-brand-300/30 via-brand-600/40 to-brand-300/30 lg:block"
          />

          <ol className="relative grid gap-10 lg:grid-cols-4 lg:gap-8">
            {COMO_FUNCIONA.map((p, i) => (
              <Reveal key={p.numero} atraso={i * 40} as="li" className="relative">
                <div className="flex gap-5 lg:flex-col">
                  <span className="relative z-10 grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-brand-600 text-lg font-bold text-white shadow-sm">
                    {p.numero}
                  </span>

                  <div className="lg:mt-5">
                    <h3 className="text-base font-semibold text-brand-900">
                      {p.titulo}
                    </h3>
                    <p className="mt-2 text-sm leading-relaxed text-slate-600">
                      {p.texto}
                    </p>
                  </div>
                </div>
              </Reveal>
            ))}
          </ol>
        </div>
      </div>
    </section>
  );
}
