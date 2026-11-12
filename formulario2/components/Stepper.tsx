'use client';

import { motion } from 'framer-motion';

const steps = [
  { title: 'Perfil', short: 'Perfil', icon: 'fa-user-tag' },
  { title: 'Seleção do edifício', short: 'Edifício', icon: 'fa-building' },
  { title: 'Hóspedes', short: 'Hóspedes', icon: 'fa-users' },
  { title: 'Veículos', short: 'Veículos', icon: 'fa-car-side' },
  { title: 'Período da estadia', short: 'Período', icon: 'fa-calendar-check' },
  { title: 'Revisão', short: 'Revisão', icon: 'fa-clipboard-check' }
];

export function Stepper({ current, onStepClick }: { current: number; onStepClick?: (step: number) => void }) {
  return (
    <div className="w-full">
      <nav aria-label="Progresso do formulário">
        <ol className="flex items-start">
          {steps.map((s, idx) => {
            const done = idx < current;
            const active = idx === current;
            const reached = idx <= current;
            const clickable = done && Boolean(onStepClick);
            return (
              <li key={s.title} className="relative flex flex-1 justify-center">
                {idx > 0 && (
                  <span
                    aria-hidden="true"
                    className={`absolute left-[-50%] right-1/2 top-[13px] h-0.5 rounded-full transition-colors duration-500 ${
                      reached ? 'bg-gradient-to-r from-primary-400 to-accent-300' : 'bg-white/15'
                    }`}
                  />
                )}
                <button
                  type="button"
                  onClick={clickable ? () => onStepClick?.(idx) : undefined}
                  disabled={!clickable}
                  aria-current={active ? 'step' : undefined}
                  aria-label={`Etapa ${idx + 1} de ${steps.length}: ${s.title}${
                    done ? ' (concluída)' : active ? ' (atual)' : ''
                  }`}
                  className={`group relative z-10 flex w-full min-w-0 flex-col items-center gap-1.5 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#03211a] ${
                    clickable ? 'cursor-pointer' : 'cursor-default'
                  }`}
                >
                  <span
                    className={`relative flex h-7 w-7 items-center justify-center rounded-full border text-[11px] font-bold transition-all duration-300 ${
                      active
                        ? 'scale-110 border-white bg-white text-[#03211a] shadow-lg shadow-accent-400/30'
                        : done
                          ? 'border-primary-300/80 bg-primary-400 text-[#03211a] group-hover:bg-primary-300'
                          : 'border-white/20 bg-white/[0.06] text-white/45'
                    }`}
                  >
                    {active && (
                      <motion.span
                        layoutId="stepper-active-glow"
                        className="absolute -inset-1 rounded-full bg-accent-400/40 blur-sm"
                        transition={{ type: 'spring', stiffness: 420, damping: 32 }}
                      />
                    )}
                    {done ? (
                      <i className="fas fa-check relative text-[10px]" aria-hidden="true" />
                    ) : (
                      <span className="relative">{idx + 1}</span>
                    )}
                  </span>
                  <span
                    className={`w-full truncate text-center text-[10px] font-semibold leading-tight transition-colors duration-300 sm:text-[11px] ${
                      active ? 'text-white' : done ? 'text-emerald-100/80 group-hover:text-white' : 'text-white/40'
                    }`}
                  >
                    {s.short}
                  </span>
                </button>
              </li>
            );
          })}
        </ol>
      </nav>
    </div>
  );
}
