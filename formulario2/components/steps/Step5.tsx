'use client';

import { useEffect, useRef } from 'react';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import { Field, StepHeader, TextInput, IconBadge } from '@/components/ui';

interface Props {
  dataEntrada: string;
  setDataEntrada: (v: string) => void;
  dataSaida: string;
  setDataSaida: (v: string) => void;
  errors: Record<string, string>;
}

export default function Step5(p: Props) {
  const entradaRef = useRef<HTMLInputElement>(null);
  const saidaRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const instances: flatpickr.Instance[] = [];
    const opts = {
      dateFormat: 'd/m/Y',
      minDate: 'today',
      locale: Portuguese,
      disableMobile: true,
      animate: true
    };

    if (entradaRef.current) {
      instances.push(
        flatpickr(entradaRef.current, {
          ...opts,
          defaultDate: p.dataEntrada || undefined,
          onChange: (_d, str) => p.setDataEntrada(str)
        })
      );
    }
    if (saidaRef.current) {
      instances.push(
        flatpickr(saidaRef.current, {
          ...opts,
          defaultDate: p.dataSaida || undefined,
          onChange: (_d, str) => p.setDataSaida(str)
        })
      );
    }

    return () => instances.forEach((i) => i.destroy());
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-calendar-check"
        title="Check-in"
        subtitle="Quase lá! Informe o período da sua estadia."
      />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="Data de Entrada" error={p.errors.data_entrada}>
          <TextInput
            ref={entradaRef}
            icon="fas fa-plane-arrival"
            type="text"
            defaultValue={p.dataEntrada}
            placeholder="dd/mm/aaaa"
            error={!!p.errors.data_entrada}
          />
        </Field>

        <Field label="Data de Saída" error={p.errors.data_saida}>
          <TextInput
            ref={saidaRef}
            icon="fas fa-plane-departure"
            type="text"
            defaultValue={p.dataSaida}
            placeholder="dd/mm/aaaa"
            error={!!p.errors.data_saida}
          />
        </Field>
      </div>

      <div className="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4">
        <IconBadge icon="fas fa-circle-info" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
        <p className="text-xs leading-relaxed text-primary-800 sm:text-sm">
          Toque no campo para abrir o calendário e escolher a data. A data de saída deve ser igual ou
          posterior à data de entrada.
        </p>
      </div>
    </div>
  );
}
