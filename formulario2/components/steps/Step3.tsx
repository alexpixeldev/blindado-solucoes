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
        subtitle="Informe quem ficará no imóvel. Nome e documento são obrigatórios; o telefone é opcional."
      />

      <div className="space-y-5">
        {p.inquilinos.map((inq, index) => {
          const isPrincipal = index === 0;
          const nomeError = p.errors[`inq_${index}_nome`];
          const documentoError = p.errors[`inq_${index}_documento`];
          const telefoneError = p.errors[`inq_${index}_telefone`];
          return (
            <div
              key={index}
              className="inquilino-item relative rounded-3xl border border-slate-100 bg-white p-4 shadow-soft transition-all duration-300 animate-fade-in sm:p-6"
            >
              <div className="mb-5 flex items-start justify-between gap-3 sm:mb-6">
                <div className="flex items-center gap-3">
                  <IconBadge
                    icon={isPrincipal ? 'fas fa-star' : 'fas fa-user-plus'}
                    tone={isPrincipal ? 'primary' : 'soft'}
                    className="h-9 w-9 rounded-xl text-sm"
                  />
                  <div>
                    <h3 className="text-base font-semibold text-slate-900 sm:text-lg">
                      {isPrincipal ? 'Hóspede principal' : `Hóspede ${index + 1}`}
                    </h3>
                    <p className="text-xs text-slate-500">
                      {isPrincipal ? 'Responsável pelas informações da estadia' : 'Ocupante adicional'}
                    </p>
                  </div>
                </div>
                {!isPrincipal && (
                  <button
                    type="button"
                    onClick={() => p.removeInquilino(index)}
                    aria-label={`Remover hóspede ${index + 1}`}
                    className="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-red-100 bg-red-50 px-2.5 py-2 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100"
                  >
                    <i className="fas fa-trash-can text-[11px]" aria-hidden="true"></i>
                    Remover
                  </button>
                )}
              </div>

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Field label="Nome completo" htmlFor={`inq_${index}_nome`} error={nomeError}>
                  <TextInput
                    id={`inq_${index}_nome`}
                    icon="fas fa-user"
                    type="text"
                    value={inq.nome}
                    onChange={(e) => p.setField(index, 'nome', e.target.value)}
                    onBlur={(e) => p.setField(index, 'nome', e.target.value.trim())}
                    placeholder="Nome do hóspede"
                    error={!!nomeError}
                    aria-describedby={nomeError ? `inq_${index}_nome-error` : undefined}
                  />
                </Field>

                <Field
                  label="Documento"
                  htmlFor={`inq_${index}_documento`}
                  error={documentoError}
                  hint="Informe o número do documento que o hóspede apresentará."
                >
                  <TextInput
                    id={`inq_${index}_documento`}
                    icon="fas fa-id-card"
                    type="text"
                    value={inq.documento}
                    onChange={(e) => p.setField(index, 'documento', e.target.value)}
                    placeholder="Número do documento"
                    error={!!documentoError}
                    aria-describedby={documentoError ? `inq_${index}_documento-error` : undefined}
                  />
                </Field>

                <Field label="Telefone (opcional)" htmlFor={`inq_${index}_telefone`} error={telefoneError}>
                  <TextInput
                    id={`inq_${index}_telefone`}
                    icon="fas fa-phone"
                    type="text"
                    inputMode="tel"
                    autoComplete="tel"
                    value={inq.telefone}
                    onChange={(e) => p.setField(index, 'telefone', formatPhone(e.target.value))}
                    placeholder="(00) 00000-0000"
                    error={!!telefoneError}
                    aria-describedby={telefoneError ? `inq_${index}_telefone-error` : undefined}
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
          <i className="fas fa-plus" aria-hidden="true"></i>
        </span>
        Adicionar outro hóspede
      </button>

      <p className="text-center text-xs leading-relaxed text-slate-500">
        Use o botão acima para incluir outra pessoa que ficará no imóvel. Para remover um hóspede
        adicional, use o botão <strong>Remover</strong> no cartão correspondente.
      </p>
    </div>
  );
}
