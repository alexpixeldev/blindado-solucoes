'use client';

import { useRef, useState } from 'react';
import { asset } from '@/lib/api';
import { fileToDataUrl, validateSelfie } from '@/lib/selfie';

interface Props {
  index: number;
  value: File | null;
  nome: string;
  onChange: (file: File | null) => void;
  withExample?: boolean;
}

export function SelfieField({ index, nome, onChange, withExample }: Props) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [preview, setPreview] = useState('');
  const [fileName, setFileName] = useState('');
  const [issues, setIssues] = useState<string[] | null>(null);
  const [checking, setChecking] = useState(false);
  const [approved, setApproved] = useState(false);

  function pick(method: 'camera' | 'file') {
    const el = inputRef.current;
    if (!el) return;
    if (method === 'camera') {
      el.setAttribute('capture', 'user');
    } else {
      el.removeAttribute('capture');
    }
    el.click();
  }

  async function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    const el = e.target;
    const file = el.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
      setPreview('');
      setFileName('');
      setIssues(null);
      setApproved(false);
      onChange(null);
      return;
    }

    const url = await fileToDataUrl(file);
    setPreview(url);
    setFileName(file.name);
    setApproved(false);
    setChecking(true);
    setIssues(null);
    onChange(file);

    const result = await validateSelfie(url);
    setChecking(false);
    setIssues(result);
    if (result.length > 0) {
      onChange(null);
      el.value = '';
      setPreview('');
      setApproved(false);
    } else {
      setApproved(true);
    }
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap gap-2 sm:gap-3">
        <button
          type="button"
          onClick={() => pick('camera')}
          className="selfie-method-button inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:bg-primary-50/50 hover:text-primary-700 sm:flex-none sm:px-4 sm:text-sm"
        >
          <i className="fas fa-camera text-primary-500"></i>
          Tirar foto agora
        </button>
        <button
          type="button"
          onClick={() => pick('file')}
          className="selfie-method-button inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:bg-primary-50/50 hover:text-primary-700 sm:flex-none sm:px-4 sm:text-sm"
        >
          <i className="fas fa-upload text-primary-500"></i>
          Enviar do aparelho
        </button>
      </div>

      <input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={handleChange} />

      {preview && issues === null && !approved && (
        <div className="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
          <img
            src={preview}
            alt="Prévia da selfie"
            loading="lazy"
            decoding="async"
            className="h-44 w-full object-cover"
          />
          {checking && (
            <div className="absolute inset-0 flex items-center justify-center bg-slate-900/40 backdrop-blur-[2px]">
              <span className="inline-flex items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-xs font-semibold text-slate-700">
                <i className="fas fa-circle-notch animate-spin text-primary-600"></i> Validando foto...
              </span>
            </div>
          )}
        </div>
      )}

      {withExample && (
        <div className="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-[220px_1fr]">
          <img
            src={asset('/img/facial.png')}
            alt="Exemplo de selfie"
            loading="lazy"
            decoding="async"
            width={220}
            height={293}
            className="mx-auto h-auto w-40 rounded-3xl border border-slate-200 bg-slate-50 object-cover sm:mx-0 sm:w-full sm:max-w-[220px]"
          />
          <div className="flex flex-col justify-center rounded-3xl border border-primary-200 bg-primary-50 p-4">
            <p className="text-sm font-semibold text-primary-900">
              <i className="fas fa-circle-check mr-1.5"></i>Como deve ser a selfie:
            </p>
            <ul className="mt-3 space-y-2 text-xs text-slate-700 sm:text-sm">
              <li className="flex items-center gap-2">
                <i className="fas fa-check text-primary-500"></i> Rosto inteiro e centralizado
              </li>
              <li className="flex items-center gap-2">
                <i className="fas fa-check text-primary-500"></i> Local bem iluminado
              </li>
              <li className="flex items-center gap-2">
                <i className="fas fa-check text-primary-500"></i> Olhar fixo na câmera
              </li>
              <li className="flex items-center gap-2">
                <i className="fas fa-check text-primary-500"></i> Sem óculos escuros, boné ou máscara
              </li>
            </ul>
            <p className="mt-3 text-xs text-slate-600 sm:text-sm">Use o exemplo ao lado: estilo foto 3x4.</p>
          </div>
        </div>
      )}

      {checking && !preview && (
        <p className="flex items-center gap-2 text-xs text-slate-500">
          <i className="fas fa-circle-notch animate-spin"></i> Validando foto...
        </p>
      )}

      {fileName && !approved && <p className="text-xs text-slate-500">Arquivo selecionado: {fileName}</p>}

      {approved && (
        <div className="flex items-center gap-3 rounded-2xl border border-primary-200 bg-primary-50 p-3 animate-pop">
          <div className="relative h-14 w-14 shrink-0 overflow-hidden rounded-2xl border-2 border-primary-500">
            {preview ? (
              <img src={preview} alt="Prévia da selfie" className="h-full w-full object-cover" />
            ) : (
              <div className="flex h-full w-full items-center justify-center bg-primary-100 text-primary-600">
                <i className="fas fa-user"></i>
              </div>
            )}
            <span className="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-[9px] text-white ring-2 ring-white">
              <i className="fas fa-check"></i>
            </span>
          </div>
          <div>
            <p className="text-sm font-semibold text-slate-900">
              <i className="fas fa-circle-check mr-1.5 text-primary-600"></i>Selfie aprovada
            </p>
            <p className="text-xs text-slate-500">{nome || 'Hóspede'}</p>
          </div>
        </div>
      )}

      {issues && issues.length > 0 && (
        <div className="selfie-validation rounded-2xl border border-red-200 bg-red-50 p-4 animate-fade-in">
          <div className="flex items-center gap-2 text-red-700">
            <i className="fas fa-triangle-exclamation text-lg"></i>
            <span className="text-sm font-bold sm:text-base">A foto não atende aos requisitos</span>
          </div>
          <ul className="mt-3 space-y-2 text-sm">
            {issues.map((issue) => (
              <li key={issue} className="flex items-start gap-2 font-medium text-red-700">
                <i className="fas fa-circle-xmark mt-0.5 text-red-500"></i>
                <span>{issue}</span>
              </li>
            ))}
          </ul>
          <div className="mt-3 border-t border-red-200 pt-3">
            <p className="text-xs italic text-red-600">
              Por favor, tire uma nova foto seguindo as orientações acima.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
