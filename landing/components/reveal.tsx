"use client";

import { useEffect, useRef, type ElementType, type ReactNode } from "react";

export function Reveal({
  children,
  atraso = 0,
  className = "",
  as: Tag = "div",
}: {
  children: ReactNode;
  atraso?: number;
  className?: string;
  as?: ElementType;
}) {
  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    const alvo = ref.current;
    if (!alvo) return;

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      alvo.dataset.visivel = "true";
      return;
    }

    const obs = new IntersectionObserver(
      (entradas) => {
        for (const e of entradas) {
          if (e.isIntersecting) {
            e.target.setAttribute("data-visivel", "true");
            obs.unobserve(e.target);
          }
        }
      },
      { rootMargin: "0px 0px -12% 0px", threshold: 0.08 },
    );

    obs.observe(alvo);
    return () => obs.disconnect();
  }, []);

  return (
    <Tag
      ref={ref}
      className={`reveal ${className}`}
      style={atraso ? { transitionDelay: `${atraso}ms` } : undefined}
    >
      {children}
    </Tag>
  );
}
