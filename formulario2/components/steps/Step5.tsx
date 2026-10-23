'use client';

import { useEffect, useRef } from 'react';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import { Field, StepHeader, TextInput, IconBadge } from '@/components/ui';
import { parseBrDate } from '@/lib/validation';

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
    let saidaInstance: flatpickr.Instance | null = null;
    const opts = {
      dateFormat: 'd/m/Y',
      minDate: 'today' as const,
      locale: Portuguese,
      disableMobile: true,
      animate: true,
      allowInput: false
    };

    if (entradaRef.current) {
      instances.push(
        flatpickr(entradaRef.current, {
          ...opts,
          defaultDate: p.dataEntrada || undefined,
          onChange: (_d, str) => {
            p.setDataEntrada(str);
            const entrada = parseBrDate(str);
            if (entrada && saidaInstance) saidaInstance.set('minDate', entrada);
          }
        })
      );
    }
    if (saidaRef.current) {
      saidaInstance = flatpickr(saidaRef.current, {
        ...opts,
        defaultDate: p.dataSaida || undefined,
        onChange: (_d, str) => p.setDataSaida(str)
      });
      instances.push(saidaInstance);
      const entrada = p.dataEntrada ? parseBrDate(p.dataEntrada) : null;
      if (entrada) saidaInstance.set('minDate', entrada);
    }

    return () => instances.forEach((i) => i.destroy());
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-calendar-check"
        title="Período da estadia"
        subtitle="Escolha as datas de entrada e saída no calendário."
      />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field
          label="Data de entrada"
          htmlFor="data_entrada"
          error={p.errors.data_entrada}
          hint="dd/mm/aaaa"
        >
          <TextInput
            id="data_entrada"
            ref={entradaRef}
            icon="fas fa-plane-arrival"
            type="text"
            defaultValue={p.dataEntrada}
            placeholder="Selecione a data"
            error={!!p.errors.data_entrada}
            aria-required="true"
            aria-describedby={`periodo-ajuda${p.errors.data_entrada ? ' data_entrada-error' : ''}`}
          />
        </Field>

        <Field
          label="Data de saída"
          htmlFor="data_saida"
          error={p.errors.data_saida}
          hint="dd/mm/aaaa"
        >
          <TextInput
            id="data_saida"
            ref={saidaRef}
            icon="fas fa-plane-departure"
            type="text"
            defaultValue={p.dataSaida}
            placeholder="Selecione a data"
            error={!!p.errors.data_saida}
            aria-required="true"
            aria-describedby={`periodo-ajuda${p.errors.data_saida ? ' data_saida-error' : ''}`}
          />
        </Field>
      </div>

      <div
        id="periodo-ajuda"
        className="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4"
      >
        <IconBadge icon="fas fa-circle-info" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
        <p className="text-xs leading-relaxed text-primary-800 sm:text-sm">
          Toque no campo para abrir o calendário e escolher a data. A data de saída deve ser igual ou
          posterior à data de entrada.
        </p>
      </div>
    </div>
  );
}
