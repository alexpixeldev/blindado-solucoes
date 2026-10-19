export interface Edificio {
  id: string;
  nome_edificio: string;
  localizacao: string;
  requer_selfie: number | string;
  nome_base: string;
  telefone: string;
}

export interface Inquilino {
  nome: string;
  documento: string;
  telefone: string;
  selfie: File | null;
}

export interface Veiculo {
  modelo: string;
  cor: string;
  placa: string;
  acesso_garagem: string;
}

export type UserType = 'locador' | 'locatario' | '';

export type FieldErrors = Record<string, string | undefined>;

export interface WizardState {
  userType: '' | 'locador' | 'locatario';
  locadorNome: string;
  locadorDdi: string;
  locadorTelefone: string;
  registradorNome: string;
  edificioId: string;
  numeroApartamento: string;
  inquilinos: Inquilino[];
  veiculos: Veiculo[];
  dataEntrada: string;
  dataSaida: string;
  observacoes: string;
}
