'use client';

import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { AnimatePresence, motion } from 'framer-motion';
import type { StepGuide } from '@/lib/guide';
import { Button } from '@/components/ui';

interface Props {
  open: boolean;
  guide: StepGuide;
  stepIndex: number;
  total: number;
  onClose: () => void;
  onDismissAll: () => void;
}

interface Rect {
  top: number;
  left: number;
  width: number;
  height: number;
}

export function GuideTour({ open, guide, stepIndex, total, onClose, onDismissAll }: Props) {
  const [mounted, setMounted] = useState(false);
  const [rect, setRect] = useState<Rect | null>(null);
  const [pos, setPos] = useState<{ top: number; left: number; width: number } | null>(null);
  const [isMobile, setIsMobile] = useState(false);
  const tipRef = useRef<HTMLDivElement | null>(null);
  const scrolledRef = useRef(false);

  useEffect(() => setMounted(true), []);

  useEffect(() => {
    if (!open) return;

    scrolledRef.current = false;

    const measure = () => {
      const mobile = window.innerWidth < 640;
      setIsMobile(mobile);

      const target = document.querySelector<HTMLElement>(guide.target);
      if (!target) {
        setRect(null);
        setPos(null);
        return;
      }

      const r = target.getBoundingClientRect();
      setRect({ top: r.top, left: r.left, width: r.width, height: r.height });

      if (!scrolledRef.current && (r.top < 8 || r.bottom > window.innerHeight - 8)) {
        scrolledRef.current = true;
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return;
      }

      if (mobile) {
        setPos(null);
        return;
      }

      const width = Math.min(340, window.innerWidth - 32);
      const height = tipRef.current?.offsetHeight ?? 190;
      const margin = 12;
      const spaceBelow = window.innerHeight - r.bottom - margin;
      const spaceAbove = r.top - margin;

      let top: number;
      if (spaceBelow >= height) {
        top = r.bottom + margin;
      } else if (spaceAbove >= height) {
        top = r.top - height - margin;
      } else if (spaceBelow >= spaceAbove) {
        top = Math.min(window.innerHeight - height - margin, r.bottom + margin);
      } else {
        top = Math.max(margin, r.top - height - margin);
      }

      const left = Math.min(
        Math.max(16, r.left + r.width / 2 - width / 2),
        window.innerWidth - width - 16
      );
      setPos({ top, left, width });
    };

    measure();
    const timer = window.setTimeout(measure, 420);

    window.addEventListener('resize', measure);
    window.addEventListener('scroll', measure, true);
    return () => {
      window.clearTimeout(timer);
      window.removeEventListener('resize', measure);
      window.removeEventListener('scroll', measure, true);
    };
  }, [open, guide.target, stepIndex]);

  useEffect(() => {
    if (!open) return;
    const timer = window.setTimeout(() => tipRef.current?.focus({ preventScroll: true }), 80);
    return () => window.clearTimeout(timer);
  }, [open, stepIndex]);

  useEffect(() => {
    if (!open) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!mounted) return null;

  return createPortal(
    <AnimatePresence>
      {open && (
        <motion.div
          key="guide"
          className="fixed inset-0 z-[80]"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <div className="absolute inset-0" onClick={onClose} aria-hidden="true" />

          {rect ? (
            <div
              aria-hidden="true"
              className="pointer-events-none fixed rounded-2xl border-2 border-accent-300/90 transition-all duration-300"
              style={{
                top: rect.top - 7,
                left: rect.left - 7,
                width: rect.width + 14,
                height: rect.height + 14,
                boxShadow:
                  '0 0 0 9999px rgba(2,18,15,0.74), 0 0 0 6px rgba(52,211,153,0.22), 0 18px 40px -12px rgba(0,0,0,0.55)'
              }}
            />
          ) : (
            <div aria-hidden="true" className="pointer-events-none absolute inset-0 bg-[#02120f]/75" />
          )}

          <motion.div
            ref={tipRef}
            role="dialog"
            aria-modal="true"
            aria-labelledby="guide-title"
            aria-describedby="guide-text"
            tabIndex={-1}
            className={[
              'fixed max-h-[72vh] overflow-y-auto rounded-3xl border border-white/60 bg-white p-5 shadow-2xl',
              isMobile || !pos ? 'inset-x-3 bottom-3' : ''
            ]
              .filter(Boolean)
              .join(' ')}
            style={!isMobile && pos ? { top: pos.top, left: pos.left, width: pos.width } : undefined}
            initial={{ opacity: 0, y: 14, scale: 0.97 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.97 }}
            transition={{ type: 'spring', stiffness: 320, damping: 28 }}
          >
            <div className="flex items-start justify-between gap-3">
              <span className="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-primary-700">
                <i className="fas fa-lightbulb" aria-hidden="true" />
                Dica {stepIndex + 1} de {total}
              </span>
              <button
                type="button"
                onClick={onClose}
                aria-label="Fechar dica"
                className="-mr-1 -mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
              >
                <i className="fas fa-times" aria-hidden="true" />
              </button>
            </div>

            <h3 id="guide-title" className="mt-3 text-base font-extrabold text-slate-900 sm:text-lg">
              {guide.title}
            </h3>
            <p id="guide-text" className="mt-1.5 text-sm leading-relaxed text-slate-600">
              {guide.text}
            </p>

            <div className="mt-4 flex items-center gap-1.5" aria-hidden="true">
              {Array.from({ length: total }).map((_, index) => (
                <span
                  key={index}
                  className={`h-1.5 rounded-full transition-all duration-300 ${
                    index === stepIndex
                      ? 'w-6 bg-primary-500'
                      : index < stepIndex
                        ? 'w-1.5 bg-primary-300'
                        : 'w-1.5 bg-slate-200'
                  }`}
                />
              ))}
            </div>

            <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <button
                type="button"
                onClick={onDismissAll}
                className="order-2 rounded-xl px-2 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 sm:order-1"
              >
                Não mostrar mais
              </button>
              <Button
                type="button"
                size="md"
                iconRight="fas fa-arrow-right"
                onClick={onClose}
                className="order-1 w-full sm:order-2 sm:w-auto"
              >
                Entendi
              </Button>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>,
    document.body
  );
}
