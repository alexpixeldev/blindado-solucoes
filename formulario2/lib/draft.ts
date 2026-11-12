import type { Veiculo } from './types';

const DRAFT_KEY = 'f2_draft_v1';

export interface DraftInquilino {
  nome: string;
  documento: string;
  telefone: string;
}

export interface DraftState {
  step: number;
  userType: string;
  locadorNome: string;
  locadorDdi: string;
  locadorTelefone: string;
  registradorNome: string;
  edificioId: string;
  numeroApartamento: string;
  dataEntrada: string;
  dataSaida: string;
  inquilinos: DraftInquilino[];
  veiculos: Veiculo[];
}

function str(value: unknown): string {
  return typeof value === 'string' ? value : '';
}

function cleanVeiculo(value: unknown): Veiculo {
  const v = (value ?? {}) as Partial<Veiculo>;
  return {
    modelo: str(v.modelo),
    cor: str(v.cor),
    placa: str(v.placa),
    acesso_garagem: str(v.acesso_garagem)
  };
}

export function loadDraft(): DraftState | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.sessionStorage.getItem(DRAFT_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Partial<DraftState> | null;
    if (!parsed || typeof parsed !== 'object') return null;

    const inquilinos = Array.isArray(parsed.inquilinos)
      ? parsed.inquilinos.map((item) => {
          const i = (item ?? {}) as Partial<DraftInquilino>;
          return { nome: str(i.nome), documento: str(i.documento), telefone: str(i.telefone) };
        })
      : [];

    const veiculos = Array.isArray(parsed.veiculos) ? parsed.veiculos.map(cleanVeiculo) : [];

    return {
      step: typeof parsed.step === 'number' && Number.isFinite(parsed.step) ? parsed.step : 0,
      userType: str(parsed.userType),
      locadorNome: str(parsed.locadorNome),
      locadorDdi: str(parsed.locadorDdi) || '+55',
      locadorTelefone: str(parsed.locadorTelefone),
      registradorNome: str(parsed.registradorNome),
      edificioId: str(parsed.edificioId),
      numeroApartamento: str(parsed.numeroApartamento),
      dataEntrada: str(parsed.dataEntrada),
      dataSaida: str(parsed.dataSaida),
      inquilinos,
      veiculos
    };
  } catch {
    return null;
  }
}

export function saveDraft(draft: DraftState): void {
  if (typeof window === 'undefined') return;
  try {
    window.sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
  } catch {
    // Sem armazenamento: segue sem rascunho.
  }
}

export function clearDraft(): void {
  if (typeof window === 'undefined') return;
  try {
    window.sessionStorage.removeItem(DRAFT_KEY);
  } catch {
    // Ignora.
  }
}
