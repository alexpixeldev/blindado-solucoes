'use client';

import { useMemo, useState } from 'react';
import type { Edificio } from '@/lib/types';
import { StepHeader, TextInput, Field, FieldError, IconBadge } from '@/components/ui';
import { filterEdificios } from '@/lib/search';

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
  const [showAll, setShowAll] = useState(false);
  const term = p.search.trim();
  const filtered = useMemo(() => filterEdificios(p.edificios, p.search), [p.edificios, p.search]);
  const selected = p.edificios.find((e) => String(e.id) === String(p.edificioId));
  const listVisible = term.length > 0 || showAll;
  const visible = term.length > 0 ? filtered : p.edificios;

  return (
    <div className="space-y-6 sm:space-y-8">
      <StepHeader
        icon="fas fa-building"
        title="Seleção do edifício"
        subtitle="Encontre o edifício onde ficará hospedado e informe o apartamento."
      />

      {selected && (
        <div className="rounded-2xl border border-primary-200 bg-primary-50/70 p-4 sm:p-5">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div className="min-w-0">
              <p className="text-[11px] font-bold uppercase tracking-wider text-primary-700">
                Edifício selecionado
              </p>
              <p className="mt-1 break-words text-base font-bold text-slate-900 sm:text-lg">
                {selected.nome_edificio}
              </p>
              <p className="mt-1 inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-2.5 py-0.5 text-xs font-bold text-white">
                <i className="fas fa-door-closed text-[10px]" aria-hidden="true"></i>
                Apartamento {p.numeroApartamento}
              </p>
            </div>
            <button
              type="button"
              onClick={() => p.openModal(selected)}
              className="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-3 py-2 text-xs font-semibold text-primary-700 shadow-soft transition-colors hover:border-primary-400 hover:bg-primary-50"
            >
              <i className="fas fa-pen text-[11px]" aria-hidden="true"></i>
              Alterar apartamento
            </button>
          </div>
          <p className="mt-2 text-xs text-slate-600">
            Para trocar de edifício, selecione outro na lista abaixo.
          </p>
        </div>
      )}

      <div className="space-y-4">
        <Field
          label="Nome do edifício"
          htmlFor="edificio_search"
          hint="Digite o nome do edifício para ver as opções. A busca ignora maiúsculas e acentos."
        >
          <TextInput
            id="edificio_search"
            icon="fas fa-search"
            type="text"
            autoComplete="off"
            value={p.search}
            onChange={(e) => p.setSearch(e.target.value)}
            placeholder="Digite o nome do edifício"
            aria-controls="lista-edificios"
          />
        </Field>

        <p className="text-xs font-medium text-slate-500" aria-live="polite">
          {term
            ? `${filtered.length} ${filtered.length === 1 ? 'edifício encontrado' : 'edifícios encontrados'}`
            : `${p.edificios.length} edifícios disponíveis`}
        </p>

        {!listVisible && (
          <div className="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-10 text-center">
            <IconBadge icon="fas fa-building-circle-check" tone="soft" className="h-12 w-12" />
            <div>
              <p className="text-sm font-semibold text-slate-700">Comece digitando o nome do edifício</p>
              <p className="mt-1 text-xs text-slate-500">
                A lista será filtrada enquanto você digita, sem precisar apertar Enter.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setShowAll(true)}
              className="mt-1 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-soft transition-colors hover:border-primary-300 hover:text-primary-600"
            >
              <i className="fas fa-list" aria-hidden="true"></i>
              Ver todos os {p.edificios.length} edifícios
            </button>
          </div>
        )}

        {listVisible && (
          <div
            id="lista-edificios"
            className="custom-scrollbar grid max-h-[420px] grid-cols-1 gap-3 overflow-y-auto pr-1 sm:grid-cols-2"
          >
            {visible.map((edificio) => {
              const isSelected = String(edificio.id) === String(p.edificioId);
              return (
                <button
                  type="button"
                  key={edificio.id}
                  onClick={() => p.openModal(edificio)}
                  aria-current={isSelected ? 'true' : undefined}
                  aria-label={`${isSelected ? 'Edifício selecionado' : 'Selecionar'} ${edificio.nome_edificio}`}
                  className={`edificio-card group relative flex w-full items-start gap-3 overflow-hidden rounded-2xl border-2 p-4 text-left transition-all duration-300 hover-lift focus-visible:ring-4 focus-visible:ring-primary-500/20 ${
                    isSelected
                      ? 'border-primary-600 bg-primary-50/60 ring-4 ring-primary-500/10'
                      : 'border-slate-100 bg-white hover:border-primary-300'
                  }`}
                >
                  <div
                    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-all duration-300 ${
                      isSelected
                        ? 'bg-brand-gradient text-white shadow-glow'
                        : 'bg-primary-100 text-primary-600 group-hover:bg-primary-200'
                    }`}
                  >
                    <i className="fas fa-building" aria-hidden="true"></i>
                  </div>
                  <div className="min-w-0 flex-1 pt-0.5">
                    <p className="break-words font-semibold leading-snug text-slate-900">
                      {edificio.nome_edificio}
                    </p>
                    {isSelected && (
                      <span className="mt-2 inline-flex items-center gap-1 rounded-full bg-primary-600 px-2.5 py-0.5 text-[11px] font-bold text-white">
                        <i className="fas fa-check text-[9px]" aria-hidden="true"></i>
                        Selecionado{p.numeroApartamento ? ` · Apto ${p.numeroApartamento}` : ''}
                      </span>
                    )}
                  </div>
                  <i
                    aria-hidden="true"
                    className={`fas fa-circle-check mt-0.5 shrink-0 text-lg text-primary-500 transition-all duration-300 ${
                      isSelected ? 'scale-100 opacity-100' : 'scale-50 opacity-0'
                    }`}
                  ></i>
                </button>
              );
            })}

            {visible.length === 0 && (
              <div className="col-span-full flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-10 text-center">
                <i className="fas fa-magnifying-glass text-2xl text-slate-300" aria-hidden="true"></i>
                <div>
                  <p className="text-sm font-semibold text-slate-600">Nenhum edifício encontrado</p>
                  <p className="mt-1 text-xs text-slate-500">
                    Confira a grafia ou tente buscar por outro nome.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => p.setSearch('')}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-soft transition-colors hover:border-primary-300 hover:text-primary-600"
                >
                  <i className="fas fa-eraser" aria-hidden="true"></i>
                  Limpar busca
                </button>
              </div>
            )}
          </div>
        )}

        <FieldError>{p.errors.edificio}</FieldError>
        <FieldError>{p.errors.apartamento}</FieldError>
      </div>
    </div>
  );
}
