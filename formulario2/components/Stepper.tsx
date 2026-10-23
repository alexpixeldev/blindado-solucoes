'use client';

import { motion } from 'framer-motion';

const steps = [
  { title: 'Perfil', icon: 'fa-user-tag' },
  { title: 'Seleção do edifício', icon: 'fa-building' },
  { title: 'Hóspedes', icon: 'fa-users' },
  { title: 'Veículos', icon: 'fa-car-side' },
  { title: 'Período da estadia', icon: 'fa-calendar-check' },
  { title: 'Revisão', icon: 'fa-clipboard-check' }
];

export function Stepper({ current }: { current: number }) {
  return (
    <div className="w-full">
      <div className="flex items-center gap-1.5 sm:gap-2" aria-hidden="true">
        {steps.map((s, idx) => {
          const done = idx < current;
          const active = idx === current;
          return (
            <div key={s.title} className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/20">
              <motion.div
                className="h-full rounded-full bg-gradient-to-r from-primary-300 via-primary-400 to-accent-400"
                initial={false}
                animate={{ width: done ? '100%' : active ? '55%' : '6%' }}
                transition={{ type: 'spring', stiffness: 120, damping: 22 }}
              />
            </div>
          );
        })}
      </div>
      <p className="mt-2 flex items-center gap-2 text-[11px] font-semibold sm:text-xs" aria-live="polite">
        <span className="text-white">{steps[current].title}</span>
        <span className="text-white/30" aria-hidden="true">•</span>
        <span className="text-emerald-100/60">
          Etapa {current + 1} de {steps.length}
        </span>
      </p>
    </div>
  );
}
