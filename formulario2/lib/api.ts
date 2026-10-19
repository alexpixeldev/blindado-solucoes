import type { Edificio, Inquilino, Veiculo } from './types';

export const API_BASE = (process.env.NEXT_PUBLIC_API_BASE || '/formulario').replace(/\/+$/, '');
export const BASE_PATH = (process.env.NEXT_PUBLIC_BASE_PATH || '').replace(/\/+$/, '');

export function apiUrl(file: string): string {
  return `${API_BASE}/${file.replace(/^\/+/, '')}`;
}

export function appHref(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${BASE_PATH}${p}`;
}

export function asset(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${BASE_PATH}${p}`;
}

export async function fetchEdificios(): Promise<Edificio[]> {
  const res = await fetch(apiUrl('edificios_data.php'), { cache: 'no-store' });
  if (!res.ok) {
    throw new Error(`Falha ao carregar edifícios (HTTP ${res.status})`);
  }
  const data = (await res.json()) as Edificio[];
  if (!Array.isArray(data)) return [];
  return data;
}

export interface BuildFormArgs {
  userType: string;
  locadorNome: string;
  locadorDdi: string;
  locadorTelefone: string;
  registradorNome: string;
  edificioId: string;
  numeroApartamento: string;
  dataEntrada: string;
  dataSaida: string;
  observacoes: string;
  inquilinos: Inquilino[];
  veiculos: Veiculo[];
}

export function buildFormData(args: BuildFormArgs): FormData {
  const fd = new FormData();
  fd.append('user_type', args.userType);
  fd.append('locador_nome', args.locadorNome);
  fd.append('locador_ddi', args.locadorDdi);
  fd.append('locador_telefone', args.locadorTelefone);
  fd.append('registrador_nome', args.registradorNome);
  fd.append('edificio_id', String(args.edificioId));
  fd.append('numero_apartamento', args.numeroApartamento);
  fd.append('data_entrada', args.dataEntrada);
  fd.append('data_saida', args.dataSaida);
  fd.append('observacoes', args.observacoes);

  args.inquilinos.forEach((inq, i) => {
    if (!inq.nome.trim()) return;
    fd.append(`inquilinos[${i}][nome]`, inq.nome);
    fd.append(`inquilinos[${i}][documento]`, inq.documento);
    fd.append(`inquilinos[${i}][telefone]`, inq.telefone);
    if (inq.selfie) {
      fd.append(`inquilinos[${i}][selfie]`, inq.selfie);
    }
  });

  args.veiculos.forEach((v, i) => {
    if (!v.modelo.trim()) return;
    fd.append(`veiculos[${i}][modelo]`, v.modelo);
    fd.append(`veiculos[${i}][cor]`, v.cor);
    fd.append(`veiculos[${i}][placa]`, v.placa);
    fd.append(`veiculos[${i}][acesso_garagem]`, v.acesso_garagem);
  });

  return fd;
}
