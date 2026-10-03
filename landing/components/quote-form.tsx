"use client";

import { useState } from "react";
import { WHATSAPP_NUMERO } from "@/lib/site";

type Erros = Partial<Record<string, string>>;

const ROTULO_PORTEIRO: Record<string, string> = {
  sim_24h: "Sim, 24 horas",
  sim_12h: "Sim, 12 horas",
  nao: "Não possui",
};

function mascaraTelefone(valor: string) {
  const d = valor.replace(/\D/g, "").slice(0, 11);
  if (d.length <= 2) return d;
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

const campo =
  "w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20";

export function QuoteForm() {
  const [enviado, setEnviado] = useState(false);
  const [erros, setErros] = useState<Erros>({});

  function enviar(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();

    const fd = new FormData(e.currentTarget);
    const get = (k: string) => String(fd.get(k) ?? "").trim();

    const dados = {
      nome: get("nome"),
      email: get("email"),
      telefone: get("telefone"),
      condominio: get("condominio"),
      unidades: get("unidades"),
      porteiro: get("porteiro"),
      mensagem: get("mensagem"),
    };

    const novosErros: Erros = {};
    if (!dados.nome) novosErros.nome = "Informe seu nome";
    if (!dados.email) novosErros.email = "Informe seu e-mail";
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(dados.email))
      novosErros.email = "E-mail inválido";
    if (!dados.telefone) novosErros.telefone = "Informe seu telefone";
    else if (dados.telefone.replace(/\D/g, "").length < 10)
      novosErros.telefone = "Telefone incompleto";
    if (!dados.condominio) novosErros.condominio = "Informe o condomínio";
    if (!dados.unidades) novosErros.unidades = "Informe a quantidade";
    if (!dados.porteiro) novosErros.porteiro = "Selecione uma opção";

    setErros(novosErros);
    if (Object.keys(novosErros).length > 0) {
      setEnviado(false);
      return;
    }

    const texto = [
      "*Solicitação de orçamento - Blindado Soluções*",
      "",
      `*Nome:* ${dados.nome}`,
      `*E-mail:* ${dados.email}`,
      `*Telefone:* ${dados.telefone}`,
      `*Condomínio:* ${dados.condominio}`,
      `*Unidades:* ${dados.unidades}`,
      `*Porteiro atual:* ${ROTULO_PORTEIRO[dados.porteiro] ?? dados.porteiro}`,
      dados.mensagem ? `*Observações:* ${dados.mensagem}` : "",
    ]
      .filter(Boolean)
      .join("\n");

    window.open(
      `https://wa.me/${WHATSAPP_NUMERO}?text=${encodeURIComponent(texto)}`,
      "_blank",
      "noopener,noreferrer",
    );

    e.currentTarget.reset();
    setEnviado(true);
  }

  return (
    <div>
      <form
        onSubmit={enviar}
        noValidate
        className="grid gap-5 sm:grid-cols-2"
      >
            <div className="grid gap-5 sm:grid-cols-2">
              <div>
                <label htmlFor="nome" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  Nome completo *
                </label>
                <input
                  id="nome"
                  name="nome"
                  type="text"
                  autoComplete="name"
                  placeholder="Como podemos te chamar"
                  className={campo}
                  aria-invalid={!!erros.nome}
                />
                {erros.nome && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.nome}</p>}
              </div>

              <div>
                <label htmlFor="email" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  E-mail *
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  placeholder="seu@email.com"
                  className={campo}
                  aria-invalid={!!erros.email}
                />
                {erros.email && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.email}</p>}
              </div>

              <div>
                <label htmlFor="telefone" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  Telefone *
                </label>
                <input
                  id="telefone"
                  name="telefone"
                  type="tel"
                  inputMode="numeric"
                  autoComplete="tel"
                  placeholder="(27) 99999-9999"
                  className={campo}
                  onChange={(ev) => {
                    ev.currentTarget.value = mascaraTelefone(ev.currentTarget.value);
                  }}
                  aria-invalid={!!erros.telefone}
                />
                {erros.telefone && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.telefone}</p>}
              </div>

              <div>
                <label htmlFor="condominio" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  Nome do Condomínio *
                </label>
                <input
                  id="condominio"
                  name="condominio"
                  type="text"
                  placeholder="Ex.: Residencial Praia Azul"
                  className={campo}
                  aria-invalid={!!erros.condominio}
                />
                {erros.condominio && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.condominio}</p>}
              </div>

              <div>
                <label htmlFor="unidades" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  Número de unidades *
                </label>
                <input
                  id="unidades"
                  name="unidades"
                  type="number"
                  min={1}
                  placeholder="Ex.: 120"
                  className={campo}
                  aria-invalid={!!erros.unidades}
                />
                {erros.unidades && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.unidades}</p>}
              </div>

              <div>
                <label htmlFor="porteiro" className="mb-1.5 block text-sm font-semibold text-brand-900">
                  Possui porteiro atual? *
                </label>
                <select
                  id="porteiro"
                  name="porteiro"
                  defaultValue=""
                  className={`${campo} appearance-none`}
                  aria-invalid={!!erros.porteiro}
                >
                  <option value="" disabled>
                    Selecione uma opção
                  </option>
                  <option value="sim_24h">Sim, 24 horas</option>
                  <option value="sim_12h">Sim, 12 horas</option>
                  <option value="nao">Não possui</option>
                </select>
                {erros.porteiro && <p className="mt-1.5 text-xs font-semibold text-red-600">{erros.porteiro}</p>}
              </div>
            </div>

            <div className="mt-5">
              <label htmlFor="mensagem" className="mb-1.5 block text-sm font-semibold text-brand-900">
                Observações
              </label>
              <textarea
                id="mensagem"
                name="mensagem"
                rows={4}
                placeholder="Conte-nos mais sobre as necessidades do seu condomínio..."
                className={`${campo} resize-y`}
              />
            </div>

            <button
              type="submit"
              className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-7 py-4 text-base font-bold text-white transition-colors hover:bg-brand-700 sm:col-span-2"
            >
              <i className="fa-brands fa-whatsapp text-lg" aria-hidden />
              Enviar pelo WhatsApp
            </button>

            <p className="text-center text-xs text-slate-500 sm:col-span-2">
              Ao enviar, o WhatsApp abre com os dados já preenchidos para você
              conferir e enviar.
            </p>

            {enviado && (
              <p
                role="status"
                className="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 sm:col-span-2"
              >
                <i className="fa-solid fa-circle-check mt-0.5" aria-hidden />
                Dados prontos! Só apertar enviar no WhatsApp que abriu.
              </p>
            )}
          </form>
    </div>
  );
}
