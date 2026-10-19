import type { Edificio } from './types';

export function formatText(text: string): string {
  if (!text) return '';
  return text
    .toLowerCase()
    .split(' ')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function formDataToObject(fd: FormData): Record<string, string> {
  const out: Record<string, string> = {};
  fd.forEach((value, key) => {
    if (typeof value === 'string') out[key] = value;
  });
  return out;
}

interface Parsed {
  [index: string]: { [field: string]: string | File };
}

function parseGrouped(fd: FormData, group: string): Parsed {
  const result: Parsed = {};
  fd.forEach((value, key) => {
    const match = key.match(new RegExp(`${group}\\[(\\d+)\\]\\[(\\w+)\\]`));
    if (match) {
      const idx = match[1];
      const field = match[2];
      if (!result[idx]) result[idx] = {};
      result[idx][field] = value;
    }
  });
  return result;
}

export function generateWhatsAppMessage(fd: FormData, edificios: Edificio[]): string {
  const data = formDataToObject(fd);
  const edificio = edificios.find((e) => String(e.id) === String(data.edificio_id));

  let msg = `*Ficha de Controle de Locação*\n\n`;
  msg += `*Edifício:* ${edificio ? formatText(edificio.nome_edificio) : 'Não informado'}\n`;
  msg += `*Apartamento:* ${data.numero_apartamento || ''}\n\n`;
  msg += `*Data de Chegada:* ${data.data_entrada || ''}\n`;
  msg += `*Data de Saída:* ${data.data_saida || ''}\n\n`;

  if (edificio && edificio.localizacao) {
    msg += `*Localização do edifício:*\n${edificio.localizacao}\n\n`;
  }

  if (data.user_type === 'locatario') {
    msg += `*Locador:* ${formatText(data.locador_nome || '')}\n`;
    msg += `*WhatsApp Locador:* ${data.locador_ddi || ''} ${data.locador_telefone || ''}\n\n`;
  }

  if (data.registrador_nome) {
    msg += `*Locação registrada por:* ${formatText(data.registrador_nome)}\n\n`;
  }

  msg += `*Inquilinos:*\n`;
  const inquilinos = parseGrouped(fd, 'inquilinos');
  const requiresSelfie = edificio && String(edificio.requer_selfie) === '1';
  Object.keys(inquilinos)
    .sort((a, b) => Number(a) - Number(b))
    .forEach((idx) => {
      const i = inquilinos[idx];
      if (!i || !i.nome) return;
      const nome = String(i.nome);
      const doc = i.documento ? String(i.documento) : '';
      const tel = i.telefone ? String(i.telefone) : '';
      const selfie = i.selfie as File | undefined;
      const telInfo = tel ? `\nTelefone: ${tel}` : '';
      const selfieInfo = requiresSelfie && selfie && selfie.size > 0 ? `\n📷 Cadastro de facial` : '';
      msg += `*${formatText(nome)}*\nDocumento: ${doc}${telInfo}${selfieInfo}\n\n`;
    });

  msg += `*Veículos:*\n`;
  const veiculos = parseGrouped(fd, 'veiculos');
  const validVeiculos = Object.keys(veiculos)
    .sort((a, b) => Number(a) - Number(b))
    .map((k) => veiculos[k])
    .filter((v) => v && v.modelo);

  if (validVeiculos.length === 0) {
    msg += 'Nenhum veículo cadastrado.\n';
  } else {
    validVeiculos.forEach((v) => {
      msg += `${formatText(String(v.modelo))}\n${formatText(String(v.cor || ''))}\nPlaca: ${String(v.placa || '').toUpperCase()}\n`;
      if (v.acesso_garagem) msg += `Acesso de garagem: ${formatText(String(v.acesso_garagem))}\n`;
      msg += `\n`;
    });
  }

  return encodeURIComponent(msg);
}

export function buildWhatsAppUrl(fd: FormData, edificios: Edificio[]): string {
  const msg = generateWhatsAppMessage(fd, edificios);
  const data = formDataToObject(fd);

  let telefone = '';
  if (data.user_type === 'locatario') {
    const ddi = data.locador_ddi ? data.locador_ddi.replace(/\D/g, '') : '55';
    const numero = data.locador_telefone ? data.locador_telefone.replace(/\D/g, '') : '';
    telefone = ddi + numero;
  } else {
    const edificio = edificios.find((e) => String(e.id) === String(data.edificio_id));
    telefone = edificio?.telefone ? edificio.telefone.replace(/\D/g, '') : '';
    if (telefone && !telefone.startsWith('55') && telefone.length <= 11) {
      telefone = '55' + telefone;
    }
  }

  return `https://api.whatsapp.com/send?phone=${telefone}&text=${msg}`;
}
