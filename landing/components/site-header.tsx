"use client";

import { useEffect, useState } from "react";
import { NAV_LINKS } from "@/lib/site";

export function SiteHeader() {
  const [aberto, setAberto] = useState(false);
  const [rolou, setRolou] = useState(false);

  useEffect(() => {
    const aoRolar = () => setRolou(window.scrollY > 24);
    aoRolar();
    window.addEventListener("scroll", aoRolar, { passive: true });
    return () => window.removeEventListener("scroll", aoRolar);
  }, []);

  useEffect(() => {
    document.body.style.overflow = aberto ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [aberto]);

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
        rolou
          ? "bg-white/95 shadow-[0_1px_30px_rgba(19,74,63,0.08)] backdrop-blur-md"
          : "bg-white"
      }`}
    >
      <div className="container-page">
        <div className="flex h-16 items-center justify-between gap-4 md:h-20">
          <a href="#topo" className="shrink-0" aria-label="Blindado Soluções">
            <img
              src="/img/logo_horizontal.png"
              alt="Blindado Soluções"
              width={930}
              height={243}
              className="h-9 w-auto md:h-11"
            />
          </a>

          <nav className="hidden items-center gap-1 lg:flex" aria-label="Principal">
            {NAV_LINKS.map((link) => (
              <a
                key={link.href}
                href={link.href}
                className="rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-900"
              >
                {link.label}
              </a>
            ))}
            <a
              href="#orcamento"
              className="ml-2 inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-700"
            >
              Solicitar Orçamento
              <i className="fa-solid fa-arrow-right text-xs" aria-hidden />
            </a>
          </nav>

          <button
            type="button"
            onClick={() => setAberto((v) => !v)}
            aria-expanded={aberto}
            aria-controls="menu-mobile"
            aria-label={aberto ? "Fechar menu" : "Abrir menu"}
            className="grid h-11 w-11 place-items-center rounded-xl border border-brand-100 text-brand-800 transition-colors hover:bg-brand-100 lg:hidden"
          >
            <i
              className={`fa-solid ${aberto ? "fa-xmark" : "fa-bars"} text-lg`}
              aria-hidden
            />
          </button>
        </div>
      </div>

      {/* menu mobile */}
      <div
        id="menu-mobile"
        // quando fechado, os links saem da navegacao por teclado e leitor de tela
        inert={!aberto}
        aria-hidden={!aberto}
        className={`overflow-hidden border-t border-brand-100 bg-white transition-[max-height,opacity] duration-300 lg:hidden ${
          aberto ? "max-h-[40rem] opacity-100" : "pointer-events-none max-h-0 opacity-0"
        }`}
      >
        <nav className="container-page flex flex-col py-3" aria-label="Menu mobile">
          {NAV_LINKS.map((link) => (
            <a
              key={link.href}
              href={link.href}
              onClick={() => setAberto(false)}
              className="rounded-lg px-2 py-3 text-base font-semibold text-brand-800 transition-colors hover:bg-brand-100"
            >
              {link.label}
            </a>
          ))}
          <a
            href="#orcamento"
            onClick={() => setAberto(false)}
            className="mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-5 py-3.5 text-base font-bold text-white"
          >
            Solicitar Orçamento
            <i className="fa-solid fa-arrow-right text-sm" aria-hidden />
          </a>
        </nav>
      </div>
    </header>
  );
}
