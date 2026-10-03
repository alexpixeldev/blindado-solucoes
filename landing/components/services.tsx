import { SERVICOS } from "@/lib/site";
import { Reveal } from "@/components/reveal";

export function Services() {
  return (
    <section id="servicos" className="bg-white py-16 md:py-20">
      <div className="container-page">
        <Reveal>
          <div className="max-w-2xl">
            <p className="eyebrow text-brand-600">
              <span className="h-px w-8 bg-brand-600" aria-hidden />
              Soluções
            </p>
            <h2 className="titulo-secao mt-4 text-brand-900">
              Tudo integrado no mesmo sistema
            </h2>
            <p className="mt-5 leading-relaxed text-slate-600">
              Portaria, monitoramento, controle de acesso e apoio operacional
              funcionando juntos — sem sistemas separados.
            </p>
          </div>
        </Reveal>

        <div className="mt-12 grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
          {SERVICOS.map((s, i) => (
            <Reveal key={s.titulo} atraso={i * 50}>
              <div className="border-t-2 border-brand-100 pt-5 transition-colors hover:border-brand-600">
                <i className={`fa-solid ${s.icon} text-xl text-brand-600`} aria-hidden />
                <h3 className="mt-4 text-base font-semibold text-brand-900">
                  {s.titulo}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                  {s.texto}
                </p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
