'use client';

import type { ReactNode } from 'react';
import { cn } from '@/components/ui';

type Tone = 'info' | 'success' | 'warn';

const TONES: Record<Tone, { wrap: string; badge: string; icon: string }> = {
  info: {
    wrap: 'border-primary-100 bg-primary-50/70 text-primary-800',
    badge: 'bg-white/80 text-primary-600',
    icon: 'fas fa-circle-info'
  },
  success: {
    wrap: 'border-emerald-200 bg-emerald-50/80 text-emerald-800',
    badge: 'bg-white/85 text-emerald-600',
    icon: 'fas fa-circle-check'
  },
  warn: {
    wrap: 'border-amber-200 bg-amber-50/80 text-amber-800',
    badge: 'bg-white/85 text-amber-600',
    icon: 'fas fa-triangle-exclamation'
  }
};

interface Props {
  tone?: Tone;
  icon?: string;
  title?: string;
  children: ReactNode;
  action?: ReactNode;
  className?: string;
  id?: string;
}

export function HintCard({ tone = 'info', icon, title, children, action, className, id }: Props) {
  const styles = TONES[tone];
  return (
    <div
      id={id}
      role={tone === 'warn' ? 'alert' : 'status'}
      className={cn(
        'flex items-start gap-3 rounded-2xl border p-4 transition-colors duration-300',
        styles.wrap,
        className
      )}
    >
      <span
        className={cn(
          'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm shadow-soft',
          styles.badge
        )}
        aria-hidden="true"
      >
        <i className={icon || styles.icon} />
      </span>
      <div className="min-w-0 flex-1">
        {title && <p className="text-xs font-bold uppercase tracking-wide">{title}</p>}
        <div className={cn('text-xs leading-relaxed sm:text-sm', title && 'mt-1')}>{children}</div>
      </div>
      {action && <div className="shrink-0 self-center">{action}</div>}
    </div>
  );
}
