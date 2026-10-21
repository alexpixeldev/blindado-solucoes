import { detectFacesBlazeface } from './facedetect';

declare global {
  interface Window {
    tracking?: any;
  }
}

export interface SelfieCheck {
  ok: boolean;
  issues: string[];
  faceBox: { x: number; y: number; w: number; h: number } | null;
  faceBoxes?: Array<{ x: number; y: number; w: number; h: number }>;
  method: 'blazeface' | 'tracking' | 'eyes' | 'skin' | 'none';
}

export function fileToDataUrl(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result));
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

export function isTrackingLoaded(): boolean {
  const t = (window as unknown as { tracking?: any }).tracking;
  return typeof t !== 'undefined' && typeof t.ObjectTracker !== 'undefined';
}

interface Box {
  x: number;
  y: number;
  w: number;
  h: number;
  area: number;
}

interface SkinRegion {
  box: Box;
  pixels: number;
}

/* ------------------------------------------------------------------ */
/* Utilidades de imagem                                                 */
/* ------------------------------------------------------------------ */

const MAX_DIM = 480;

function isSkin(r: number, g: number, b: number): boolean {
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const y = 0.299 * r + 0.587 * g + 0.114 * b;
  const cr = (r - y) * 0.713 + 128;
  const cb = (b - y) * 0.564 + 128;

  // Pele em YCrCb, com faixa estrita para nao confundir madeira/bege.
  const ycc =
    y > 80 &&
    y < 245 &&
    cr >= 135 &&
    cr <= 172 &&
    cb >= 88 &&
    cb <= 132 &&
    r > g &&
    r > b;

  // Regra RGB classica de pele.
  const rgb =
    r > 95 &&
    g > 40 &&
    b > 20 &&
    max - min > 15 &&
    Math.abs(r - g) > 12 &&
    r > g &&
    r > b;

  return ycc || rgb;
}

function brightness(r: number, g: number, b: number): number {
  return (r + g + b) / 3;
}

/**
 * Estatísticas de uma região retangular.
 *
 * Besides brightness (mean/std) we also calculate the "skin hue" metrics
 * (YCbCr Cr and RGB saturation). They are what separates a real dark-skinned
 * face (high Cr, high saturation) from a photo that is simply too dark or
 * desaturated (low Cr, low saturation) - brightness alone cannot tell them
 * apart.
 */
function regionStats(
  data: Uint8ClampedArray,
  width: number,
  height: number,
  box: Box
): { mean: number; std: number; cr: number; sat: number; r: number; g: number; b: number } {
  const x0 = Math.max(0, Math.floor(box.x));
  const y0 = Math.max(0, Math.floor(box.y));
  const x1 = Math.min(width, Math.ceil(box.x + box.w));
  const y1 = Math.min(height, Math.ceil(box.y + box.h));
  let sum = 0;
  let sumSq = 0;
  let sumR = 0;
  let sumG = 0;
  let sumB = 0;
  let sumCr = 0;
  let sumSat = 0;
  let n = 0;
  for (let y = y0; y < y1; y++) {
    for (let x = x0; x < x1; x++) {
      const i = (y * width + x) * 4;
      const r = data[i];
      const g = data[i + 1];
      const b = data[i + 2];
      const v = brightness(r, g, b);
      sum += v;
      sumSq += v * v;
      sumR += r;
      sumG += g;
      sumB += b;
      sumCr += 128 + 0.5 * (r - b);
      sumSat += Math.max(r, g, b) - Math.min(r, g, b);
      n++;
    }
  }
  if (!n) return { mean: 0, std: 0, cr: 0, sat: 0, r: 0, g: 0, b: 0 };
  const mean = sum / n;
  return {
    mean,
    std: Math.sqrt(Math.max(0, sumSq / n - mean * mean)),
    cr: sumCr / n,
    sat: sumSat / n,
    r: sumR / n,
    g: sumG / n,
    b: sumB / n,
  };
}

/** Nitidez: variância do Laplaciano. Valores baixos = imagem borrada. */
function laplacianVariance(data: Uint8ClampedArray, width: number, height: number): number {
  const gray = new Float32Array(width * height);
  for (let i = 0, p = 0; i < data.length; i += 4, p++) {
    gray[p] = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
  }
  let sum = 0;
  let sumSq = 0;
  let n = 0;
  for (let y = 1; y < height - 1; y++) {
    for (let x = 1; x < width - 1; x++) {
      const i = y * width + x;
      const v = 4 * gray[i] - gray[i - 1] - gray[i + 1] - gray[i - width] - gray[i + width];
      sum += v;
      sumSq += v * v;
      n++;
    }
  }
  if (!n) return 0;
  const mean = sum / n;
  return sumSq / n - mean * mean;
}

/* ------------------------------------------------------------------ */
/* Detecção de pele com componentes conectados                          */
/* ------------------------------------------------------------------ */

function findSkinRegions(
  data: Uint8ClampedArray,
  width: number,
  height: number
): SkinRegion[] {
  const total = width * height;
  const mask = new Uint8Array(total);
  for (let p = 0; p < total; p++) {
    const i = p * 4;
    if (isSkin(data[i], data[i + 1], data[i + 2])) mask[p] = 1;
  }

  const visited = new Uint8Array(total);
  const regions: SkinRegion[] = [];
  const queue = new Int32Array(total);

  for (let start = 0; start < total; start++) {
    if (!mask[start] || visited[start]) continue;
    let head = 0;
    let tail = 0;
    queue[tail++] = start;
    visited[start] = 1;

    let count = 0;
    let minX = width;
    let minY = height;
    let maxX = 0;
    let maxY = 0;

    while (head < tail) {
      const p = queue[head++];
      const x = p % width;
      const y = (p - x) / width;
      count++;
      if (x < minX) minX = x;
      if (x > maxX) maxX = x;
      if (y < minY) minY = y;
      if (y > maxY) maxY = y;

      if (x > 0 && mask[p - 1] && !visited[p - 1]) {
        visited[p - 1] = 1;
        queue[tail++] = p - 1;
      }
      if (x < width - 1 && mask[p + 1] && !visited[p + 1]) {
        visited[p + 1] = 1;
        queue[tail++] = p + 1;
      }
      if (y > 0 && mask[p - width] && !visited[p - width]) {
        visited[p - width] = 1;
        queue[tail++] = p - width;
      }
      if (y < height - 1 && mask[p + width] && !visited[p + width]) {
        visited[p + width] = 1;
        queue[tail++] = p + width;
      }
    }

    const w = maxX - minX + 1;
    const h = maxY - minY + 1;
    regions.push({
      box: { x: minX, y: minY, w, h, area: w * h },
      pixels: count,
    });
  }

  regions.sort((a, b) => b.pixels - a.pixels);
  return regions;
}

/* ------------------------------------------------------------------ */
/* Detecção de rosto via tracking.js (local, mesmo origin)             */
/* ------------------------------------------------------------------ */

/** Interseção sobre a união de dois retângulos. */
function iou(a: Box, b: Box): number {
  const x1 = Math.max(a.x, b.x);
  const y1 = Math.max(a.y, b.y);
  const x2 = Math.min(a.x + a.w, b.x + b.w);
  const y2 = Math.min(a.y + a.h, b.y + b.h);
  if (x2 <= x1 || y2 <= y1) return 0;
  const inter = (x2 - x1) * (y2 - y1);
  const uni = a.area + b.area - inter;
  return uni > 0 ? inter / uni : 0;
}

/**
 * O tracking.js emite um evento por quadro, repetindo o mesmo rosto.
 * Mantemos apenas as caixas distintas (IoU baixo) para contar pessoas.
 */
function dedupeBoxes(boxes: Box[]): Box[] {
  const out: Box[] = [];
  for (const b of boxes) {
    const dup = out.some((o) => iou(o, b) > 0.25);
    if (!dup) out.push(b);
  }
  return out;
}

function detectFacesWithTracking(
  canvas: HTMLCanvasElement
): Promise<Box[]> {
  return new Promise((resolve) => {
    const tracking = window.tracking;
    if (!tracking || typeof tracking.ObjectTracker === 'undefined') {
      resolve([]);
      return;
    }
    let tracker: any;
    try {
      tracker = new tracking.ObjectTracker('face');
      tracker.setInitialScale(4);
      tracker.setStepSize(2);
      tracker.setEdgesDensity(0.1);
    } catch {
      resolve([]);
      return;
    }

    const found: Box[] = [];
    let done = false;

    const finish = () => {
      if (done) return;
      done = true;
      try {
        tracking.stop();
      } catch {
        /* ignore */
      }
      resolve(dedupeBoxes(found));
    };

    tracker.on('track', (event: { data: Array<{ width: number; height: number; x: number; y: number }> }) => {
      for (const f of event.data) {
        found.push({ x: f.x, y: f.y, w: f.width, h: f.height, area: f.width * f.height });
      }
    });

    try {
      tracking.track('#selfie-canvas', tracker);
    } catch {
      finish();
      return;
    }

    setTimeout(finish, 3000);
  });
}

/* ------------------------------------------------------------------ */
/* Detecção de olhos (pares) - base para localizar rostos              */
/* ------------------------------------------------------------------ */
interface DarkBlob {
  x: number;
  y: number;
  w: number;
  h: number;
  pixels: number;
}

/** Blobs escuros pequenos (olhos) na imagem. */
function findDarkBlobs(
  data: Uint8ClampedArray,
  width: number,
  height: number,
  threshold: number
): DarkBlob[] {
  const total = width * height;
  const imgArea = total;
  const mask = new Uint8Array(total);
  for (let p = 0; p < total; p++) {
    const i = p * 4;
    const b = brightness(data[i], data[i + 1], data[i + 2]);
    if (b < threshold) mask[p] = 1;
  }

  const visited = new Uint8Array(total);
  const queue = new Int32Array(total);
  const out: DarkBlob[] = [];

  for (let start = 0; start < total; start++) {
    if (!mask[start] || visited[start]) continue;
    let head = 0;
    let tail = 0;
    queue[tail++] = start;
    visited[start] = 1;

    let count = 0;
    let minX = width;
    let minY = height;
    let maxX = 0;
    let maxY = 0;

    while (head < tail) {
      const p = queue[head++];
      const x = p % width;
      const y = (p - x) / width;
      count++;
      if (x < minX) minX = x;
      if (x > maxX) maxX = x;
      if (y < minY) minY = y;
      if (y > maxY) maxY = y;

      if (x > 0 && mask[p - 1] && !visited[p - 1]) {
        visited[p - 1] = 1;
        queue[tail++] = p - 1;
      }
      if (x < width - 1 && mask[p + 1] && !visited[p + 1]) {
        visited[p + 1] = 1;
        queue[tail++] = p + 1;
      }
      if (y > 0 && mask[p - width] && !visited[p - width]) {
        visited[p - width] = 1;
        queue[tail++] = p - width;
      }
      if (y < height - 1 && mask[p + width] && !visited[p + width]) {
        visited[p + width] = 1;
        queue[tail++] = p + width;
      }
    }

    const w = maxX - minX + 1;
    const h = maxY - minY + 1;
    // O olho e pequeno: rejeita blobs grandes (cabelo, sombra) e ruido.
    if (count > imgArea * 0.0004 && count < imgArea * 0.012 && w < width * 0.35 && h < height * 0.2) {
      out.push({ x: minX, y: minY, w, h, pixels: count });
    }
  }

  return out;
}

/**
 * Agrupa os blobs escuros em pares de olhos (mesma linha, tamanho parecido).
 * Cada par indica um rosto presente na foto.
 */
function findEyePairs(blobs: DarkBlob[], width: number, height: number): DarkBlob[][] {
  const pairs: DarkBlob[][] = [];
  const used = new Set<number>();

  for (let i = 0; i < blobs.length; i++) {
    if (used.has(i)) continue;
    for (let j = i + 1; j < blobs.length; j++) {
      if (used.has(j)) continue;
      const a = blobs[i];
      const b = blobs[j];

      const ax = a.x + a.w / 2;
      const ay = a.y + a.h / 2;
      const bx = b.x + b.w / 2;
      const by = b.y + b.h / 2;

      const dy = Math.abs(ay - by) / height;
      if (dy > 0.045) continue;

      const dx = Math.abs(ax - bx) / width;
      if (dx < 0.045 || dx > 0.42) continue;

      const ratio = a.pixels / Math.max(1, b.pixels);
      if (ratio < 0.35 || ratio > 2.8) continue;

      pairs.push([a, b]);
      used.add(i);
      used.add(j);
      break;
    }
  }

  return pairs;
}

/** Estimativa da area do rosto a partir do par de olhos. */
function faceBoxFromEyes(pair: DarkBlob[]): Box {
  const [a, b] = pair;
  const ax = a.x + a.w / 2;
  const bx = b.x + b.w / 2;
  const eyeY = (a.y + a.h / 2 + b.y + b.h / 2) / 2;
  const dist = Math.abs(ax - bx);
  const cx = (ax + bx) / 2;
  const cy = eyeY + dist * 0.95;
  const fw = dist * 2.6;
  const fh = dist * 3.2;
  return {
    x: cx - fw / 2,
    y: cy - fh / 2,
    w: fw,
    h: fh,
    area: fw * fh,
  };
}

/* ------------------------------------------------------------------ */
/* Validação principal                                                  */
/* ------------------------------------------------------------------ */

export async function validateSelfie(src: string): Promise<SelfieCheck> {
  type Issue = { prio: number; msg: string };
  const found: Issue[] = [];
  const add = (prio: number, msg: string) => found.push({ prio, msg });

  const img = document.createElement('img');
  await new Promise<void>((resolve) => {
    img.onload = () => resolve();
    img.onerror = () => resolve();
    img.src = src;
  });

  if (!img.width || !img.height) {
    return { ok: true, issues: [], faceBox: null, method: 'none' };
  }

  const originalW = img.width;
  const originalH = img.height;

  const canvas = document.createElement('canvas');
  canvas.id = 'selfie-canvas';
  canvas.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0;pointer-events:none;';
  const scale = Math.min(1, MAX_DIM / Math.max(originalW, originalH));
  canvas.width = Math.round(originalW * scale);
  canvas.height = Math.round(originalH * scale);
  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  if (!ctx) {
    canvas.remove();
    return { ok: true, issues: [], faceBox: null, method: 'none' };
  }

  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
  const w = canvas.width;
  const h = canvas.height;
  const imgArea = w * h;

  /* --- qualidade geral da imagem --- */
  const whole = regionStats(data, w, h, { x: 0, y: 0, w, h, area: imgArea });
  const sharp = laplacianVariance(data, w, h);

  if (originalW < 320 || originalH < 320) {
    add(5, 'A foto tem resolução baixa. Tire a foto com a câmera do aparelho, sem zoom digital.');
  }
  /* Limiares pensados para nao penalizar pele escura: so foto realmente
     mal iluminada e reprovada. */
  if (whole.mean < 32) {
    add(4, 'A foto está escura demais. Tire a foto em um local bem iluminado, virado para a luz.');
  } else if (whole.mean > 236) {
    add(4, 'A foto está com luz estourada. Evite luz direto na câmera.');
  }
  if (sharp < 18) {
    add(4, 'A foto está desfocada. Segure o aparelho firme e espere o foco na tela antes de tirar a foto.');
  }

  /* --- proporção da imagem (retrato 3x4) --- */
  const ratio = originalW / originalH;
  const formatoRuim = ratio > 0.95 || ratio < 0.5;
  if (ratio > 0.95) {
    add(2, 'A foto precisa ser no formato retrato (3x4), com a pessoa em pé. Vire o aparelho na vertical.');
  } else if (ratio < 0.5) {
    add(2, 'A foto está muito estreita. Use o formato retrato 3x4, com o rosto centralizado.');
  }

  /* Formato inválido: não adianta detectar rosto, a foto já está fora
     do padrão. Retorna na hora, sem esperar a detecção. */
  if (formatoRuim) {
    canvas.remove();
    const issues = found
      .sort((a, b) => a.prio - b.prio)
      .filter((i, idx, arr) => arr.findIndex((o) => o.msg === i.msg) === idx)
      .slice(0, 3)
      .map((i) => i.msg);
    return { ok: false, issues, faceBox: null, faceBoxes: [], method: 'none' };
  }

  /* --- detecção de rosto ---
     Ordem de confiança:
       1. BlazeFace (rede neural) - detecta varias pessoas com precision
       2. tracking.js (Haar)      - detector classico, mais fraco
       3. pares de olhos          - estimativa geometrica
       4. tom de pele             - ultimo recurso, so para enquadramento

     IMPORTANTE: se o BlazeFace esta disponivel e nao encontra nenhum rosto,
     a foto e REPROVADA. Nao ha fallback permissivo nesse caso, senao fotos
     sem rosto (ou com mais gente) voltariam a ser aprovadas. */
  let method: SelfieCheck['method'] = 'none';
  let faceBox: Box | null = null;
  let extraFaces = 0;
  const faceBoxes: Box[] = [];
  let blazefaceFalhou = false;
  let blazefaceRodou = false;
  let deteccaoSuspeita = 0;

  /* 1) BlazeFace */
  try {
    const blazefaces = await detectFacesBlazeface(canvas);
    blazefaceRodou = true;

    /* Deteccoes com alta confianca contam como rosto. Deteccoes fracas
       (< 0.5) sao tratadas como suspeita: nao aprovam a foto, para nao
       deixar passar uma segunda pessoa com deteccao fraca. */
    const faces = blazefaces.filter((f) => f.score >= 0.5);
    const suspeitas = blazefaces.filter((f) => f.score < 0.5);
    deteccaoSuspeita = suspeitas.length;

    if (faces.length > 0) {
      method = 'blazeface';
      for (const f of faces) {
        faceBoxes.push({ x: f.x, y: f.y, w: f.w, h: f.h, area: f.w * f.h });
      }
      faceBoxes.sort((a, b) => b.area - a.area);
      faceBox = faceBoxes[0];
      extraFaces = faceBoxes.length - 1;
    }
  } catch {
    blazefaceFalhou = true;
  }

  if (!blazefaceRodou) {
    /* 2) tracking.js */
    const tracked = await detectFacesWithTracking(canvas);
    if (tracked.length > 0) {
      method = 'tracking';
      tracked.sort((a, b) => b.area - a.area);
      faceBox = tracked[0];
      extraFaces = tracked.length - 1;
      faceBoxes.push(...tracked);
    }
  }

  if (!blazefaceRodou && !faceBox && extraFaces === 0) {
    /* 3) pares de olhos.
       Cada par de olhos corresponde a um rosto, e permite medir o
       enquadramento com precisao. Se houver mais de um par, a foto tem
       mais de uma pessoa. */
    const darkBlobs = findDarkBlobs(data, w, h, Math.max(70, whole.mean * 0.55));
    const pairs = findEyePairs(darkBlobs, w, h);

    if (pairs.length > 1) {
      extraFaces = pairs.length - 1;
      method = 'eyes';
      for (const pair of pairs) faceBoxes.push(faceBoxFromEyes(pair));
    } else if (pairs.length === 1) {
      faceBox = faceBoxFromEyes(pairs[0]);
      method = 'eyes';
      faceBoxes.push(faceBox);
    } else {
      /* 4) tom de pele (menos preciso). */
      const regions = findSkinRegions(data, w, h);
      const candidates = regions.filter((r) => r.pixels > imgArea * 0.012);

      if (candidates.length > 0) {
        const main = candidates[0];
        const fill = main.pixels / Math.max(1, main.box.w * main.box.h);
        const shape = main.box.w / main.box.h;
        const cx = main.box.x + main.box.w / 2;
        const cy = main.box.y + main.box.h / 2;
        const offX = Math.abs(cx - w / 2) / w;
        const offY = Math.abs(cy - h / 2) / h;

        const compacts = fill > 0.5 && shape > 0.45 && shape < 1.9;
        const tamanhoOk = main.pixels > imgArea * 0.06 && main.pixels < imgArea * 0.55;
        const centralizado = offX < 0.2 && offY < 0.2;

        if (compacts && tamanhoOk && centralizado) {
          faceBox = {
            x: main.box.x,
            y: main.box.y,
            w: main.box.w,
            h: main.box.h,
            area: main.box.w * main.box.h,
          };
          method = 'skin';
          faceBoxes.push(faceBox);
        }

        // Outra pessoa: regiao grande, separada da principal.
        extraFaces = candidates.filter(
          (c) =>
            c !== main &&
            c.pixels > main.pixels * 0.5 &&
            c.pixels > imgArea * 0.04 &&
            c.pixels < imgArea * 0.55
        ).length;
      }
    }
  }

  if (!faceBox) {
    if ((extraFaces > 0 || deteccaoSuspeita > 0) && !formatoRuim) {
      add(1, 'Foram identificadas mais de uma pessoa na foto. Envie uma selfie apenas sua, com uma pessoa por foto.');
    } else {
      add(1, 'Não foi possível identificar o seu rosto. Enquadre bem o rosto, sem óculos escuros, boné ou máscara.');
    }
    canvas.remove();
    const issues = found
      .sort((a, b) => a.prio - b.prio)
      .filter((i, idx, arr) => arr.findIndex((o) => o.msg === i.msg) === idx)
      .map((i) => i.msg);
    return {
      ok: issues.length === 0,
      issues,
      faceBox: null,
      faceBoxes: [],
      method,
    };
  }

  if ((extraFaces > 0 || deteccaoSuspeita > 0) && !formatoRuim) {
    add(1, 'Foram identificadas mais de uma pessoa na foto. Envie uma selfie apenas sua, com uma pessoa por foto.');
  }

  /* --- tamanho do rosto --- */
  const faceRatio = faceBox.area / imgArea;
  if (faceRatio < 0.055) {
    add(3, 'O rosto está pequeno demais na foto. Aproxime-se da câmera até o rosto ocupar a maior parte da imagem.');
  } else if (faceRatio > 0.85) {
    add(3, 'O rosto está grande demais / muito colado na câmera. Afaste um pouco o aparelho.');
  }

  /* --- proporção do rosto --- */
  const faceAspect = faceBox.w / faceBox.h;
  if (faceAspect < 0.45 || faceAspect > 1.9) {
    add(3, 'O enquadramento do rosto está atípico. Centralize o rosto na foto, na vertical.');
  }

  /* --- centralidade ---
     O rosto precisa ficar perto do meio da foto. 0.22 = ~11% da largura
     para cada lado do centro, o que ainda tolera uma selfie normal. */
  const cx = faceBox.x + faceBox.w / 2;
  const cy = faceBox.y + faceBox.h / 2;
  const offX = Math.abs(cx - w / 2) / w;
  const offY = Math.abs(cy - h / 2) / h;
  if (offX > 0.22 || offY > 0.22) {
    add(3, 'O rosto não está centralizado na foto. Centralize o rosto no meio da imagem.');
  }

  /* --- iluminacao e contraste do rosto ---
     Brilho absoluto NAO reprova pele escura: o tom de pele (Cr em YCbCr e
     saturacao) e o criterio. Uma pele escura tem Cr alto; uma foto
     escurecida ou sem luz no rosto tem Cr baixo. */
  const faceStats = regionStats(data, w, h, faceBox);
  if (faceStats.mean < 30) {
    add(4, 'O rosto está escuro na foto. Vire-se para uma luz mais forte e refaça a selfie.');
  } else if (faceStats.mean > 242) {
    add(4, 'O rosto está com luz estourada. Tire a foto com o rosto iluminado, sem luz direta.');
  }
  if (faceStats.cr < 148 && faceStats.sat < 40) {
    add(4, 'A luz no rosto está fraca. Vire-se para uma luz mais forte e refaça a selfie.');
  }
  if (faceStats.std < 16) {
    add(4, 'O rosto não tem contraste suficiente. Use um local com luz natural, virado para o rosto.');
  }

  canvas.remove();

  const normalized = {
    x: faceBox.x / w,
    y: faceBox.y / h,
    w: faceBox.w / w,
    h: faceBox.h / h,
  };

  const normalizedBoxes = faceBoxes.map((b) => ({
    x: b.x / w,
    y: b.y / h,
    w: b.w / w,
    h: b.h / h,
  }));

  // Mostra no maximo 3 problemas, do mais critico para o menos critico.
  const issues = found
    .sort((a, b) => a.prio - b.prio)
    .filter((i, idx, arr) => arr.findIndex((o) => o.msg === i.msg) === idx)
    .slice(0, 3)
    .map((i) => i.msg);

  return {
    ok: issues.length === 0,
    issues,
    faceBox: normalized,
    faceBoxes: normalizedBoxes,
    method,
  };
}

if (process.env.NODE_ENV !== 'production' && typeof window !== 'undefined') {
  const w = window as unknown as {
    __validateSelfie?: typeof validateSelfie;
    __debugTracking?: (src: string) => Promise<number>;
  };
  w.__validateSelfie = validateSelfie;
  w.__debugTracking = async (src: string) => {
    const img = document.createElement('img');
    await new Promise<void>((resolve) => {
      img.onload = () => resolve();
      img.onerror = () => resolve();
      img.src = src;
    });
    const canvas = document.createElement('canvas');
    canvas.id = 'selfie-canvas';
    canvas.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0;';
    const scale = Math.min(1, MAX_DIM / Math.max(img.width, img.height));
    canvas.width = Math.round(img.width * scale);
    canvas.height = Math.round(img.height * scale);
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    if (ctx) ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    const found = await detectFacesWithTracking(canvas);
    canvas.remove();
    return found.length;
  };
}
