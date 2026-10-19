'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { motion, AnimatePresence, MotionConfig } from 'framer-motion';
import type { Edificio, Inquilino, Veiculo } from '@/lib/types';
import { apiUrl, appHref, asset, buildFormData, fetchEdificios } from '@/lib/api';
import { buildWhatsAppUrl } from '@/lib/whatsapp';
import { Stepper } from '@/components/Stepper';
import { Button, Field, TextInput } from '@/components/ui';
import Step1 from '@/components/steps/Step1';
import Step2 from '@/components/steps/Step2';
import Step3 from '@/components/steps/Step3';
import Step4 from '@/components/steps/Step4';
import Step5 from '@/components/steps/Step5';
import Step6 from '@/components/steps/Step6';

const emptyInquilino = (): Inquilino => ({ nome: '', documento: '', telefone: '', selfie: null });
const emptyVeiculo = (): Veiculo => ({ modelo: '', cor: '', placa: '', acesso_garagem: '' });

const stepVariants = {
  enter: (dir: number) => ({ opacity: 0, x: dir > 0 ? 64 : -64 }),
  center: {
    opacity: 1,
    x: 0,
    transition: { duration: 0.42, ease: [0.22, 1, 0.36, 1] as const }
  },
  exit: (dir: number) => ({
    opacity: 0,
    x: dir > 0 ? -64 : 64,
    transition: { duration: 0.26, ease: [0.4, 0, 1, 1] as const }
  })
};

function parseDate(str: string): Date {
  const parts = str.split('/');
  return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0]));
}

export default function Wizard() {
  const [currentStep, setCurrentStep] = useState(0);
  const [direction, setDirection] = useState(1);
  const [edificios, setEdificios] = useState<Edificio[]>([]);
  const [loadError, setLoadError] = useState('');

  const [userType, setUserType] = useState('');
  const [locadorNome, setLocadorNome] = useState('');
  const [locadorDdi, setLocadorDdi] = useState('+55');
  const [locadorTelefone, setLocadorTelefone] = useState('');
  const [registradorNome, setRegistradorNome] = useState('');

  const [search, setSearch] = useState('');
  const [edificioId, setEdificioId] = useState('');
  const [numeroApartamento, setNumeroApartamento] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [modalEdificio, setModalEdificio] = useState<Edificio | null>(null);
  const [modalApt, setModalApt] = useState('');
  const [modalError, setModalError] = useState('');

  const [inquilinos, setInquilinos] = useState<Inquilino[]>([emptyInquilino()]);
  const [veiculos, setVeiculos] = useState<Veiculo[]>([emptyVeiculo()]);
  const [dataEntrada, setDataEntrada] = useState('');
  const [dataSaida, setDataSaida] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    fetchEdificios()
      .then(setEdificios)
      .catch((e: Error) => setLoadError(e.message));
  }, []);

  const selectedEdificio = useMemo(
    () => edificios.find((e) => String(e.id) === String(edificioId)),
    [edificios, edificioId]
  );
  const requiresSelfie = selectedEdificio ? String(selectedEdificio.requer_selfie) === '1' : false;

  const setError = useCallback((key: string, value: string) => {
    setErrors((prev) => {
      const next = { ...prev };
      if (value) next[key] = value;
      else delete next[key];
      return next;
    });
  }, []);

  const clearAllErrors = useCallback(() => setErrors({}), []);

  function validateStep(step: number): Record<string, string> {
    const e: Record<string, string> = {};

    if (step === 0) {
      if (!userType) e.user_type = 'Você precisa selecionar uma opção';
      if (userType === 'locatario') {
        if (!locadorNome.trim()) e.locador_nome = 'Você precisa inserir o nome do locador';
        if (!locadorTelefone.trim()) e.locador_telefone = 'Você precisa inserir o WhatsApp do locador';
      } else if (userType === 'locador') {
        if (!registradorNome.trim()) e.registrador_nome = 'Você precisa informar o seu nome';
      }
    }

    if (step === 1) {
      if (!edificioId) e.edificio = 'Você precisa selecionar um edifício';
      else if (!numeroApartamento.trim()) e.apartamento = 'Você precisa informar o número do apartamento';
    }

    if (step === 2) {
      inquilinos.forEach((inq, idx) => {
        if (!inq.nome.trim()) e[`inq_${idx}_nome`] = 'Você precisa inserir o nome';
        if (!inq.documento.trim()) e[`inq_${idx}_documento`] = 'Você precisa inserir o documento';
      });
    }

    if (step === 4) {
      if (!dataEntrada.trim()) e.data_entrada = 'Você precisa informar a data de entrada';
      if (!dataSaida.trim()) e.data_saida = 'Você precisa informar a data de saída';
      else if (dataEntrada && dataSaida) {
        const entrada = parseDate(dataEntrada);
        const saida = parseDate(dataSaida);
        if (saida < entrada) e.data_saida = 'A data de saída não pode ser anterior à data de entrada';
      }
    }

    return e;
  }

  function goNext() {
    const e = validateStep(currentStep);
    if (Object.keys(e).length) {
      setErrors(e);
      return;
    }
    setErrors({});
    if (currentStep < 5) {
      setDirection(1);
      setCurrentStep((s) => s + 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function goPrev() {
    setErrors({});
    if (currentStep > 0) {
      setDirection(-1);
      setCurrentStep((s) => s - 1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function handleKeyDown(e: React.KeyboardEvent<HTMLDivElement>) {
    if (e.key !== 'Enter' || modalOpen) return;
    const tag = (e.target as HTMLElement)?.tagName;
    if (tag === 'BUTTON' || tag === 'TEXTAREA' || tag === 'A') return;
    if (currentStep >= 5) return;
    e.preventDefault();
    goNext();
  }

  function openModal(edificio: Edificio) {
    setModalEdificio(edificio);
    setModalApt(String(edificio.id) === String(edificioId) ? numeroApartamento : '');
    setModalError('');
    setModalOpen(true);
  }

  function confirmApartment() {
    if (!modalApt.trim()) {
      setModalError('Informe o número do apartamento');
      return;
    }
    const apt = modalApt.trim();
    setNumeroApartamento(apt);
    setEdificioId(String(modalEdificio?.id || ''));
    setModalError('');
    setModalOpen(false);
    setErrors((prev) => {
      const next = { ...prev };
      delete next.edificio;
      delete next.apartamento;
      return next;
    });
    setTimeout(() => {
      if (currentStep === 1) {
        setDirection(1);
        setCurrentStep(2);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }, 250);
  }

  function buildArgs(): Parameters<typeof buildFormData>[0] {
    return {
      userType,
      locadorNome,
      locadorDdi,
      locadorTelefone,
      registradorNome,
      edificioId,
      numeroApartamento,
      dataEntrada,
      dataSaida,
      observacoes: '',
      inquilinos,
      veiculos
    };
  }

  async function submitForm() {
    setSubmitting(true);
    try {
      const fd = buildFormData(buildArgs());
      const res = await fetch(apiUrl('salvar_locacao.php'), {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const result = await res.json().catch(() => {
        throw new Error('Resposta inválida do servidor. Tente novamente.');
      });

      if (result.status === 'success') {
        const url = buildWhatsAppUrl(fd, edificios);
        sessionStorage.setItem('whatsapp_locacao', url);
        window.location.href = appHref('/sucesso/');
      } else if (result.status === 'duplicate') {
        const url = buildWhatsAppUrl(fd, edificios);
        sessionStorage.setItem('reativacao_locacao', url);
        window.location.href = appHref('/sucesso/') + '?duplicado=1';
      } else {
        throw new Error(result.message || 'Erro ao salvar');
      }
    } catch (err) {
      alert((err as Error)?.message || 'Erro ao salvar os dados. Tente novamente.');
      setSubmitting(false);
    }
  }

  function gerarPdf() {
    const fd = buildFormData(buildArgs());
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = apiUrl('gerar_pdf.php');
    form.target = '_blank';
    form.style.display = 'none';
    fd.forEach((value, key) => {
      if (typeof value === 'string') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  const isLast = currentStep === 5;

  return (
    <MotionConfig reducedMotion="user">
      <div className="flex min-h-screen flex-col" onKeyDown={handleKeyDown}>
        <header className="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-[#03211a]/70 backdrop-blur-xl">
          <div className="mx-auto flex max-w-3xl items-center gap-4 px-4 py-3 sm:px-6">
            <span className="inline-flex shrink-0 items-center rounded-2xl bg-white/95 px-3 py-1.5 shadow-soft">
              <img
                src={asset('/img/logo_horizontal.png')}
                alt="Blindado Soluções"
                width={180}
                height={40}
                className="h-7 w-auto max-w-[130px] object-contain sm:h-8 sm:max-w-[160px]"
              />
            </span>
            <div className="min-w-0 flex-1">
              <Stepper current={currentStep} />
            </div>
          </div>
        </header>

        <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center px-4 pb-12 pt-24 sm:px-6 sm:pt-28">
          <h1 className="sr-only">Formulário de Locação</h1>

          <div className="gradient-border overflow-hidden rounded-[2rem] shadow-card">
            <div className="bg-white/95 backdrop-blur-xl">
              {loadError && (
                <div className="m-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 animate-fade-in sm:m-6">
                  <i className="fas fa-triangle-exclamation mt-0.5 text-red-500"></i>
                  <div className="text-sm font-medium text-red-700">Erro ao carregar edifícios: {loadError}</div>
                </div>
              )}

              <div className="p-5 sm:p-8">
                <AnimatePresence mode="wait" custom={direction} initial={false}>
                  <motion.div
                    key={currentStep}
                    custom={direction}
                    variants={stepVariants}
                    initial="enter"
                    animate="center"
                    exit="exit"
                  >
                    <motion.div
                      drag="x"
                      dragDirectionLock
                      dragConstraints={{ left: 0, right: 0 }}
                      dragElastic={0.12}
                      onDragEnd={(_, info) => {
                        if (info.offset.x < -80) goNext();
                        else if (info.offset.x > 80) goPrev();
                      }}
                    >
                      {currentStep === 0 && (
                        <Step1
                          userType={userType}
                          setUserType={(v) => {
                            setUserType(v);
                            clearAllErrors();
                          }}
                          locadorNome={locadorNome}
                          setLocadorNome={(v) => {
                            setLocadorNome(v);
                            setError('locador_nome', '');
                          }}
                          locadorDdi={locadorDdi}
                          setLocadorDdi={setLocadorDdi}
                          locadorTelefone={locadorTelefone}
                          setLocadorTelefone={(v) => {
                            setLocadorTelefone(v);
                            setError('locador_telefone', '');
                          }}
                          registradorNome={registradorNome}
                          setRegistradorNome={(v) => {
                            setRegistradorNome(v);
                            setError('registrador_nome', '');
                          }}
                          errors={errors}
                        />
                      )}

                      {currentStep === 1 && (
                        <Step2
                          search={search}
                          setSearch={setSearch}
                          edificios={edificios}
                          edificioId={edificioId}
                          numeroApartamento={numeroApartamento}
                          openModal={openModal}
                          errors={errors}
                        />
                      )}

                      {currentStep === 2 && (
                        <Step3
                          inquilinos={inquilinos}
                          requiresSelfie={requiresSelfie}
                          setField={(index, field, value) => {
                            setInquilinos((prev) => prev.map((it, i) => (i === index ? { ...it, [field]: value } : it)));
                            setError(`inq_${index}_${field}`, '');
                          }}
                          setSelfie={(index, file) => {
                            setInquilinos((prev) => prev.map((it, i) => (i === index ? { ...it, selfie: file } : it)));
                          }}
                          addInquilino={() => setInquilinos((prev) => [...prev, emptyInquilino()])}
                          removeInquilino={(index) => setInquilinos((prev) => prev.filter((_, i) => i !== index))}
                          errors={errors}
                        />
                      )}

                      {currentStep === 3 && (
                        <Step4
                          veiculos={veiculos}
                          isLocador={userType === 'locador'}
                          setVeiculo={(index, field, value) => {
                            setVeiculos((prev) => prev.map((it, i) => (i === index ? { ...it, [field]: value } : it)));
                          }}
                          addVeiculo={() => setVeiculos((prev) => [...prev, emptyVeiculo()])}
                          removeVeiculo={(index) => setVeiculos((prev) => prev.filter((_, i) => i !== index))}
                        />
                      )}

                      {currentStep === 4 && (
                        <Step5
                          dataEntrada={dataEntrada}
                          setDataEntrada={(v) => {
                            setDataEntrada(v);
                            setError('data_entrada', '');
                          }}
                          dataSaida={dataSaida}
                          setDataSaida={(v) => {
                            setDataSaida(v);
                            setError('data_saida', '');
                          }}
                          errors={errors}
                        />
                      )}

                      {currentStep === 5 && (
                        <Step6
                          userType={userType}
                          locadorNome={locadorNome}
                          locadorDdi={locadorDdi}
                          locadorTelefone={locadorTelefone}
                          registradorNome={registradorNome}
                          edificioNome={selectedEdificio?.nome_edificio || 'Não informado'}
                          numeroApartamento={numeroApartamento}
                          dataEntrada={dataEntrada}
                          dataSaida={dataSaida}
                          inquilinos={inquilinos}
                          veiculos={veiculos}
                          submitting={submitting}
                          onWhatsApp={submitForm}
                          onPdf={gerarPdf}
                          onBack={goPrev}
                        />
                      )}
                    </motion.div>
                  </motion.div>
                </AnimatePresence>
              </div>

              {!isLast && (
                <div className="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-8 sm:py-5">
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={goPrev}
                    disabled={currentStep === 0}
                    icon="fas fa-arrow-left"
                  >
                    Anterior
                  </Button>

                  <span className="hidden text-xs font-medium text-slate-400 sm:block">
                    Pressione <kbd className="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px]">Enter</kbd> para avançar
                  </span>

                  <Button type="button" variant="primary" onClick={goNext} iconRight="fas fa-arrow-right">
                    Próxima
                  </Button>
                </div>
              )}
            </div>
          </div>

          <footer className="mt-8 text-center text-xs text-emerald-100/60 animate-fade-in sm:text-sm">
            <p>© {new Date().getFullYear()} Blindado Soluções. Todos os direitos reservados.</p>
          </footer>
        </main>

        <AnimatePresence>
          {modalOpen && (
            <div className="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4">
              <motion.div
                className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => setModalOpen(false)}
              />
              <motion.div
                className="relative w-full max-w-md overflow-hidden rounded-t-[2rem] bg-white shadow-2xl sm:rounded-[2rem]"
                initial={{ opacity: 0, y: 40, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 30, scale: 0.98 }}
                transition={{ type: 'spring', stiffness: 260, damping: 26 }}
              >
                <div className="relative flex items-center justify-between overflow-hidden bg-brand-gradient p-5 text-white sm:p-6">
                  <div className="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10" />
                  <h3 className="relative flex items-center gap-2 text-lg font-bold sm:text-xl">
                    <i className="fas fa-door-open"></i>
                    Número do Apartamento
                  </h3>
                  <button
                    type="button"
                    onClick={() => setModalOpen(false)}
                    aria-label="Fechar"
                    className="relative flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white/90 transition-colors hover:bg-white/25 hover:text-white"
                  >
                    <i className="fas fa-times"></i>
                  </button>
                </div>

                <div className="space-y-6 p-6 sm:p-8">
                  <div className="text-center">
                    <p className="text-sm text-slate-500">Informe o número do apartamento para o edifício:</p>
                    <p className="mt-1 text-lg font-bold text-slate-900">{modalEdificio?.nome_edificio}</p>
                  </div>

                  <Field label="Número do Apartamento" error={modalError}>
                    <TextInput
                      icon="fas fa-hashtag"
                      type="text"
                      value={modalApt}
                      onChange={(e) => {
                        setModalApt(e.target.value);
                        setModalError('');
                      }}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter') confirmApartment();
                      }}
                      placeholder="Ex: 101, 202-A"
                      error={!!modalError}
                      autoFocus
                    />
                  </Field>

                  <Button
                    type="button"
                    variant="primary"
                    size="lg"
                    icon="fas fa-check"
                    onClick={confirmApartment}
                    className="w-full"
                  >
                    Confirmar apartamento
                  </Button>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    </MotionConfig>
  );
}
