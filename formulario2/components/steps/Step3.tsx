import type { Inquilino } from '@/lib/types';
import { StepHeader, TextInput, Field, IconBadge } from '@/components/ui';
import { formatPhone } from '@/lib/masks';
import { SelfieField } from '@/components/SelfieField';

interface Props {
  inquilinos: Inquilino[];
  requiresSelfie: boolean;
  setField: (index: number, field: 'nome' | 'documento' | 'telefone', value: string) => void;
  setSelfie: (index: number, file: File | null) => void;
  addInquilino: () => void;
  removeInquilino: (index: number) => void;
  errors: Record<string, string>;
}

export default function Step3(p: Props) {
  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-users"
        title="Hóspedes / Inquilinos"
        subtitle="Quem ficará no imóvel? Adicione todos os ocupantes."
      />

      <div className="space-y-5">
        {p.inquilinos.map((inq, index) => {
          const isPrincipal = index === 0;
          return (
            <div
              key={index}
              className="inquilino-item group relative rounded-3xl border border-slate-100 bg-white p-4 shadow-soft transition-all duration-300 animate-fade-in sm:p-6"
            >
              {!isPrincipal && (
                <button
                  type="button"
                  onClick={() => p.removeInquilino(index)}
                  aria-label="Remover hóspede"
                  className="remove-item absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition-all hover:scale-105 hover:bg-red-600"
                >
                  <i className="fas fa-times text-xs"></i>
                </button>
              )}

              <div className="mb-5 flex items-center gap-3 sm:mb-6">
                <IconBadge
                  icon={isPrincipal ? 'fas fa-star' : 'fas fa-user-plus'}
                  tone={isPrincipal ? 'primary' : 'soft'}
                  className="h-9 w-9 rounded-xl text-sm"
                />
                <div>
                  <h3 className="text-base font-semibold text-slate-900 sm:text-lg">
                    {isPrincipal ? 'Hóspede Principal' : 'Hóspede Adicional'}
                  </h3>
                  <p className="text-xs text-slate-400">
                    {isPrincipal ? 'Responsável principal' : 'Ocupante adicional'}
                  </p>
                </div>
              </div>

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Field label="Nome Completo" error={p.errors[`inq_${index}_nome`]}>
                  <TextInput
                    icon="fas fa-user"
                    type="text"
                    value={inq.nome}
                    onChange={(e) => p.setField(index, 'nome', e.target.value)}
                    onBlur={(e) => p.setField(index, 'nome', e.target.value.trim())}
                    placeholder="Nome do hóspede"
                    error={!!p.errors[`inq_${index}_nome`]}
                  />
                </Field>

                <Field label="Documento" error={p.errors[`inq_${index}_documento`]}>
                  <TextInput
                    icon="fas fa-id-card"
                    type="text"
                    value={inq.documento}
                    onChange={(e) => p.setField(index, 'documento', e.target.value)}
                    placeholder="Número do documento"
                    error={!!p.errors[`inq_${index}_documento`]}
                  />
                </Field>

                <Field label="Telefone">
                  <TextInput
                    icon="fas fa-phone"
                    type="text"
                    value={inq.telefone}
                    onChange={(e) => p.setField(index, 'telefone', formatPhone(e.target.value))}
                    placeholder="(00) 00000-0000"
                  />
                </Field>
              </div>

              {p.requiresSelfie && (
                <div className="mt-5 space-y-2 selfie-field-container">
                  <label className="block text-sm font-semibold text-slate-700">Selfie do Hóspede</label>
                  <SelfieField
                    index={index}
                    value={inq.selfie}
                    nome={inq.nome}
                    withExample={isPrincipal}
                    onChange={(file) => p.setSelfie(index, file)}
                  />
                </div>
              )}
            </div>
          );
        })}
      </div>

      <button
        type="button"
        onClick={p.addInquilino}
        className="group flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-white/60 py-4 font-semibold text-slate-500 transition-all duration-300 hover:border-primary-500 hover:bg-primary-50/50 hover:text-primary-600"
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 transition-colors duration-300 group-hover:bg-primary-100 group-hover:text-primary-600">
          <i className="fas fa-plus"></i>
        </span>
        Adicionar outro hóspede
      </button>
    </div>
  );
}
