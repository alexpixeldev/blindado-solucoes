'use client';

import { Field, IconBadge, StepHeader, TextInput } from '@/components/ui';
import RangeCalendar from '@/components/RangeCalendar';

interface Props {
  dataEntrada: string;
  setDataEntrada: (v: string) => void;
  dataSaida: string;
  setDataSaida: (v: string) => void;
  errors: Record<string, string>;
}

export default function Step5(p: Props) {
  return (
    <div className="space-y-6">
      <StepHeader
        icon="fas fa-calendar-check"
        title="Período da estadia"
        subtitle="Selecione a data de entrada e a data de saída no calendário."
      />

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field
          label="Data de entrada"
          htmlFor="data_entrada"
          error={p.errors.data_entrada}
          hint="dd/mm/aaaa"
        >
          <TextInput
            id="data_entrada"
            icon="fas fa-plane-arrival"
            type="text"
            value={p.dataEntrada}
            readOnly
            placeholder="Selecione a data"
            error={!!p.errors.data_entrada}
            className="cursor-default"
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
            icon="fas fa-plane-departure"
            type="text"
            value={p.dataSaida}
            readOnly
            placeholder="Selecione a data"
            error={!!p.errors.data_saida}
            className="cursor-default"
            aria-required="true"
            aria-describedby={`periodo-ajuda${p.errors.data_saida ? ' data_saida-error' : ''}`}
          />
        </Field>
      </div>

      <RangeCalendar
        entrada={p.dataEntrada}
        saida={p.dataSaida}
        onChange={(entrada, saida) => {
          p.setDataEntrada(entrada);
          p.setDataSaida(saida);
        }}
        describedBy="periodo-ajuda"
      />

      <div
        id="periodo-ajuda"
        className="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4"
      >
        <IconBadge icon="fas fa-circle-info" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
        <p className="text-xs leading-relaxed text-primary-800 sm:text-sm">
          Selecione primeiro a data de entrada e depois a data de saída no calendário. A data de
          saída deve ser igual ou posterior à data de entrada. Você também pode navegar pelas datas
          com as setas do teclado.
        </p>
      </div>
    </div>
  );
}
