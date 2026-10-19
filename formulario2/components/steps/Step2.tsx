import type { Edificio } from '@/lib/types';
import { StepHeader, TextInput, FieldError } from '@/components/ui';

interface Props {
  search: string;
  setSearch: (v: string) => void;
  edificios: Edificio[];
  edificioId: string;
  numeroApartamento: string;
  openModal: (e: Edificio) => void;
  errors: Record<string, string>;
}

export default function Step2(p: Props) {
  const term = p.search.trim().toLowerCase();
  const filtered = term
    ? p.edificios.filter((e) => e.nome_edificio.toLowerCase().includes(term))
    : p.edificios;

  return (
    <div className="space-y-8">
      <StepHeader
        icon="fas fa-map-location-dot"
        title="Localização"
        subtitle="Selecione o edifício onde ficará hospedado."
      />

      <div className="space-y-4">
        <TextInput
          icon="fas fa-search"
          type="text"
          value={p.search}
          onChange={(e) => p.setSearch(e.target.value)}
          placeholder="Buscar edifício pelo nome..."
        />

        <div className="custom-scrollbar grid max-h-[420px] grid-cols-1 gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
          {filtered.map((edificio) => {
            const selected = String(edificio.id) === String(p.edificioId);
            return (
              <button
                type="button"
                key={edificio.id}
                onClick={() => p.openModal(edificio)}
                className={`edificio-card group relative flex w-full items-start gap-3 overflow-hidden rounded-2xl border-2 p-4 text-left transition-all duration-300 hover-lift ${
                  selected
                    ? 'border-primary-500 bg-primary-50/60 ring-4 ring-primary-500/10'
                    : 'border-slate-100 bg-white hover:border-primary-300'
                }`}
              >
                <div
                  className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-all duration-300 ${
                    selected
                      ? 'bg-brand-gradient text-white shadow-glow'
                      : 'bg-primary-100 text-primary-600 group-hover:bg-primary-200'
                  }`}
                >
                  <i className="fas fa-building"></i>
                </div>
                <div className="min-w-0 flex-1 pt-0.5">
                  <p className="truncate font-semibold text-slate-900">{edificio.nome_edificio}</p>
                  {edificio.localizacao ? (
                    <p className="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                      <i className="fas fa-location-dot text-[10px]"></i>
                      <span className="truncate">{edificio.localizacao}</span>
                    </p>
                  ) : edificio.nome_base ? (
                    <p className="mt-0.5 text-xs text-slate-400">{edificio.nome_base}</p>
                  ) : null}
                  {selected && p.numeroApartamento && (
                    <span className="mt-2 inline-flex items-center gap-1 rounded-full bg-primary-600 px-2.5 py-0.5 text-[11px] font-bold text-white">
                      <i className="fas fa-door-closed text-[10px]"></i> Apto {p.numeroApartamento}
                    </span>
                  )}
                </div>
                <i
                  className={`fas fa-circle-check mt-0.5 shrink-0 text-lg text-primary-500 transition-all duration-300 ${
                    selected ? 'scale-100 opacity-100' : 'scale-50 opacity-0'
                  }`}
                ></i>
              </button>
            );
          })}

          {filtered.length === 0 && (
            <div className="col-span-full flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 py-12 text-center">
              <i className="fas fa-magnifying-glass text-2xl text-slate-300"></i>
              <p className="text-sm font-medium text-slate-500">Nenhum edifício encontrado</p>
              <p className="text-xs text-slate-400">Tente buscar por outro nome.</p>
            </div>
          )}
        </div>

        <FieldError>{p.errors.edificio}</FieldError>
        <FieldError>{p.errors.apartamento}</FieldError>
      </div>
    </div>
  );
}
