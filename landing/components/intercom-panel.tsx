"use client";

import { useEffect, useRef, useState } from "react";

/**
 * O video real do atendimento ocupa a area da camera: e o que demonstra
 * o atendimento acontecendo. O arquivo e 1024x768, exatamente 4:3 como a
 * caixa, entao object-cover nao corta nada e nao distorce.
 *
 * O poster entra antes dos bytes chegarem: o arquivo tem 4,1 MB e sem isso a
 * area ficaria preta em conexao lenta.
 *
 * currentTime e repassado ao pai para a contagem de "tempo de chamada"
 * andar junto com o video, e nao com um relogio paralelo.
 */
function CameraFeed({ onTempo }: { onTempo: (s: number) => void }) {
  const ref = useRef<HTMLVideoElement>(null);

  useEffect(() => {
    const v = ref.current;
    if (!v) return;

    // respeita quem pediu menos movimento
    const semMovimento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (semMovimento) return;

    v.play().catch(() => {
      /* o navegador pode bloquear o autoplay; o poster/primeiro frame cobre */
    });
  }, []);

  useEffect(() => {
    const v = ref.current;
    if (!v) return;

    const avisar = () => onTempo(v.currentTime);
    // timeupdate dispara ~4x por segundo: o suficiente para mostrar segundos
    v.addEventListener("timeupdate", avisar);
    return () => v.removeEventListener("timeupdate", avisar);
  }, [onTempo]);

  return (
    <video
      ref={ref}
      className="absolute inset-0 h-full w-full object-cover object-center"
      src="/img/mulher-interfone.mp4"
      poster="/img/mulher-interfone-poster.jpg"
      muted
      loop
      playsInline
      preload="metadata"
      aria-label="Atendimento na portaria por interfone"
    />
  );
}

/** 83 -> "01:23" */
function formatarDuracao(segundos: number) {
  const s = Math.max(0, Math.floor(segundos));
  const mm = Math.floor(s / 60);
  const ss = s % 60;
  return `${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
}

export function IntercomPanel() {
  const [liberado, setLiberado] = useState(false);
  const [tempo, setTempo] = useState(0);

  return (
    <div
      className="relative mx-auto max-w-[560px]"
      role="group"
      aria-label="Demonstração de atendimento na portaria"
    >
      <div
        aria-hidden
        className="absolute -inset-6 -z-10 rounded-[3rem] bg-brand-50/70 blur-2xl"
      />

      <div className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-lg">
        {/* ---------- barra superior ---------- */}
        <div className="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 sm:gap-3 sm:px-5 sm:py-3.5">
          <span className="flex min-w-0 shrink-0 items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[10px] font-bold tracking-widest text-brand-700 uppercase">
            <span className="h-1.5 w-1.5 rounded-full bg-brand-600" />
            {liberado ? "Liberado" : "Em atendimento"}
          </span>
          <span className="hidden min-w-0 flex-1 truncate text-xs font-semibold text-slate-700 sm:block">
            Porta Social
          </span>
          <i className="fa-solid fa-video shrink-0 text-sm text-slate-400" aria-hidden />
        </div>

        {/* ---------- video do interfone ---------- */}
        <div className="relative aspect-4/3 overflow-hidden bg-slate-900">
          <CameraFeed onTempo={setTempo} />

          {/* escurece o topo e a base para o texto em cima ficar legivel */}
          <div
            aria-hidden
            className="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/55 via-transparent to-slate-950/25"
          />

          {/* selo de visitante detectado */}
          <div className="pointer-events-none absolute inset-0 grid place-items-center">
            <div className="rounded-full bg-slate-950/45 px-4 py-2 backdrop-blur-[2px]">
              <span className="flex items-center gap-2 text-sm font-semibold text-white">
                <span className="h-2 w-2 shrink-0 animate-pulse rounded-full bg-emerald-400" />
                Visitante detectado
              </span>
            </div>
          </div>

          {/* marca de tempo do lado inferior */}
          <span className="pointer-events-none absolute bottom-3 right-3 rounded-lg bg-slate-950/60 px-2 py-1 font-mono text-[10px] text-white backdrop-blur-[2px]">
            14:32:07
          </span>

          {/* confirmacao de liberacao */}
          {liberado && (
            <div className="pointer-events-none absolute inset-0 grid place-items-center bg-slate-950/45 backdrop-blur-[1px]">
              <div className="text-center">
                <span className="mx-auto grid h-16 w-16 place-items-center rounded-full bg-brand-600 text-2xl text-white shadow-lg">
                  <i className="fa-solid fa-check" aria-hidden />
                </span>
                <p className="mt-3 text-sm font-bold text-white">
                  Acesso liberado
                </p>
                <p className="mt-1 text-xs text-white/80">
                  Portão aberto · histórico registrado
                </p>
              </div>
            </div>
          )}
        </div>

        {/* ---------- acoes ----------
             No celular o texto sobe e os botoes ocupam a largura toda.
             Sem isso o min-content da linha (~469px) forçava o grid inteiro
             a 471px e empurrava o texto do hero para fora da tela. */}
        <div className="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-5">
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-brand-900">
              Carlos M.
            </p>
            <p className="mt-0.5 truncate text-xs text-slate-500">
              {liberado
                ? "Entrada registrada no histórico"
                : "Operador falando com o visitante"}
            </p>
          </div>

          <div className="grid w-full grid-cols-2 gap-2.5 sm:w-auto sm:shrink-0">
            <button
              type="button"
              onClick={() => setLiberado(false)}
              className="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-600 transition-colors hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700"
            >
              <i className="fa-solid fa-phone-slash text-xs" aria-hidden />
              Recusar
            </button>
            <button
              type="button"
              onClick={() => setLiberado(true)}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-3 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-brand-700"
            >
              <i className="fa-solid fa-lock-open text-xs" aria-hidden />
              Liberar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
