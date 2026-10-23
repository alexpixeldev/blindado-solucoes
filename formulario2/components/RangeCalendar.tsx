'use client';

import { useEffect, useMemo, useRef, useState } from 'react';
import { parseBrDate } from '@/lib/validation';

interface RangeCalendarProps {
  entrada: string;
  saida: string;
  onChange: (entrada: string, saida: string) => void;
  describedBy?: string;
}

const MONTHS = [
  'janeiro',
  'fevereiro',
  'março',
  'abril',
  'maio',
  'junho',
  'julho',
  'agosto',
  'setembro',
  'outubro',
  'novembro',
  'dezembro'
];

const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

function startOfDay(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addDays(date: Date, amount: number): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + amount);
}

function addMonths(date: Date, amount: number): Date {
  return new Date(date.getFullYear(), date.getMonth() + amount, 1);
}

function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function sameMonth(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
}

function before(a: Date, b: Date): boolean {
  return a.getTime() < b.getTime();
}

function formatBr(date: Date): string {
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
}

function keyOf(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function longDate(date: Date): string {
  return `${date.getDate()} de ${MONTHS[date.getMonth()]} de ${date.getFullYear()}`;
}

function monthGrid(month: Date): Date[] {
  const first = new Date(month.getFullYear(), month.getMonth(), 1);
  const start = addDays(first, -first.getDay());
  return Array.from({ length: 42 }, (_, index) => addDays(start, index));
}

export default function RangeCalendar({ entrada, saida, onChange, describedBy }: RangeCalendarProps) {
  const today = useMemo(() => startOfDay(new Date()), []);
  const entradaDate = parseBrDate(entrada);
  const saidaDate = parseBrDate(saida);
  const [baseMonth, setBaseMonth] = useState<Date>(() => startOfMonth(entradaDate ?? today));
  const [hoverDate, setHoverDate] = useState<Date | null>(null);
  const [focusDate, setFocusDate] = useState<Date>(() => startOfDay(entradaDate ?? today));
  const [live, setLive] = useState('');
  const pendingFocus = useRef<Date | null>(null);
  const dayRefs = useRef(new Map<string, HTMLButtonElement>());
  const previousEntrada = useRef(entrada);

  useEffect(() => {
    if (previousEntrada.current === entrada) return;
    previousEntrada.current = entrada;
    const date = parseBrDate(entrada);
    if (!date) return;
    setBaseMonth(startOfMonth(date));
    setFocusDate(date);
  }, [entrada]);

  useEffect(() => {
    const pending = pendingFocus.current;
    if (!pending) return;
    pendingFocus.current = null;
    dayRefs.current.get(keyOf(pending))?.focus();
  }, [baseMonth]);

  const months = [baseMonth, addMonths(baseMonth, 1)];
  const minMonth = startOfMonth(today);
  const canGoPrevious = baseMonth.getTime() > minMonth.getTime();
  const previewEnd =
    entradaDate && !saidaDate && hoverDate && !before(hoverDate, entradaDate) ? hoverDate : null;
  const rangeEnd = saidaDate ?? previewEnd;

  function selectDay(day: Date) {
    if (before(day, today)) return;
    setFocusDate(day);
    if (!entradaDate || (entradaDate && saidaDate) || before(day, entradaDate)) {
      onChange(formatBr(day), '');
      setLive(`Entrada em ${longDate(day)}. Selecione a data de saída.`);
      return;
    }
    onChange(entrada, formatBr(day));
    setLive(`Período selecionado: de ${longDate(entradaDate)} a ${longDate(day)}.`);
  }

  function moveFocus(day: Date, amount: number) {
    const next = addDays(day, amount);
    if (before(next, today)) return;
    setFocusDate(next);
    const visible = months.some((month) => sameMonth(month, next));
    if (!visible) {
      pendingFocus.current = next;
      setBaseMonth(startOfMonth(next));
      return;
    }
    requestAnimationFrame(() => dayRefs.current.get(keyOf(next))?.focus());
  }

  function handleKeyDown(event: React.KeyboardEvent<HTMLButtonElement>, day: Date) {
    const amounts: Record<string, number> = {
      ArrowLeft: -1,
      ArrowRight: 1,
      ArrowUp: -7,
      ArrowDown: 7
    };
    const amount = amounts[event.key];
    if (!amount) return;
    event.preventDefault();
    moveFocus(day, amount);
  }

  function goToMonth(next: Date) {
    const month = startOfMonth(next);
    if (month.getTime() < minMonth.getTime()) return;
    setBaseMonth(month);
    setHoverDate(null);
    setFocusDate(sameMonth(month, today) ? today : month);
  }

  function pickToday() {
    onChange(formatBr(today), '');
    setBaseMonth(startOfMonth(today));
    setFocusDate(today);
    setHoverDate(null);
    setLive(`Entrada em ${longDate(today)}. Selecione a data de saída.`);
  }

  function pickWeek() {
    const end = addDays(today, 7);
    onChange(formatBr(today), formatBr(end));
    setBaseMonth(startOfMonth(today));
    setFocusDate(end);
    setHoverDate(null);
    setLive(`Período selecionado: de ${longDate(today)} a ${longDate(end)}.`);
  }

  function clearDates() {
    onChange('', '');
    setBaseMonth(startOfMonth(today));
    setFocusDate(today);
    setHoverDate(null);
    setLive('Datas removidas. Selecione a data de entrada.');
  }

  let status = 'Selecione a data de entrada';
  if (entradaDate && !saidaDate) status = 'Agora selecione a data de saída';
  if (entradaDate && saidaDate) status = 'Período selecionado';

  function renderMonth(month: Date, secondary = false) {
    const days = monthGrid(month);
    return (
      <div key={keyOf(month)} className={secondary ? 'hidden md:block' : undefined}>
        <div className="grid grid-cols-7 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400 sm:text-[11px]">
          {WEEKDAYS.map((weekday) => (
            <span key={weekday} aria-hidden="true">
              {weekday}
            </span>
          ))}
        </div>
        <div className="mt-2 grid grid-cols-7 gap-y-1">
          {days.map((day) => {
            if (day.getMonth() !== month.getMonth()) {
              return <span key={keyOf(day)} aria-hidden="true" />;
            }
            const disabled = before(day, today);
            const isStart = !!entradaDate && sameDay(day, entradaDate);
            const isEnd = !!saidaDate && sameDay(day, saidaDate);
            const isPreviewEnd = !saidaDate && !!previewEnd && sameDay(day, previewEnd);
            const edgeLabel = isStart ? 'Entrada' : isEnd ? 'Saída' : '';
            const isInRange =
              !!entradaDate &&
              !!rangeEnd &&
              day.getTime() > entradaDate.getTime() &&
              day.getTime() < rangeEnd.getTime();
            const isToday = sameDay(day, today);
            const isEdge = isStart || isEnd || isPreviewEnd;

            const classes = [
              'relative flex h-11 w-full items-center justify-center rounded-lg text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1 sm:h-12',
              disabled
                ? 'cursor-not-allowed text-slate-300'
                : isEdge
                  ? 'bg-primary-600 text-white shadow-sm hover:bg-primary-700'
                  : isInRange
                    ? 'bg-primary-50 text-primary-800 hover:bg-primary-100'
                    : isToday
                      ? 'text-primary-700 ring-1 ring-inset ring-primary-300 hover:bg-primary-50'
                      : 'text-slate-700 hover:bg-slate-100'
            ].join(' ');

            const label = [
              longDate(day),
              isStart ? 'entrada' : '',
              isEnd ? 'saída' : '',
              isToday ? 'hoje' : ''
            ]
              .filter(Boolean)
              .join(', ');

            return (
              <button
                key={keyOf(day)}
                ref={(element) => {
                  if (element) dayRefs.current.set(keyOf(day), element);
                  else dayRefs.current.delete(keyOf(day));
                }}
                type="button"
                data-day={keyOf(day)}
                disabled={disabled}
                tabIndex={sameDay(day, focusDate) ? 0 : -1}
                aria-label={label}
                aria-pressed={isStart || isEnd}
                aria-current={isToday ? 'date' : undefined}
                onClick={() => selectDay(day)}
                onFocus={() => setFocusDate(day)}
                onMouseEnter={() => setHoverDate(day)}
                onKeyDown={(event) => handleKeyDown(event, day)}
                className={classes}
              >
                {edgeLabel ? (
                  <span className="flex flex-col items-center justify-center gap-0.5">
                    <span className="text-[7px] font-semibold leading-none tracking-tight sm:text-[9px]">
                      {edgeLabel}
                    </span>
                    <span className="text-sm font-semibold leading-none">{day.getDate()}</span>
                  </span>
                ) : (
                  day.getDate()
                )}
              </button>
            );
          })}
        </div>
      </div>
    );
  }

  return (
    <section
      id="periodo-calendario"
      aria-label="Calendário para escolher as datas de entrada e saída"
      aria-describedby={describedBy}
      className="rounded-3xl border border-slate-200 bg-white p-4 shadow-soft sm:p-6"
    >
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => goToMonth(addMonths(baseMonth, -1))}
          disabled={!canGoPrevious}
          aria-label="Mês anterior"
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition-colors hover:border-primary-300 hover:text-primary-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500"
        >
          <i className="fas fa-chevron-left text-xs" aria-hidden="true"></i>
        </button>
        <div className="grid flex-1 grid-cols-1 md:grid-cols-2">
          {months.map((month, index) => (
            <p
              key={keyOf(month)}
              className={`text-center text-sm font-bold capitalize text-slate-900 ${
                index === 1 ? 'hidden md:block' : ''
              }`}
            >
              {MONTHS[month.getMonth()]} de {month.getFullYear()}
            </p>
          ))}
        </div>
        <button
          type="button"
          onClick={() => goToMonth(addMonths(baseMonth, 1))}
          aria-label="Próximo mês"
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition-colors hover:border-primary-300 hover:text-primary-700"
        >
          <i className="fas fa-chevron-right text-xs" aria-hidden="true"></i>
        </button>
      </div>

      <div
        className="mt-4 grid animate-fade-in gap-6 md:grid-cols-2"
        onMouseLeave={() => setHoverDate(null)}
      >
        {renderMonth(months[0])}
        {renderMonth(months[1], true)}
      </div>

      <div className="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm font-semibold text-slate-700">{status}</p>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={pickToday}
            className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:border-primary-300 hover:text-primary-700"
          >
            Hoje
          </button>
          <button
            type="button"
            onClick={pickWeek}
            className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:border-primary-300 hover:text-primary-700"
          >
            Próxima semana
          </button>
          {entrada || saida ? (
            <button
              type="button"
              onClick={clearDates}
              className="rounded-lg border border-transparent px-3 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
            >
              Limpar
            </button>
          ) : null}
        </div>
      </div>

      <p role="status" aria-live="polite" className="sr-only">
        {live}
      </p>
    </section>
  );
}
