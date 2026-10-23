export function digitsOnly(value: string): string {
  return value.replace(/\D/g, '');
}

export function isOnlyZeros(value: string): boolean {
  const digits = digitsOnly(value);
  return digits.length > 0 && /^0+$/.test(digits);
}

export function phoneFormatError(value: string): string | null {
  const digits = digitsOnly(value);
  if (!digits || isOnlyZeros(value)) return 'Informe um telefone válido';
  if (digits.length < 10 || digits.length > 11) {
    return 'Informe o telefone com DDD, por exemplo (27) 99999-0000';
  }
  return null;
}

export function documentFormatError(value: string): string | null {
  const cleaned = value.replace(/[\s.\-/]/g, '');
  if (!cleaned) return null;
  if (/^0+$/.test(cleaned)) return 'Informe um número de documento válido';
  return null;
}

export function parseBrDate(value: string): Date | null {
  const match = value.trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!match) return null;
  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return date;
}
