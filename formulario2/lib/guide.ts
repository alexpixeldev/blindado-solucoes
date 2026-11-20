export interface StepGuide {
  key: string;
  target: string;
  title: string;
  text: string;
}

export const STEP_GUIDES: StepGuide[] = [
  {
    key: 'perfil',
    target: '#guide-perfil',
    title: 'Comece escolhendo seu perfil',
    text: 'Selecione “Sou hóspede” se você vai se hospedar ou “Sou proprietário” se é o responsável pelo apartamento. A opção escolhida define os próximos campos.'
  },
  {
    key: 'edificio',
    target: '#edificio_search',
    title: 'Encontre o edifício',
    text: 'Digite parte do nome do edifício. A lista filtra enquanto você digita e ignora maiúsculas e acentos. Depois clique no edifício e escolha o apartamento na lista.'
  },
  {
    key: 'hospedes',
    target: '#guide-hospedes',
    title: 'Quem vai se hospedar?',
    text: 'Preencha nome e documento do hóspede principal. Se houver mais pessoas, use “Adicionar outro hóspede”. O telefone é opcional.'
  },
  {
    key: 'veiculos',
    target: '#guide-veiculos',
    title: 'Esta etapa é opcional',
    text: 'Cadastre os veículos que ficarão na garagem. Se não tiver veículo, basta clicar em “Próxima” — nada aqui é obrigatório.'
  },
  {
    key: 'periodo',
    target: '#guide-calendario',
    title: 'Escolha as datas',
    text: 'Clique primeiro na data de entrada e depois na data de saída. As duas ficam destacadas no calendário e podem ser alteradas clicando novamente.'
  },
  {
    key: 'revisao',
    target: '#guide-revisao',
    title: 'Confira antes de enviar',
    text: 'Revise os dados e use “Editar” para corrigir qualquer etapa. Depois envie pelo WhatsApp ou gere o PDF para impressão.'
  }
];

export interface GuideState {
  off: boolean;
}

export const GUIDE_STORAGE_KEY = 'f2_guide_v1';

export function readGuideState(): GuideState {
  if (typeof window === 'undefined') return { off: false };
  try {
    const raw = window.localStorage.getItem(GUIDE_STORAGE_KEY);
    if (!raw) return { off: false };
    const parsed = JSON.parse(raw) as Partial<GuideState> | null;
    return { off: parsed?.off === true };
  } catch {
    return { off: false };
  }
}

export function writeGuideState(state: GuideState): void {
  if (typeof window === 'undefined') return;
  try {
    window.localStorage.setItem(GUIDE_STORAGE_KEY, JSON.stringify(state));
  } catch {
    // Armazenamento indisponível (modo privado): o guia apenas reaparece na próxima visita.
  }
}
