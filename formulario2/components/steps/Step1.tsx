import { TextInput, Field, FieldError, StepHeader, IconBadge, inputPlainClass } from '@/components/ui';

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
  const temCards = p.userType !== '';
  return (
    <div className="flex flex-1 flex-col space-y-8">
      <StepHeader
        icon="fas fa-user-tag"
        title="Quem é você?"
        subtitle="Selecione o seu perfil para continuarmos o cadastro."
      />

      <div
        className="field-container mx-auto grid w-full flex-1 auto-rows-fr grid-cols-1 gap-5"
        style={{ maxWidth: '40rem' }}
      >
        {(['locador', 'locatario'] as const).map((value) => {
          const isLocadorCard = value === 'locador';
          const active = p.userType === value;
          const oculto = p.userType !== '' && !active;
          return (
            <label
              key={value}
              className={`selection-card group relative flex min-h-[8rem] flex-1 cursor-pointer items-center gap-5 overflow-hidden rounded-3xl border-2 bg-white px-6 py-10 shadow-soft transition-all duration-300 hover-lift sm:px-8 sm:py-12 ${
                oculto ? 'hidden' : ''
              } ${
                active ? 'border-primary-500 ring-4 ring-primary-500/10' : 'border-slate-100 hover:border-primary-300'
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
                className={`relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl transition-all duration-300 ${
                  active
                    ? 'bg-brand-gradient text-white shadow-glow'
                    : 'bg-primary-100 text-primary-600 group-hover:bg-primary-200'
                }`}
              >
                <i className={`fas ${isLocadorCard ? 'fa-key' : 'fa-user-check'} text-2xl`}></i>
              </div>
              <div className="relative min-w-0 flex-1">
                <span className="block text-xl font-bold text-slate-900">
                  {isLocadorCard ? 'Sou Locador' : 'Sou Hóspede'}
                </span>
                <span className="mt-1 block text-sm text-slate-500">
                  {isLocadorCard ? 'Proprietário ou responsável pelo imóvel.' : 'Inquilino temporário'}
                </span>
              </div>
              <span
                className={`relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300 ${
                  active ? 'border-primary-600 bg-primary-600' : 'border-slate-200'
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
            <i className="fas fa-rotate-left text-xs"></i>
            Trocar perfil
          </button>
        </div>
      )}

      {p.userType === 'locatario' && (
        <div className="animate-slide-up space-y-6 rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 shadow-soft sm:p-6">
          <div className="flex items-center gap-3">
            <IconBadge icon="fas fa-user-tie" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
            <h3 className="text-base font-bold text-slate-900 sm:text-lg">Dados do Locador</h3>
          </div>

          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <Field label="Nome do Locador" error={p.errors.locador_nome}>
              <TextInput
                icon="fas fa-user"
                type="text"
                value={p.locadorNome}
                onChange={(e) => p.setLocadorNome(e.target.value)}
                placeholder="Nome do locador"
                error={!!p.errors.locador_nome}
              />
            </Field>

            <Field label="WhatsApp do Locador" error={p.errors.locador_telefone}>
              <div className="flex gap-2">
                <div className="relative w-1/3">
                  <input
                    type="text"
                    value={p.locadorDdi}
                    onChange={(e) => p.setLocadorDdi(e.target.value)}
                    placeholder="+55"
                    className={`${inputPlainClass} text-center pl-2 pr-2`}
                  />
                </div>
                <div className="flex-1">
                  <TextInput
                    icon="fab fa-whatsapp"
                    type="text"
                    value={p.locadorTelefone}
                    onChange={(e) => p.setLocadorTelefone(e.target.value)}
                    placeholder="(00) 00000-0000"
                    error={!!p.errors.locador_telefone}
                  />
                </div>
              </div>
            </Field>
          </div>
        </div>
      )}

      {p.userType === 'locador' && (
        <div className="animate-fade-in space-y-6 rounded-3xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 shadow-soft sm:p-6">
          <div className="flex items-center gap-3">
            <IconBadge icon="fas fa-user-pen" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
            <h3 className="text-base font-bold text-slate-900 sm:text-lg">Seus Dados</h3>
          </div>
          <Field label="Seu nome" error={p.errors.registrador_nome}>
            <TextInput
              icon="fas fa-user"
              type="text"
              value={p.registradorNome}
              onChange={(e) => p.setRegistradorNome(e.target.value)}
              placeholder="Informe seu nome"
              error={!!p.errors.registrador_nome}
            />
          </Field>
        </div>
      )}
    </div>
  );
}
