'use client';

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { motion, AnimatePresence, MotionConfig } from 'framer-motion';
import type { Edificio, Inquilino, Veiculo } from '@/lib/types';
import { apiUrl, appHref, asset, buildFormData, fetchEdificios } from '@/lib/api';
import { buildWhatsAppUrl } from '@/lib/whatsapp';
import { documentFormatError, parseBrDate, phoneFormatError } from '@/lib/validation';
import { clearDraft, loadDraft, saveDraft, type DraftState } from '@/lib/draft';
import { readGuideState, STEP_GUIDES, writeGuideState } from '@/lib/guide';
import { Stepper } from '@/components/Stepper';
import { GuideTour } from '@/components/GuideTour';
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

function parseDate(str: string): Date | null {
  return parseBrDate(str);
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
  const modalTriggerRef = useRef<HTMLElement | null>(null);
  const wasModalOpenRef = useRef(false);

  const [inquilinos, setInquilinos] = useState<Inquilino[]>([emptyInquilino()]);
  const [veiculos, setVeiculos] = useState<Veiculo[]>([]);
  const [dataEntrada, setDataEntrada] = useState('');
  const [dataSaida, setDataSaida] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const [guideOpen, setGuideOpen] = useState(false);
  const [guideOff, setGuideOff] = useState(false);
  const guideShownRef = useRef<Set<string>>(new Set());
  const [draftRestored, setDraftRestored] = useState(false);
  const draftReadyRef = useRef(false);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const mq = window.matchMedia('(max-width: 639px)');
    const apply = () => setIsMobile(mq.matches);
    apply();
    mq.addEventListener('change', apply);
    return () => mq.removeEventListener('change', apply);
  }, []);

  useEffect(() => {
    if (wasModalOpenRef.current && !modalOpen) {
      modalTriggerRef.current?.focus?.();
    }
    wasModalOpenRef.current = modalOpen;
  }, [modalOpen]);

  useEffect(() => {
    if (!modalOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setModalOpen(false);
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [modalOpen]);

  useEffect(() => {
    fetchEdificios()
      .then(setEdificios)
      .catch((e: Error) => setLoadError(e.message));
  }, []);

  useEffect(() => {
    const guide = readGuideState();
    setGuideOff(guide.off);

    const draft = loadDraft();
    if (draft) {
      const hasData = Boolean(
        draft.userType ||
          draft.edificioId ||
          draft.locadorNome.trim() ||
          draft.registradorNome.trim() ||
          draft.dataEntrada ||
          draft.dataSaida ||
          draft.inquilinos.some((i) => i.nome.trim() || i.documento.trim() || i.telefone.trim()) ||
          draft.veiculos.some((v) => v.modelo.trim() || v.cor.trim() || v.placa.trim() || v.acesso_garagem.trim())
      );

      if (hasData) {
        setUserType(draft.userType);
        setLocadorNome(draft.locadorNome);
        setLocadorDdi(draft.locadorDdi || '+55');
        setLocadorTelefone(draft.locadorTelefone);
        setRegistradorNome(draft.registradorNome);
        setEdificioId(draft.edificioId);
        setNumeroApartamento(draft.numeroApartamento);
        setDataEntrada(draft.dataEntrada);
        setDataSaida(draft.dataSaida);
        setInquilinos(
          draft.inquilinos.length
            ? draft.inquilinos.map((i) => ({
                nome: i.nome,
                documento: i.documento,
                telefone: i.telefone,
                selfie: null
              }))
            : [emptyInquilino()]
        );
        setVeiculos(draft.veiculos);
        setCurrentStep(Math.min(Math.max(draft.step, 0), 5));
        setDraftRestored(true);
      }
    }

    draftReadyRef.current = true;
  }, []);

  useEffect(() => {
    if (!draftReadyRef.current) return;

    const draft: DraftState = {
      step: currentStep,
      userType,
      locadorNome,
      locadorDdi,
      locadorTelefone,
      registradorNome,
      edificioId,
      numeroApartamento,
      dataEntrada,
      dataSaida,
      inquilinos: inquilinos.map(({ nome, documento, telefone }) => ({ nome, documento, telefone })),
      veiculos
    };

    const hasData = Boolean(
      draft.userType ||
        draft.edificioId ||
        draft.locadorNome.trim() ||
        draft.registradorNome.trim() ||
        draft.dataEntrada ||
        draft.dataSaida ||
        draft.inquilinos.some((i) => i.nome.trim() || i.documento.trim() || i.telefone.trim()) ||
        draft.veiculos.some((v) => v.modelo.trim() || v.cor.trim() || v.placa.trim() || v.acesso_garagem.trim())
    );

    if (hasData) saveDraft(draft);
  }, [
    currentStep,
    userType,
    locadorNome,
    locadorDdi,
    locadorTelefone,
    registradorNome,
    edificioId,
    numeroApartamento,
    dataEntrada,
    dataSaida,
    inquilinos,
    veiculos
  ]);

  const guide = STEP_GUIDES[currentStep];

  useEffect(() => {
    if (!guide || guideOff || modalOpen) return;
    if (guideShownRef.current.has(guide.key)) return;

    const timer = window.setTimeout(() => {
      guideShownRef.current.add(guide.key);
      setGuideOpen(true);
    }, 650);
    return () => window.clearTimeout(timer);
  }, [guide, guideOff, modalOpen]);

  const closeGuide = useCallback(() => setGuideOpen(false), []);

  const dismissGuide = useCallback(() => {
    setGuideOpen(false);
    setGuideOff(true);
    writeGuideState({ off: true });
  }, []);

  const openGuide = useCallback(() => {
    if (guide) guideShownRef.current.add(guide.key);
    setGuideOpen(true);
  }, [guide]);

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
        if (!locadorTelefone.trim()) {
          e.locador_telefone = 'Você precisa inserir o WhatsApp do locador';
        } else {
          const phoneError = phoneFormatError(locadorTelefone);
          if (phoneError) e.locador_telefone = phoneError;
        }
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
        if (!inq.documento.trim()) {
          e[`inq_${idx}_documento`] = 'Você precisa inserir o documento';
        } else {
          const docError = documentFormatError(inq.documento);
          if (docError) e[`inq_${idx}_documento`] = docError;
        }
        if (inq.telefone.trim()) {
          const phoneError = phoneFormatError(inq.telefone);
          if (phoneError) e[`inq_${idx}_telefone`] = phoneError;
        }
      });
    }

    if (step === 3) {
      veiculos.forEach((v, idx) => {
        const hasOtherData = Boolean(v.cor.trim() || v.placa.trim() || v.acesso_garagem.trim());
        if (!v.modelo.trim() && hasOtherData) {
          e[`vei_${idx}_modelo`] = 'Informe o modelo ou remova este veículo';
        }
      });
    }

    if (step === 4) {
      if (!dataEntrada.trim()) e.data_entrada = 'Você precisa informar a data de entrada';
      if (!dataSaida.trim()) {
        e.data_saida = 'Você precisa informar a data de saída';
      } else if (dataEntrada && dataSaida) {
        const entrada = parseDate(dataEntrada);
        const saida = parseDate(dataSaida);
        if (!entrada) e.data_entrada = 'Escolha a data de entrada no calendário';
        else if (!saida) e.data_saida = 'Escolha a data de saída no calendário';
        else if (saida < entrada) e.data_saida = 'A data de saída não pode ser anterior à data de entrada';
      }
    }

    return e;
  }

  function validateAll(): { errors: Record<string, string>; step: number } | null {
    for (let step = 0; step < 5; step += 1) {
      const stepErrors = validateStep(step);
      if (Object.keys(stepErrors).length) return { errors: stepErrors, step };
    }
    return null;
  }

  function focusFirstError(nextErrors: Record<string, string>) {
    const key = Object.keys(nextErrors)[0];
    if (!key) return;

    const targetId =
      key === 'user_type'
        ? 'guide-perfil'
        : key === 'edificio' || key === 'apartamento'
          ? 'edificio_search'
          : key;

    window.setTimeout(() => {
      const el =
        key === 'user_type'
          ? document.querySelector<HTMLInputElement>('input[name="user_type"]')
          : document.getElementById(targetId);
      if (!el) return;
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.focus({ preventScroll: true });
    }, 420);
  }

  function guardAllSteps(): boolean {
    const invalid = validateAll();
    if (!invalid) return true;

    setErrors(invalid.errors);
    setDirection(-1);
    setCurrentStep(invalid.step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    focusFirstError(invalid.errors);
    return false;
  }

  function validateInquilinoField(index: number, field: 'nome' | 'documento' | 'telefone', value: string) {
    let message = '';
    if (field === 'nome' && !value.trim()) {
      message = 'Você precisa inserir o nome';
    } else if (field === 'documento') {
      if (!value.trim()) message = 'Você precisa inserir o documento';
      else message = documentFormatError(value) || '';
    } else if (field === 'telefone' && value.trim()) {
      message = phoneFormatError(value) || '';
    }
    setError(`inq_${index}_${field}`, message);
  }

  function startOver() {
    clearDraft();
    guideShownRef.current.clear();
    setDraftRestored(false);
    setErrors({});
    setDirection(-1);
    setCurrentStep(0);
    setUserType('');
    setLocadorNome('');
    setLocadorDdi('+55');
    setLocadorTelefone('');
    setRegistradorNome('');
    setSearch('');
    setEdificioId('');
    setNumeroApartamento('');
    setInquilinos([emptyInquilino()]);
    setVeiculos([]);
    setDataEntrada('');
    setDataSaida('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
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

  function goToStep(step: number) {
    setErrors({});
    setDirection(step > currentStep ? 1 : -1);
    setCurrentStep(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function skipVehicles() {
    setVeiculos([]);
    setErrors({});
    setDirection(1);
    setCurrentStep(4);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function removeVeiculo(index: number) {
    setVeiculos((prev) => prev.filter((_, i) => i !== index));
    setErrors((prev) => {
      const next = { ...prev };
      Object.keys(next).forEach((key) => {
        if (key.startsWith('vei_')) delete next[key];
      });
      return next;
    });
  }

  function handleKeyDown(e: React.KeyboardEvent<HTMLDivElement>) {
    if (e.key !== 'Enter' || modalOpen) return;
    const tag = (e.target as HTMLElement)?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || tag === 'BUTTON' || tag === 'A') return;
    if (currentStep >= 5) return;
    e.preventDefault();
    goNext();
  }

  function openModal(edificio: Edificio) {
    if (guide) guideShownRef.current.add(guide.key);
    setGuideOpen(false);
    modalTriggerRef.current = document.activeElement as HTMLElement | null;
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
    if (!guardAllSteps()) return;

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
        clearDraft();
        sessionStorage.setItem('whatsapp_locacao', url);
        window.location.href = appHref('/sucesso/');
      } else if (result.status === 'duplicate') {
        const url = buildWhatsAppUrl(fd, edificios);
        clearDraft();
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
    if (!guardAllSteps()) return;

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
          <div className="mx-auto max-w-3xl px-4 py-3 sm:px-6">
            <div className="flex items-center justify-between gap-3">
              <span className="inline-flex shrink-0 items-center rounded-2xl bg-white/95 px-3 py-1.5 shadow-soft">
                <img
                  src={asset('/img/logo_horizontal.png')}
                  alt="Blindado Soluções"
                  width={180}
                  height={40}
                  className="h-7 w-auto max-w-[130px] object-contain sm:h-8 sm:max-w-[160px]"
                />
              </span>
              <div className="flex shrink-0 items-center gap-2">
                <button
                  type="button"
                  onClick={openGuide}
                  aria-label="Abrir guia desta etapa"
                  title="Guia desta etapa"
                  className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/[0.08] text-emerald-50/90 transition-colors hover:bg-white/20 hover:text-white"
                >
                  <i className="fas fa-circle-question text-sm" aria-hidden="true" />
                </button>
                <span
                  className="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-2.5 py-1 text-[10px] font-semibold text-emerald-50/85 sm:px-3 sm:text-[11px]"
                  aria-live="polite"
                >
                  <span className="h-1.5 w-1.5 rounded-full bg-accent-400" aria-hidden="true" />
                  <span className="sm:hidden">
                    {currentStep + 1}/6
                  </span>
                  <span className="hidden sm:inline">Etapa {currentStep + 1} de 6</span>
                </span>
              </div>
            </div>
            <div className="mt-2.5">
              <Stepper current={currentStep} onStepClick={goToStep} />
            </div>
          </div>
        </header>

        <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 pb-12 pt-36 sm:px-6 sm:pt-36">
          <h1 className="sr-only">Formulário de Locação</h1>

          <div className="gradient-border flex flex-1 flex-col overflow-hidden rounded-[2rem] shadow-card">
            <div className="flex flex-1 flex-col bg-white/95 backdrop-blur-xl">
              {loadError && (
                <div className="m-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 animate-fade-in sm:m-6">
                  <i className="fas fa-triangle-exclamation mt-0.5 text-red-500"></i>
                  <div className="text-sm font-medium text-red-700">Erro ao carregar edifícios: {loadError}</div>
                </div>
              )}

              {draftRestored && (
                <div className="mx-5 mt-5 flex flex-col gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 p-4 animate-fade-in sm:mx-6 sm:mt-6 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex items-start gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/80 text-sm text-primary-600 shadow-soft">
                      <i className="fas fa-clock-rotate-left" aria-hidden="true" />
                    </span>
                    <div>
                      <p className="text-sm font-bold text-primary-900">Rascunho restaurado</p>
                      <p className="text-xs leading-relaxed text-primary-800 sm:text-sm">
                        Continuamos de onde você parou neste navegador.
                      </p>
                    </div>
                  </div>
                  <div className="flex shrink-0 gap-2">
                    <button
                      type="button"
                      onClick={() => setDraftRestored(false)}
                      className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-primary-200 bg-white px-3 py-2 text-xs font-semibold text-primary-700 shadow-soft transition-colors hover:border-primary-400 hover:bg-primary-50 sm:flex-none"
                    >
                      <i className="fas fa-play text-[10px]" aria-hidden="true" />
                      Continuar
                    </button>
                    <button
                      type="button"
                      onClick={startOver}
                      className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition-colors hover:bg-white/70 hover:text-slate-800 sm:flex-none"
                    >
                      <i className="fas fa-eraser text-[10px]" aria-hidden="true" />
                      Começar do zero
                    </button>
                  </div>
                </div>
              )}

              <div className="flex flex-1 flex-col p-5 sm:p-8">
                <AnimatePresence mode="wait" custom={direction} initial={false}>
                  <motion.div
                    key={currentStep}
                    custom={direction}
                    variants={stepVariants}
                    initial="enter"
                    animate="center"
                    exit="exit"
                    className="flex flex-1 flex-col"
                  >
                    <motion.div
                      drag="x"
                      dragDirectionLock
                      dragConstraints={{ left: 0, right: 0 }}
                      dragElastic={0.12}
                      className="flex h-full flex-1 flex-col"
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
                          validateField={validateInquilinoField}
                          errors={errors}
                        />
                      )}

                      {currentStep === 3 && (
                        <Step4
                          veiculos={veiculos}
                          isLocador={userType === 'locador'}
                          setVeiculo={(index, field, value) => {
                            setVeiculos((prev) => prev.map((it, i) => (i === index ? { ...it, [field]: value } : it)));
                            setError(`vei_${index}_modelo`, '');
                          }}
                          addVeiculo={() => setVeiculos((prev) => [...prev, emptyVeiculo()])}
                          removeVeiculo={removeVeiculo}
                          onSkip={skipVehicles}
                          errors={errors}
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
                          onEditStep={goToStep}
                        />
                      )}
                    </motion.div>
                  </motion.div>
                </AnimatePresence>
              </div>

              {!isLast && (
                <div className="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-8 sm:py-5">
                  <Button type="button" variant="secondary" onClick={goPrev} disabled={currentStep === 0} icon="fas fa-arrow-left">
                    Anterior
                  </Button>

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
            <div
              className="fixed inset-0 z-[60] flex justify-center"
              style={
                isMobile
                  ? { alignItems: 'flex-start', padding: '1rem', paddingTop: '5.5rem' }
                  : { alignItems: 'center', padding: '1rem' }
              }
            >
              <motion.div
                className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => setModalOpen(false)}
              />
              <motion.div
                className="relative max-h-[70vh] w-full overflow-y-auto overflow-x-hidden rounded-[2rem] bg-white shadow-2xl"
                style={{ maxWidth: '28rem' }}
                initial={{ opacity: 0, y: 40, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 30, scale: 0.98 }}
                transition={{ type: 'spring', stiffness: 260, damping: 26 }}
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-apartamento-titulo"
              >
                <div className="relative flex items-center justify-between overflow-hidden bg-brand-gradient p-4 text-white sm:p-6">
                  <div className="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10" />
                  <h3 id="modal-apartamento-titulo" className="relative flex items-center gap-2 text-base font-bold sm:text-xl">
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

                <div className={isMobile ? 'space-y-4 p-4' : 'space-y-6 p-6 sm:p-8'}>
                  <div className="text-center">
                    <p className="text-xs text-slate-500 sm:text-sm">Informe o número do apartamento para o edifício:</p>
                    <p className="mt-0.5 text-base font-bold text-slate-900 sm:mt-1 sm:text-lg">{modalEdificio?.nome_edificio}</p>
                  </div>

                  <Field label="Número do Apartamento" htmlFor="modal_apt_input" error={modalError}>
                    <TextInput
                      id="modal_apt_input"
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
                      aria-describedby={modalError ? 'modal_apt_input-error' : undefined}
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

        {guide && (
          <GuideTour
            open={guideOpen && !modalOpen}
            guide={guide}
            stepIndex={currentStep}
            total={STEP_GUIDES.length}
            onClose={closeGuide}
            onDismissAll={dismissGuide}
          />
        )}
      </div>
    </MotionConfig>
  );
}
