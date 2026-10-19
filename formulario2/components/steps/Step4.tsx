import type { Veiculo } from '@/lib/types';
import { StepHeader, TextInput, Field, IconBadge } from '@/components/ui';

interface Props {
  veiculos: Veiculo[];
  isLocador: boolean;
  setVeiculo: (index: number, field: 'modelo' | 'cor' | 'placa' | 'acesso_garagem', value: string) => void;
  addVeiculo: () => void;
  removeVeiculo: (index: number) => void;
}

export default function Step4(p: Props) {
  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-car-side"
        title="Veículos"
        subtitle="Informe os dados dos veículos que ficarão na garagem."
      />

      <div className="space-y-5">
        {p.veiculos.map((v, index) => (
          <div
            key={index}
            className="group relative rounded-3xl border border-slate-100 bg-white p-4 shadow-soft transition-all duration-300 animate-fade-in sm:p-6"
          >
            {index > 0 && (
              <button
                type="button"
                onClick={() => p.removeVeiculo(index)}
                aria-label="Remover veículo"
                className="remove-item absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition-all hover:scale-105 hover:bg-red-600"
              >
                <i className="fas fa-times text-xs"></i>
              </button>
            )}

            <div className="mb-5 flex items-center gap-3 sm:mb-6">
              <IconBadge icon="fas fa-car" tone="primary" className="h-9 w-9 rounded-xl text-sm" />
              <div>
                <h3 className="text-base font-semibold text-slate-900 sm:text-lg">
                  {index === 0 ? 'Veículo 1' : 'Veículo Adicional'}
                </h3>
                <p className="text-xs text-slate-400">Dados do veículo {index + 1}</p>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <Field label="Modelo">
                <TextInput
                  icon="fas fa-car"
                  type="text"
                  value={v.modelo}
                  onChange={(e) => p.setVeiculo(index, 'modelo', e.target.value)}
                  placeholder="Ex: Toyota Corolla"
                />
              </Field>

              <Field label="Cor">
                <TextInput
                  icon="fas fa-palette"
                  type="text"
                  value={v.cor}
                  onChange={(e) => p.setVeiculo(index, 'cor', e.target.value)}
                  placeholder="Ex: Prata"
                />
              </Field>

              <Field label="Placa">
                <TextInput
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
                <Field label="Acesso de garagem" className="acesso-garagem-container">
                  <TextInput
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
        ))}
      </div>

      <button
        type="button"
        onClick={p.addVeiculo}
        className="group flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-white/60 py-4 font-semibold text-slate-500 transition-all duration-300 hover:border-primary-500 hover:bg-primary-50/50 hover:text-primary-600"
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 transition-colors duration-300 group-hover:bg-primary-100 group-hover:text-primary-600">
          <i className="fas fa-plus"></i>
        </span>
        Adicionar outro veículo
      </button>
    </div>
  );
}
