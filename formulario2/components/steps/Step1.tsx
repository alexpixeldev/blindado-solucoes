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
  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-user-tag"
        title="Quem é você?"
        subtitle="Selecione o seu perfil para continuarmos o cadastro."
      />

      <div className="grid grid-cols-1 gap-4 field-container sm:grid-cols-2">
        {(['locador', 'locatario'] as const).map((value) => {
          const isLocadorCard = value === 'locador';
          const active = p.userType === value;
          return (
            <label
              key={value}
              className={`selection-card group relative flex cursor-pointer flex-col overflow-hidden rounded-3xl border-2 bg-white p-6 shadow-soft transition-all duration-300 hover-lift ${
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
              <div className="relative mb-4 flex items-center justify-between">
                <div
                  className={`flex h-12 w-12 items-center justify-center rounded-2xl transition-all duration-300 ${
                    active
                      ? 'bg-brand-gradient text-white shadow-glow'
                      : 'bg-primary-100 text-primary-600 group-hover:bg-primary-200'
                  }`}
                >
                  <i className={`fas ${isLocadorCard ? 'fa-key' : 'fa-user-check'} text-lg`}></i>
                </div>
                <span
                  className={`flex h-6 w-6 items-center justify-center rounded-full border-2 transition-all duration-300 ${
                    active ? 'border-primary-600 bg-primary-600' : 'border-slate-200'
                  }`}
                >
                  <i
                    className={`fas fa-check text-[10px] text-white transition-all duration-300 ${
                      active ? 'scale-100 opacity-100' : 'scale-50 opacity-0'
                    }`}
                  ></i>
                </span>
              </div>
              <span className="relative text-lg font-bold text-slate-900">
                {isLocadorCard ? 'Sou Locador' : 'Sou Hóspede'}
              </span>
              <span className="relative mt-1 text-sm text-slate-500">
                {isLocadorCard ? 'Proprietário ou responsável pelo imóvel.' : 'Inquilino temporário'}
              </span>
            </label>
          );
        })}
      </div>

      <FieldError>{p.errors.user_type}</FieldError>

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
