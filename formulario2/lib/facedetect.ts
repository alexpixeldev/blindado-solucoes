/**
 * Deteccao de rostos com BlazeFace (TensorFlow.js), 100% local.
 *
 * O tracking.js (classico, baseado em Haar cascade) e fraco: em fotos com
 * varias pessoas ele detecta apenas o rosto mais evidente e "aprova" a foto.
 * O BlazeFace e um detector de redes neurais, detecta varias pessoas e
 * funciona bem com diferentes tons de pele.
 *
 * O modelo e servido do proprio dominio (/js/blazeface) porque a CSP do
 * servidor bloqueia requisicoes para dominios externos.
 */

import '@tensorflow/tfjs-backend-cpu';

export interface DetectedFace {
  x: number;
  y: number;
  w: number;
  h: number;
  score: number;
  keypoints: Array<{ x: number; y: number }>;
}

let modelPromise: Promise<any> | null = null;
let tfPromise: Promise<any> | null = null;

function basePath(): string {
  return process.env.NEXT_PUBLIC_BASE_PATH || '';
}

/** Carrega o runtime do TensorFlow.js e o modelo BlazeFace (uma unica vez). */
async function loadModel(): Promise<any> {
  if (!modelPromise) {
    modelPromise = (async () => {
      const [tf, blazeface] = await Promise.all([
        import('@tensorflow/tfjs-core'),
        import('@tensorflow-models/blazeface'),
      ]);

      // Sem WASM (a CSP nao libera wasm-unsafe-eval): usa o backend JS.
      try {
        await tf.setBackend('cpu');
      } catch {
        /* mantem o backend padrao */
      }
      await tf.ready();

      const modelUrl = `${basePath()}/js/blazeface/model.json`;
      return blazeface.load({
        modelUrl,
        maxFaces: 10,
        inputWidth: 128,
        inputHeight: 128,
        iouThreshold: 0.3,
        scoreThreshold: 0.3,
      });
    })().catch((err) => {
      modelPromise = null;
      throw err;
    });
  }
  return modelPromise;
}

/** Garante que o runtime do tf está pronto antes de usar. */
export async function isFaceModelReady(): Promise<boolean> {
  try {
    if (!tfPromise) {
      tfPromise = import('@tensorflow/tfjs-core').then((tf) => {
        try {
          tf.setBackend('cpu');
        } catch {
          /* ignore */
        }
        return tf.ready().then(() => tf);
      });
    }
    await tfPromise;
    return true;
  } catch {
    return false;
  }
}

/**
 * Detecta rostos em um canvas.
 * Devolve as caixas em coordenadas do canvas e o score de cada deteccao.
 */
export async function detectFacesBlazeface(
  canvas: HTMLCanvasElement
): Promise<DetectedFace[]> {
  const model = await loadModel();
  const tf = await import('@tensorflow/tfjs-core');

  const input = tf.browser.fromPixels(canvas);

  try {
    const predictions = await model.estimateFaces(input, false, false, true);

    const out: DetectedFace[] = [];
    for (const p of predictions) {
      const topLeft = p.topLeft as [number, number];
      const bottomRight = p.bottomRight as [number, number];
      const kp = (p.landmarks || []).map((k: any) => ({ x: k.x, y: k.y }));

      let score = 0;
      const prob = p.probability;
      if (Array.isArray(prob)) {
        score = typeof prob[0] === 'number' ? prob[0] : 0;
      } else if (prob && typeof prob.data === 'function') {
        try {
          const data = await prob.data();
          score = typeof data[0] === 'number' ? data[0] : 0;
        } catch {
          score = 0;
        }
      }

      out.push({
        x: topLeft[0],
        y: topLeft[1],
        w: bottomRight[0] - topLeft[0],
        h: bottomRight[1] - topLeft[1],
        score,
        keypoints: kp,
      });
    }

    return out.sort((a: DetectedFace, b: DetectedFace) => b.score - a.score);
  } finally {
    input.dispose();
  }
}

if (process.env.NODE_ENV !== 'production' && typeof window !== 'undefined') {
  (window as unknown as { __detectFacesBlazeface?: typeof detectFacesBlazeface })
    .__detectFacesBlazeface = detectFacesBlazeface;
}
