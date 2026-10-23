'use client';

import { forwardRef } from 'react';
import { motion } from 'framer-motion';

export function cn(...classes: Array<string | false | null | undefined>) {
  return classes.filter(Boolean).join(' ');
}

export function Reveal({
  delay = 0,
  y = 16,
  className,
  children
}: {
  delay?: number;
  y?: number;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay, ease: [0.22, 1, 0.36, 1] }}
    >
      {children}
    </motion.div>
  );
}

export const labelClass = 'mb-2 block text-sm font-semibold text-slate-700';

const inputBase =
  'block w-full rounded-2xl border bg-slate-50/70 py-3.5 text-sm font-medium text-slate-900 shadow-field transition-all duration-200 placeholder:font-normal placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/15 disabled:cursor-not-allowed disabled:opacity-60';

export const inputClass = `${inputBase} border-slate-200 pl-12 pr-4 hover:border-slate-300 focus:border-primary-500`;
export const inputPlainClass = `${inputBase} border-slate-200 px-4 hover:border-slate-300 focus:border-primary-500`;
export const inputErrorClass = `${inputBase} border-red-300 bg-red-50/40 pl-12 pr-4 focus:border-red-500 focus:ring-red-500/15`;

export const iconWrap =
  'pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors group-focus-within:text-primary-500';

export function FieldError({ children, id }: { children?: React.ReactNode; id?: string }) {
  if (!children) return null;
  return (
    <p id={id} role="alert" className="mt-2 flex items-start gap-1.5 text-xs font-medium text-red-500 animate-fade-in">
      <i className="fas fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
      <span>{children}</span>
    </p>
  );
}

export function Field({
  label,
  htmlFor,
  error,
  hint,
  className,
  children
}: {
  label?: string;
  htmlFor?: string;
  error?: string;
  hint?: string;
  className?: string;
  children: React.ReactNode;
}) {
  const errorId = htmlFor ? `${htmlFor}-error` : undefined;
  return (
    <div className={['field-container', className].filter(Boolean).join(' ')}>
      {label && (
        <label htmlFor={htmlFor} className={labelClass}>
          {label}
        </label>
      )}
      {children}
      {hint && !error && <p className="mt-2 text-xs text-slate-400">{hint}</p>}
      <FieldError id={errorId}>{error}</FieldError>
    </div>
  );
}

export interface TextInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  icon?: string;
  error?: boolean;
}

export const TextInput = forwardRef<HTMLInputElement, TextInputProps>(function TextInput(
  { icon, error, className, ...props },
  ref
) {
  const base = error
    ? `${inputBase} border-red-300 bg-red-50/40 focus:border-red-500 focus:ring-red-500/15`
    : `${inputBase} border-slate-200 hover:border-slate-300 focus:border-primary-500`;
  return (
    <div className="group relative">
      {icon && (
        <span className={iconWrap}>
          <i className={icon} aria-hidden="true"></i>
        </span>
      )}
      <input
        ref={ref}
        className={[base, icon ? 'pl-12 pr-4' : 'px-4', className].filter(Boolean).join(' ')}
        aria-invalid={error || undefined}
        {...props}
      />
    </div>
  );
});

type Variant = 'primary' | 'secondary' | 'dark' | 'ghost' | 'danger' | 'whatsapp';
type Size = 'sm' | 'md' | 'lg';

const variantClasses: Record<Variant, string> = {
  primary:
    'btn-shine bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-glow hover:shadow-glow-lg',
  secondary:
    'border border-slate-200 bg-white text-slate-600 shadow-soft hover:bg-slate-50 hover:text-slate-900',
  dark: 'btn-shine bg-gradient-to-br from-slate-800 to-slate-950 text-white shadow-soft hover:shadow-lg',
  ghost: 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
  danger: 'bg-red-500 text-white shadow-soft hover:bg-red-600',
  whatsapp:
    'btn-shine bg-gradient-to-br from-[#25d366] to-[#128c4b] text-white shadow-lg shadow-[#25d366]/30'
};

const sizeClasses: Record<Size, string> = {
  sm: 'gap-1.5 px-4 py-2.5 text-xs',
  md: 'gap-2 px-5 py-3 text-sm',
  lg: 'gap-2.5 px-6 py-3.5 text-sm sm:text-base'
};

type ButtonDomProps = Pick<
  React.ButtonHTMLAttributes<HTMLButtonElement>,
  'type' | 'disabled' | 'onClick' | 'title' | 'name' | 'value' | 'form' | 'autoFocus' | 'id'
>;

export function Button({
  variant = 'primary',
  size = 'md',
  icon,
  iconRight,
  loading,
  className,
  children,
  ...props
}: ButtonDomProps & {
  variant?: Variant;
  size?: Size;
  icon?: string;
  iconRight?: string;
  loading?: boolean;
  className?: string;
  children?: React.ReactNode;
  'aria-label'?: string;
}) {
  const disabled = props.disabled;
  return (
    <motion.button
      whileHover={disabled ? undefined : { y: -2 }}
      whileTap={disabled ? undefined : { scale: 0.97 }}
      transition={{ type: 'spring', stiffness: 420, damping: 18 }}
      className={[
        'inline-flex items-center justify-center rounded-2xl font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-60',
        sizeClasses[size],
        variantClasses[variant],
        className
      ]
        .filter(Boolean)
        .join(' ')}
      {...props}
    >
      {loading ? (
        <i className="fas fa-circle-notch animate-spin" aria-hidden="true"></i>
      ) : (
        icon && <i className={icon} aria-hidden="true"></i>
      )}
      {children}
      {iconRight && <i className={iconRight} aria-hidden="true"></i>}
    </motion.button>
  );
}

export function IconBadge({
  icon,
  tone = 'primary',
  className
}: {
  icon: string;
  tone?: 'primary' | 'soft' | 'accent';
  className?: string;
}) {
  const tones = {
    primary: 'bg-brand-gradient text-white shadow-glow',
    soft: 'bg-primary-100 text-primary-600',
    accent: 'bg-accent-gradient text-white shadow-soft'
  };
  return (
    <span
      className={[
        'relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl',
        tones[tone],
        className
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {tone === 'primary' && (
        <span className="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-br from-white/30 to-transparent" />
      )}
      <i className={`${icon} relative text-lg`} aria-hidden="true"></i>
    </span>
  );
}

export function StepHeader({
  icon,
  title,
  subtitle
}: {
  icon: string;
  title: string;
  subtitle?: string;
}) {
  return (
    <div className="mb-6 flex items-start gap-4 animate-fade-in sm:mb-8">
      <IconBadge icon={icon} />
      <div className="min-w-0 pt-0.5">
        <h2 className="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{title}</h2>
        {subtitle && <p className="mt-1 text-sm leading-relaxed text-slate-500">{subtitle}</p>}
      </div>
    </div>
  );
}

export function SectionCard({
  icon,
  title,
  action,
  className,
  children
}: {
  icon?: string;
  title?: string;
  action?: React.ReactNode;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <div
      className={['rounded-3xl border border-slate-100 bg-white p-5 shadow-soft sm:p-6', className]
        .filter(Boolean)
        .join(' ')}
    >
      {title && (
        <div className="mb-4 flex items-center justify-between gap-3">
          <h4 className="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
            {icon && <i className={`${icon} text-primary-500`} aria-hidden="true"></i>}
            {title}
          </h4>
          {action}
        </div>
      )}
      {children}
    </div>
  );
}

export function Chip({ icon, children, className }: { icon?: string; children: React.ReactNode; className?: string }) {
  return (
    <span
      className={[
        'inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white/70 px-3 py-1 text-xs font-medium text-slate-600 shadow-soft',
        className
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {icon && <i className={`${icon} text-primary-500`} aria-hidden="true"></i>}
      {children}
    </span>
  );
}
