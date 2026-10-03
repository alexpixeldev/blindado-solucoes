"use client";

import { useState } from "react";
import { FAQ } from "@/lib/site";
import { Reveal } from "@/components/reveal";

export function Faq() {
  const [aberto, setAberto] = useState<number | null>(0);

  return (
    <section id="faq" className="bg-brand-50 py-16 md:py-20">
      <div className="container-page">
        <Reveal>
          <div className="max-w-2xl">
            <p className="eyebrow text-brand-600">
              <span className="h-px w-8 bg-brand-600" aria-hidden />
              Dúvidas frequentes
            </p>
            <h2 className="titulo-secao mt-4 text-brand-900">
              Tudo o que o síndico precisa saber
            </h2>
            <p className="mt-5 leading-relaxed text-slate-600">
              As respostas mais comuns sobre portaria remota e nosso modelo de
              implantação.
            </p>
          </div>
        </Reveal>

        <Reveal atraso={60}>
          <div className="mt-10 overflow-hidden rounded-2xl border border-brand-100 bg-white">
            {FAQ.map((f, i) => (
              <div key={f.pergunta} className="border-b border-slate-100 last:border-0">
                <button
                  type="button"
                  onClick={() => setAberto((v) => (v === i ? null : i))}
                  aria-expanded={aberto === i}
                  className="flex w-full items-center justify-between gap-6 px-6 py-5 text-left transition-colors hover:bg-brand-50/40"
                >
                  <h3 className="text-base font-semibold leading-snug text-brand-900">
                    {f.pergunta}
                  </h3>
                  <i
                    className={`fa-solid ${
                      aberto === i ? "fa-chevron-up" : "fa-chevron-down"
                    } shrink-0 text-brand-600 transition-transform`}
                    aria-hidden
                  />
                </button>

                <div
                  className={`overflow-hidden px-6 transition-[max-height] duration-300 ${
                    aberto === i ? "max-h-96 pb-5" : "max-h-0"
                  }`}
                >
                  <p className="leading-relaxed text-slate-600">{f.resposta}</p>
                </div>
              </div>
            ))}
          </div>
        </Reveal>
      </div>
    </section>
  );
}
