import { TextInput, Field, FieldError, StepHeader, IconBadge, inputPlainClass } from '@/components/ui';
import { formatPhone } from '@/lib/masks';

interface Props {
  userType: string;
  setUserType: (v: string) => void;
  locadorNome: string;
  setLocadorNome: (v: string) => void;
  locadorDdi: string;
  setLocadorDdi: (v: string) => void;
  locadorTelefone: string;
  setLocadorTelefone: (v: string) => void;
  registradorNome: string;
  setRegistradorNome: (v: string) => void;
  errors: Record<string, string>;
}

export default function Step1(p: Props) {
  return (
    <div className="flex flex-1 flex-col space-y-8">
      <StepHeader
        icon="fas fa-user-tag"
        title="Qual opção descreve você nesta locação?"
        subtitle="Selecione uma opção para continuar com o cadastro."
      />

      <div className="mx-auto grid w-full auto-rows-fr grid-cols-1 gap-4 sm:gap-5" style={{ maxWidth: '40rem' }}>
        {(['locador', 'locatario'] as const).map((value) => {
          const isLocadorCard = value === 'locador';
          const active = p.userType === value;
          const oculto = p.userType !== '' && !active;
          return (
            <label
              key={value}
              className={`selection-card group relative flex min-h-[7rem] cursor-pointer items-center gap-4 overflow-hidden rounded-3xl border-2 bg-white px-5 py-6 shadow-soft transition-all duration-300 hover-lift focus-within:ring-4 focus-within:ring-primary-500/20 sm:gap-5 sm:px-7 sm:py-8 ${
                oculto ? 'hidden' : ''
              } ${
                active
                  ? 'border-primary-600 ring-4 ring-primary-500/10'
                  : 'border-slate-200 hover:border-primary-300'
              }`}
            >
              <input
                type="radio"
                name="user_type"
                value={value}
                checked={active}
                onChange={() => p.setUserType(value)}
                className="sr-only"
              />
              <div
                className={`pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full transition-all duration-500 ${
                  active ? 'scale-125 bg-primary-100/80' : 'bg-slate-50 group-hover:bg-primary-50'
                }`}
              />
              <div
                className={`relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl transition-all duration-300 sm:h-16 sm:w-16 ${
                  active
                    ? 'bg-brand-gradient text-white shadow-glow'
                    : 'bg-primary-100 text-primary-600 group-hover:bg-primary-200'
                }`}
              >
                <i className={`fas ${isLocadorCard ? 'fa-key' : 'fa-user-check'} text-xl sm:text-2xl`}></i>
              </div>
              <div className="relative min-w-0 flex-1">
                <span className="block text-lg font-bold text-slate-900 sm:text-xl">
                  {isLocadorCard ? 'Sou Proprietário ou responsável' : 'Sou hóspede'}
                </span>
                <span className="mt-1 block text-sm leading-relaxed text-slate-600">
                  {isLocadorCard
                    ? 'Responsável pelo apartamento'
                    : 'Vou me hospedar em um apartamento'}
                </span>
              </div>
              <span
                aria-hidden="true"
                className={`relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300 ${
                  active ? 'border-primary-600 bg-primary-600' : 'border-slate-300'
                }`}
              >
                <i
                  className={`fas fa-check text-xs text-white transition-all duration-300 ${
                    active ? 'scale-100 opacity-100' : 'scale-50 opacity-0'
                  }`}
                ></i>
              </span>
            </label>
          );
        })}
      </div>

      <FieldError>{p.errors.user_type}</FieldError>

      {p.userType !== '' && (
        <div className="flex justify-center">
          <button
            type="button"
            onClick={() => p.setUserType('')}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-soft transition-all hover:border-primary-300 hover:text-primary-600"
          >
            <i className="fas fa-rotate-left text-xs" aria-hidden="true"></i>
            Trocar perfil
          </button>
        </div>
      )}

      {p.userType === 'locatario' && (
        <div className="animate-slide-up space-y-6 rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 shadow-soft sm:p-6">
          <div className="flex items-start gap-3">
            <IconBadge icon="fas fa-user-tie" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
            <div>
              <h3 className="text-base font-bold text-slate-900 sm:text-lg">Dados do Locador</h3>
              <p className="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">
                Como você está preenchendo como hóspede, informe o nome e o WhatsApp da pessoa que
                está alugando o apartamento para você.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <Field label="Nome do locador" htmlFor="locador_nome" error={p.errors.locador_nome}>
              <TextInput
                id="locador_nome"
                icon="fas fa-user"
                type="text"
                value={p.locadorNome}
                onChange={(e) => p.setLocadorNome(e.target.value)}
                placeholder="Nome do locador"
                error={!!p.errors.locador_nome}
                aria-describedby={p.errors.locador_nome ? 'locador_nome-error' : undefined}
              />
            </Field>

            <Field
              label="WhatsApp do locador"
              htmlFor="locador_telefone"
              error={p.errors.locador_telefone}
              hint="Com DDD. Ex.: (27) 99999-0000"
            >
              <div className="flex gap-2">
                <div className="relative w-1/3">
                  <input
                    type="text"
                    value={p.locadorDdi}
                    onChange={(e) => p.setLocadorDdi(e.target.value)}
                    placeholder="+55"
                    aria-label="Código do país (DDI)"
                    inputMode="tel"
                    className={`${inputPlainClass} text-center pl-2 pr-2`}
                  />
                </div>
                <div className="flex-1">
                  <TextInput
                    id="locador_telefone"
                    icon="fab fa-whatsapp"
                    type="text"
                    inputMode="tel"
                    autoComplete="tel"
                    value={p.locadorTelefone}
                    onChange={(e) => p.setLocadorTelefone(formatPhone(e.target.value))}
                    placeholder="(00) 00000-0000"
                    error={!!p.errors.locador_telefone}
                    aria-describedby={p.errors.locador_telefone ? 'locador_telefone-error' : undefined}
                  />
                </div>
              </div>
            </Field>
          </div>
        </div>
      )}

      {p.userType === 'locador' && (
        <div className="animate-fade-in space-y-6 rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 shadow-soft sm:p-6">
          <div className="flex items-start gap-3">
            <IconBadge icon="fas fa-user-pen" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
            <div>
              <h3 className="text-base font-bold text-slate-900 sm:text-lg">Seus dados</h3>
              <p className="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">
                Informe o seu nome como locador responsável por este cadastro.
              </p>
            </div>
          </div>
          <Field label="Seu nome" htmlFor="registrador_nome" error={p.errors.registrador_nome}>
            <TextInput
              id="registrador_nome"
              icon="fas fa-user"
              type="text"
              value={p.registradorNome}
              onChange={(e) => p.setRegistradorNome(e.target.value)}
              placeholder="Informe seu nome"
              error={!!p.errors.registrador_nome}
              aria-describedby={p.errors.registrador_nome ? 'registrador_nome-error' : undefined}
            />
          </Field>
        </div>
      )}
    </div>
  );
}
