import type { Inquilino, Veiculo } from '@/lib/types';
import { StepHeader, SectionCard, Button, IconBadge } from '@/components/ui';

interface Props {
  userType: string;
  locadorNome: string;
  locadorDdi: string;
  locadorTelefone: string;
  registradorNome: string;
  edificioNome: string;
  numeroApartamento: string;
  dataEntrada: string;
  dataSaida: string;
  inquilinos: Inquilino[];
  veiculos: Veiculo[];
  submitting: boolean;
  onWhatsApp: () => void;
  onPdf: () => void;
  onBack: () => void;
}

export default function Step6(p: Props) {
  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-clipboard-check"
        title="Revisão dos Dados"
        subtitle="Confira as informações com atenção antes de enviar."
      />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div className="relative overflow-hidden rounded-3xl bg-brand-gradient p-5 text-white shadow-glow sm:p-6">
          <i className="fas fa-building pointer-events-none absolute -right-4 -top-4 text-6xl text-white/10"></i>
          <h4 className="text-[11px] font-bold uppercase tracking-wider text-white/70">Localização</h4>
          <p className="mt-2 break-words text-lg font-bold">{p.edificioNome || 'Não informado'}</p>
          <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-sm">
            <i className="fas fa-door-closed text-xs"></i> Apartamento {p.numeroApartamento}
          </p>
        </div>

        <SectionCard>
          <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Período</h4>
          <p className="mt-2 flex items-center gap-2 break-words text-lg font-bold text-slate-900">
            <i className="fas fa-calendar-day text-primary-500"></i>
            {p.dataEntrada} <span className="text-slate-300">→</span> {p.dataSaida}
          </p>
          <p className="mt-2 inline-flex items-center gap-1.5 text-sm text-primary-600">
            <i className="fas fa-circle-check"></i> Check-in registrado
          </p>
        </SectionCard>
      </div>

      {p.userType === 'locatario' && (
        <SectionCard icon="fas fa-user-tie" title="Locador">
          <p className="break-words text-lg font-bold text-slate-900">{p.locadorNome}</p>
          <p className="mt-1 flex items-center gap-2 text-sm text-slate-600">
            <i className="fab fa-whatsapp text-primary-500"></i>
            {p.locadorDdi} {p.locadorTelefone}
          </p>
        </SectionCard>
      )}

      {p.registradorNome && (
        <SectionCard icon="fas fa-user-pen" title="Locação registrada por">
          <p className="break-words text-lg font-bold text-slate-900">{p.registradorNome}</p>
        </SectionCard>
      )}

      <SectionCard icon="fas fa-users" title="Hóspedes">
        <div className="space-y-3">
          {p.inquilinos
            .filter((i) => i.nome.trim())
            .map((inq, i) => (
              <div key={i} className="flex flex-col border-b border-slate-50 py-2 last:border-0">
                <span className="flex items-center gap-2 text-sm font-medium text-slate-900 sm:text-base">
                  <i className="fas fa-circle-user text-primary-500"></i>
                  {inq.nome}
                </span>
                <span className="pl-6 text-xs text-slate-600 sm:text-sm">Documento: {inq.documento}</span>
                {inq.telefone && (
                  <span className="pl-6 text-xs text-slate-600 sm:text-sm">Telefone: {inq.telefone}</span>
                )}
              </div>
            ))}
        </div>
      </SectionCard>

      {p.veiculos.filter((v) => v.modelo.trim()).length > 0 && (
        <SectionCard icon="fas fa-car" title="Veículos">
          <div className="space-y-3 sm:space-y-4">
            {p.veiculos
              .filter((v) => v.modelo.trim())
              .map((v, i) => (
                <div
                  key={i}
                  className="flex flex-col border-b border-slate-50 py-2 last:border-0 sm:flex-row sm:items-center sm:justify-between"
                >
                  <span className="text-sm font-medium text-slate-900 sm:text-base">
                    {v.modelo} ({v.cor})
                  </span>
                  <span className="mt-1 self-start rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700 sm:mt-0 sm:text-sm">
                    {v.placa}
                  </span>
                  {v.acesso_garagem && (
                    <p className="mt-1 text-xs font-medium italic text-primary-600 sm:ml-3 sm:mt-0">
                      Acesso de garagem: {v.acesso_garagem}
                    </p>
                  )}
                </div>
              ))}
          </div>
        </SectionCard>
      )}

      <div className="space-y-3">
        <Button
          type="button"
          variant="whatsapp"
          size="lg"
          icon="fab fa-whatsapp"
          loading={p.submitting}
          disabled={p.submitting}
          onClick={p.onWhatsApp}
          className="w-full"
        >
          {p.submitting ? 'Enviando...' : 'Enviar via WhatsApp'}
        </Button>

        <Button type="button" variant="dark" size="lg" icon="fas fa-file-pdf" onClick={p.onPdf} className="w-full">
          Gerar PDF para Impressão
        </Button>

        <Button
          type="button"
          variant="secondary"
          size="lg"
          icon="fas fa-arrow-left"
          onClick={p.onBack}
          className="w-full"
        >
          Voltar para editar
        </Button>
      </div>

      <div className="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4 sm:gap-4 sm:p-5">
        <IconBadge icon="fas fa-circle-info" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
        <div className="text-xs leading-relaxed text-primary-800 sm:text-sm">
          Ao clicar em <strong>Enviar via WhatsApp</strong>, seus dados serão salvos em nosso sistema e você
          será redirecionado para o WhatsApp para finalizar o atendimento com nossa equipe.
        </div>
      </div>
    </div>
  );
}
