import type { Veiculo } from '@/lib/types';
import { Button, Field, IconBadge, StepHeader, TextInput } from '@/components/ui';

interface Props {
  veiculos: Veiculo[];
  isLocador: boolean;
  setVeiculo: (index: number, field: 'modelo' | 'cor' | 'placa' | 'acesso_garagem', value: string) => void;
  addVeiculo: () => void;
  removeVeiculo: (index: number) => void;
  onSkip: () => void;
  errors: Record<string, string>;
}

export default function Step4(p: Props) {
  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-car-side"
        title="Veículos"
        subtitle="Informe os veículos que ficarão na garagem."
      />

      {p.veiculos.length === 0 && (
        <div className="rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 p-6 text-center sm:p-8">
          <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <i className="fas fa-car text-lg" aria-hidden="true"></i>
          </span>
          <p className="mt-3 font-semibold text-slate-800">Nenhum veículo adicionado</p>
          <p className="mt-2 text-lg font-bold text-primary-700">Esta etapa é opcional.</p>
          <p className="mt-1 text-sm leading-relaxed text-slate-600">
            Se não tiver veículo, basta clicar em <strong>Próxima</strong>.
          </p>
        </div>
      )}

      <div className="space-y-5">
        {p.veiculos.map((v, index) => {
          const modeloError = p.errors[`vei_${index}_modelo`];
          return (
            <div
              key={index}
              className="group relative rounded-3xl border border-slate-100 bg-white p-4 shadow-soft transition-all duration-300 animate-fade-in sm:p-6"
            >
              <div className="mb-5 flex items-start justify-between gap-3 sm:mb-6">
                <div className="flex items-center gap-3">
                  <IconBadge icon="fas fa-car" tone="primary" className="h-9 w-9 rounded-xl text-sm" />
                  <div>
                    <h3 className="text-base font-semibold text-slate-900 sm:text-lg">Veículo {index + 1}</h3>
                    <p className="text-xs text-slate-500">Preencha ao menos o modelo para cadastrar.</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => p.removeVeiculo(index)}
                  aria-label={`Remover veículo ${index + 1}`}
                  className="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-red-100 bg-red-50 px-2.5 py-2 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100"
                >
                  <i className="fas fa-trash-can text-[11px]" aria-hidden="true"></i>
                  Remover
                </button>
              </div>

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Modelo" htmlFor={`vei_${index}_modelo`} error={modeloError}>
                  <TextInput
                    id={`vei_${index}_modelo`}
                    icon="fas fa-car"
                    type="text"
                    value={v.modelo}
                    onChange={(e) => p.setVeiculo(index, 'modelo', e.target.value)}
                    placeholder="Ex: Toyota Corolla"
                    error={!!modeloError}
                    aria-describedby={modeloError ? `vei_${index}_modelo-error` : undefined}
                  />
                </Field>

                <Field label="Cor" htmlFor={`vei_${index}_cor`}>
                  <TextInput
                    id={`vei_${index}_cor`}
                    icon="fas fa-palette"
                    type="text"
                    value={v.cor}
                    onChange={(e) => p.setVeiculo(index, 'cor', e.target.value)}
                    placeholder="Ex: Prata"
                  />
                </Field>

                <Field label="Placa" htmlFor={`vei_${index}_placa`} hint="Ex.: ABC1D23 (7 caracteres)">
                  <TextInput
                    id={`vei_${index}_placa`}
                    icon="fas fa-hashtag"
                    type="text"
                    value={v.placa}
                    onChange={(e) => p.setVeiculo(index, 'placa', e.target.value.toUpperCase())}
                    placeholder="ABC1D23"
                    className="font-mono font-bold uppercase tracking-[0.2em]"
                    maxLength={7}
                  />
                </Field>

                {p.isLocador && (
                  <Field label="Acesso de garagem" htmlFor={`vei_${index}_garagem`} className="acesso-garagem-container">
                    <TextInput
                      id={`vei_${index}_garagem`}
                      icon="fas fa-warehouse"
                      type="text"
                      value={v.acesso_garagem}
                      onChange={(e) => p.setVeiculo(index, 'acesso_garagem', e.target.value)}
                      placeholder="Ex: Térreo, Rampa ou Subsolo"
                    />
                  </Field>
                )}
              </div>
            </div>
          );
        })}
      </div>

      <button
        type="button"
        onClick={p.addVeiculo}
        className="group flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-white/60 py-4 font-semibold text-slate-500 transition-all duration-300 hover:border-primary-500 hover:bg-primary-50/50 hover:text-primary-600"
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 transition-colors duration-300 group-hover:bg-primary-100 group-hover:text-primary-600">
          <i className="fas fa-plus" aria-hidden="true"></i>
        </span>
        {p.veiculos.length === 0 ? 'Adicionar veículo' : 'Adicionar outro veículo'}
      </button>

      {p.veiculos.length > 0 && (
        <Button
          type="button"
          variant="secondary"
          size="lg"
          icon="fas fa-ban"
          onClick={p.onSkip}
          className="w-full"
        >
          Não tenho veículo para cadastrar
        </Button>
      )}
    </div>
  );
}
