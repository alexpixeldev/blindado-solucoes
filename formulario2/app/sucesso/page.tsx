'use client';

import { useEffect, useState } from 'react';
import { appHref, asset } from '@/lib/api';

export default function SucessoPage() {
  const [duplicado, setDuplicado] = useState(false);
  const [whatsappUrl, setWhatsappUrl] = useState('');
  const [showBaloon, setShowBaloon] = useState(false);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    const isDup = new URLSearchParams(window.location.search).has('duplicado');
    setDuplicado(isDup);

    if (isDup) {
      const url = sessionStorage.getItem('reativacao_locacao');
      if (url) setShowBaloon(true);
    } else {
      const url = sessionStorage.getItem('whatsapp_locacao');
      if (url) {
        setWhatsappUrl(url);
        sessionStorage.removeItem('whatsapp_locacao');
        window.location.replace(url);
        return;
      }
    }
    setReady(true);
  }, []);

  function finalizarWhatsApp() {
    if (!whatsappUrl) return;
    sessionStorage.removeItem('whatsapp_locacao');
    window.location.href = whatsappUrl;
  }

  function reenviar() {
    const url = sessionStorage.getItem('reativacao_locacao');
    if (!url) return;
    sessionStorage.removeItem('reativacao_locacao');
    window.location.href = url;
  }

  function recusar() {
    sessionStorage.removeItem('reativacao_locacao');
    setShowBaloon(false);
  }

  return (
    <div className="flex min-h-full flex-col px-3 pb-10 pt-5 sm:px-6 sm:pt-10 lg:px-8">
      <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col">
        <header className="mb-7 text-center animate-fade-in sm:mb-9">
          <div className="mb-5 inline-flex items-center justify-center rounded-3xl border border-white/70 bg-white/70 px-5 py-3 shadow-soft backdrop-blur-md">
            <img
              src={asset('/img/logo_horizontal.png')}
              alt="Blindado Soluções"
              width={220}
              height={48}
              className="h-9 w-auto max-w-full object-contain sm:h-12"
            />
          </div>
          <p className="text-sm font-medium uppercase tracking-[0.2em] text-primary-600">Blindado Soluções</p>
        </header>

        <main className="flex-1 animate-slide-up" style={{ animationDelay: '0.15s' }}>
          <div className="overflow-hidden rounded-[2rem] border border-white/70 bg-white/85 shadow-card backdrop-blur-xl">
            <div className="p-6 text-center sm:p-12">
              {!duplicado ? (
                <div className="animate-fade-in">
                  <div className="relative mx-auto mb-6 flex h-24 w-24 items-center justify-center">
                    <span className="absolute inset-0 rounded-full bg-primary-100 animate-ping opacity-60" />
                    <span className="relative flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-700 text-white shadow-glow-lg animate-pop">
                      <i className="fas fa-check text-4xl"></i>
                    </span>
                  </div>
                  <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Parabéns!</h1>
                  <p className="mx-auto mt-2 max-w-md text-base text-slate-600 sm:text-lg">
                    Sua locação foi enviada com sucesso!
                  </p>

                  {ready && whatsappUrl && (
                    <div className="mt-7">
                      <p className="text-sm text-slate-600 sm:text-base">
                        Para finalizar o atendimento, envie os dados pelo WhatsApp:
                      </p>
                      <button
                        type="button"
                        onClick={finalizarWhatsApp}
                        className="mt-4 inline-flex items-center justify-center gap-3 rounded-2xl bg-gradient-to-br from-[#25d366] to-[#128c4b] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#25d366]/30 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[#25d366]/50 active:translate-y-0"
                      >
                        <i className="fab fa-whatsapp text-xl"></i> Finalizar no WhatsApp
                      </button>
                    </div>
                  )}
                </div>
              ) : (
                <div className="animate-fade-in">
                  <div className="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg animate-pop">
                    <i className="fas fa-triangle-exclamation text-4xl"></i>
                  </div>
                  <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Atenção!</h1>
                  <p className="mx-auto mt-2 max-w-lg text-base text-slate-600 sm:text-lg">
                    Estas informações já foram enviadas anteriormente para este apartamento nesta data.
                  </p>
                  <div className="mx-auto mt-6 flex max-w-lg items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left">
                    <i className="fas fa-circle-info mt-0.5 text-amber-500"></i>
                    <div className="text-sm leading-relaxed text-amber-900">
                      <strong>Nada foi registrado novamente.</strong>
                      <span> Se você ainda precisa da informação, escolha reenviar via WhatsApp abaixo.</span>
                    </div>
                  </div>
                </div>
              )}

              <div className="mt-8">
                <a
                  href={appHref('/')}
                  className="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 px-6 py-3.5 text-sm font-bold text-white shadow-glow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-glow-lg active:translate-y-0"
                >
                  <i className="fas fa-rotate-right"></i>
                  Preencher Novo Formulário
                </a>
              </div>
            </div>
          </div>
        </main>

        <footer className="mt-8 text-center text-xs text-slate-400 animate-fade-in sm:text-sm" style={{ animationDelay: '0.3s' }}>
          <p>© {new Date().getFullYear()} Blindado Soluções. Todos os direitos reservados.</p>
        </footer>
      </div>

      {duplicado && ready && showBaloon && (
        <div className="baloon baloon-anim">
          <div className="p-5">
            <div className="mb-3 flex items-center gap-2.5 text-[15px] font-bold text-[#075e54]">
              <i className="fab fa-whatsapp text-2xl text-[#25d366]"></i>
              Reenviar via WhatsApp
            </div>
            <p className="mb-4 text-sm leading-relaxed text-slate-600">
              Deseja reenviar as informações preenchidas pelo WhatsApp?
            </p>
            <div className="flex gap-2.5">
              <button
                type="button"
                onClick={reenviar}
                className="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#25d366] px-4 py-3 text-[13px] font-bold text-white transition-colors hover:bg-[#1ebe5b]"
              >
                <i className="fab fa-whatsapp"></i> Sim, reenviar
              </button>
              <button
                type="button"
                onClick={recusar}
                className="cursor-pointer rounded-xl bg-slate-100 px-4 py-3 text-[13px] font-semibold text-slate-600 transition-colors hover:bg-slate-200"
              >
                Não
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
