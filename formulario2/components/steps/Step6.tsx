import type { Inquilino, Veiculo } from '@/lib/types';
import { Button, IconBadge, SectionCard, StepHeader } from '@/components/ui';

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
  onEditStep: (step: number) => void;
}

export default function Step6(p: Props) {
  const isLocatario = p.userType === 'locatario';
  const inquilinos = p.inquilinos.filter((i) => i.nome.trim());
  const veiculos = p.veiculos.filter((v) => v.modelo.trim());

  const EditButton = ({ step, label }: { step: number; label: string }) => (
    <button
      type="button"
      onClick={() => p.onEditStep(step)}
      aria-label={`Editar ${label}`}
      className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 transition-colors hover:border-primary-300 hover:text-primary-600"
    >
      <i className="fas fa-pen text-[10px]" aria-hidden="true"></i>
      Editar
    </button>
  );

  return (
    <div className="space-y-6 sm:space-y-7">
      <StepHeader
        icon="fas fa-clipboard-check"
        title="Revisão dos dados"
        subtitle="Confira tudo antes de enviar. Use Editar para corrigir qualquer etapa."
      />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div className="relative overflow-hidden rounded-3xl bg-brand-gradient p-5 text-white shadow-glow sm:p-6">
          <i className="fas fa-building pointer-events-none absolute -right-4 -top-4 text-6xl text-white/10"></i>
          <div className="relative flex items-start justify-between gap-3">
            <h4 className="text-[11px] font-bold uppercase tracking-wider text-white/70">Edifício</h4>
            <button
              type="button"
              onClick={() => p.onEditStep(1)}
              aria-label="Editar edifício e apartamento"
              className="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-white/15 px-2.5 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-white/25"
            >
              <i className="fas fa-pen text-[10px]" aria-hidden="true"></i>
              Editar
            </button>
          </div>
          <p className="relative mt-2 break-words text-lg font-bold">{p.edificioNome || 'Não informado'}</p>
          <p className="relative mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-sm">
            <i className="fas fa-door-closed text-xs" aria-hidden="true"></i>
            Apartamento {p.numeroApartamento || 'não informado'}
          </p>
        </div>

        <SectionCard
          action={<EditButton step={4} label="período da estadia" />}
          title="Período informado"
          icon="fas fa-calendar-day"
        >
          <dl className="space-y-2 text-sm">
            <div className="flex items-center justify-between gap-3">
              <dt className="flex items-center gap-2 text-slate-500">
                <i className="fas fa-plane-arrival text-primary-500" aria-hidden="true"></i>
                Entrada
              </dt>
              <dd className="font-bold text-slate-900">{p.dataEntrada || 'Não informada'}</dd>
            </div>
            <div className="flex items-center justify-between gap-3">
              <dt className="flex items-center gap-2 text-slate-500">
                <i className="fas fa-plane-departure text-primary-500" aria-hidden="true"></i>
                Saída
              </dt>
              <dd className="font-bold text-slate-900">{p.dataSaida || 'Não informada'}</dd>
            </div>
          </dl>
        </SectionCard>
      </div>

      <SectionCard
        icon="fas fa-user-tag"
        title="Perfil e responsável"
        action={<EditButton step={0} label="perfil e responsável" />}
      >
        <p className="text-lg font-bold text-slate-900">{isLocatario ? 'Hóspede' : 'Locador'}</p>
        {isLocatario ? (
          <>
            <p className="mt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Dados do locador
            </p>
            <p className="mt-1 break-words text-base font-semibold text-slate-900">{p.locadorNome || 'Não informado'}</p>
            <p className="mt-1 flex items-center gap-2 text-sm text-slate-600">
              <i className="fab fa-whatsapp text-primary-500" aria-hidden="true"></i>
              {p.locadorDdi} {p.locadorTelefone}
            </p>
          </>
        ) : (
          <>
            <p className="mt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Quem está preenchendo
            </p>
            <p className="mt-1 break-words text-base font-semibold text-slate-900">{p.registradorNome || 'Não informado'}</p>
          </>
        )}
      </SectionCard>

      <SectionCard
        icon="fas fa-users"
        title="Hóspedes"
        action={<EditButton step={2} label="hóspedes" />}
      >
        {inquilinos.length === 0 ? (
          <p className="text-sm text-slate-500">Nenhum hóspede informado.</p>
        ) : (
          <div className="space-y-3">
            {inquilinos.map((inq, i) => (
              <div key={i} className="border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                <span className="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900 sm:text-base">
                  <i className="fas fa-circle-user text-primary-500" aria-hidden="true"></i>
                  {inq.nome}
                  <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                    {i === 0 ? 'Principal' : 'Adicional'}
                  </span>
                </span>
                <span className="mt-1 block pl-6 text-xs text-slate-600 sm:text-sm">
                  Documento: {inq.documento}
                </span>
                {inq.telefone && (
                  <span className="block pl-6 text-xs text-slate-600 sm:text-sm">
                    Telefone: {inq.telefone}
                  </span>
                )}
              </div>
            ))}
          </div>
        )}
      </SectionCard>

      <SectionCard
        icon="fas fa-car"
        title="Veículos"
        action={<EditButton step={3} label="veículos" />}
      >
        {veiculos.length === 0 ? (
          <p className="text-sm text-slate-500">Nenhum veículo informado. Esta etapa é opcional.</p>
        ) : (
          <div className="space-y-3 sm:space-y-4">
            {veiculos.map((v, i) => (
              <div
                key={i}
                className="flex flex-col border-b border-slate-50 py-1 last:border-0 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-2"
              >
                <span className="text-sm font-medium text-slate-900 sm:text-base">
                  {v.modelo}
                  {v.cor ? ` (${v.cor})` : ''}
                </span>
                {v.placa && (
                  <span className="mt-1 self-start rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700 sm:mt-0 sm:text-sm">
                    {v.placa}
                  </span>
                )}
                {v.acesso_garagem && (
                  <p className="mt-1 text-xs font-medium italic text-primary-600 sm:mt-0">
                    Acesso de garagem: {v.acesso_garagem}
                  </p>
                )}
              </div>
            ))}
          </div>
        )}
      </SectionCard>

      <div className="space-y-3">
        <div className="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4 sm:gap-4 sm:p-5">
          <IconBadge icon="fas fa-circle-info" tone="soft" className="h-9 w-9 rounded-xl text-sm" />
          <div className="text-xs leading-relaxed text-primary-800 sm:text-sm">
            Ao clicar em enviar via whatsapp, os dados serão enviados via WhatsApp para a pessoa o
            responsável pelo registro
          </div>
        </div>

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
          Gerar PDF para impressão
        </Button>
        <p className="px-1 text-center text-xs leading-relaxed text-slate-500">
          O PDF é gerado para conferência e impressão, e os dados também são salvos no sistema.
        </p>

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
    </div>
  );
}
