import type { Edificio } from './types';

export function normalizeSearch(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function matchScore(name: string, term: string): number {
  const normalizedName = normalizeSearch(name);
  if (normalizedName === term) return 3;
  if (normalizedName.startsWith(term)) return 2;
  if (normalizedName.includes(term)) return 1;
  return 0;
}

export function filterEdificios(edificios: Edificio[], search: string): Edificio[] {
  const term = normalizeSearch(search);
  if (!term) return [];

  return edificios
    .map((edificio, index) => ({ edificio, index, score: matchScore(edificio.nome_edificio, term) }))
    .filter((item) => item.score > 0)
    .sort((a, b) => b.score - a.score || a.index - b.index)
    .map((item) => item.edificio);
}
