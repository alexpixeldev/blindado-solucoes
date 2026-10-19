declare global {
  interface Window {
    tracking?: any;
  }
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

function detectFaceByColorAnalysis(
  canvas: HTMLCanvasElement,
  data: Uint8ClampedArray
): Promise<{ hasFace: boolean; reason?: string }> {
  const tracking = typeof window !== 'undefined' ? window.tracking : undefined;
  const trackingLoaded = !!tracking && typeof tracking.ObjectTracker !== 'undefined';

  return new Promise((resolve) => {
    if (!trackingLoaded) {
      let skinPixels = 0;
      for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        if (r > 95 && g > 40 && b > 20 && r > g && r > b && Math.abs(r - g) > 15 && r - g < 100) {
          skinPixels++;
        }
      }
      const totalPixels = data.length / 4;
      const skinRatio = skinPixels / totalPixels;
      if (skinRatio > 0.05) {
        resolve({ hasFace: true });
      } else {
        resolve({ hasFace: false, reason: 'Rosto não identificado. A foto deve mostrar claramente o rosto da pessoa.' });
      }
      return;
    }

    const tracker = new tracking.ObjectTracker('face');
    tracker.setInitialScale(4);
    tracker.setStepSize(2);
    tracker.setEdgesDensity(0.1);

    tracking.track('#temp-canvas', tracker);

    tracker.on('track', (event: { data: Array<{ width: number; height: number; x: number; y: number }> }) => {
      if (event.data.length === 0) {
        resolve({ hasFace: false, reason: 'Rosto não identificado. A foto deve mostrar claramente o rosto da pessoa.' });
      } else if (event.data.length > 1) {
        resolve({ hasFace: false, reason: 'Múltiplos rostos identificados. A foto deve mostrar apenas uma pessoa.' });
      } else {
        const face = event.data[0];
        const faceArea = face.width * face.height;
        const imageArea = canvas.width * canvas.height;
        const faceRatio = faceArea / imageArea;

        if (faceRatio < 0.1) {
          resolve({ hasFace: false, reason: 'Rosto muito pequeno. Aproxime-se da câmera.' });
        }

        const faceCenterX = face.x + face.width / 2;
        const faceCenterY = face.y + face.height / 2;
        const imageCenterX = canvas.width / 2;
        const imageCenterY = canvas.height / 2;

        const offsetX = Math.abs(faceCenterX - imageCenterX) / canvas.width;
        const offsetY = Math.abs(faceCenterY - imageCenterY) / canvas.height;

        if (offsetX > 0.3 || offsetY > 0.3) {
          resolve({ hasFace: false, reason: 'Rosto não centralizado. Centralize o rosto na imagem.' });
        }

        resolve({ hasFace: true });
      }
    });

    setTimeout(() => {
      resolve({ hasFace: false, reason: 'Rosto não identificado. A foto deve mostrar claramente o rosto da pessoa.' });
    }, 5000);
  });
}

export async function validateSelfie(src: string): Promise<string[]> {
  const canvas = document.createElement('canvas');
  canvas.id = 'temp-canvas';
  canvas.style.display = 'none';
  document.body.appendChild(canvas);
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    canvas.remove();
    return [];
  }

  const img = document.createElement('img');

  return new Promise<string[]>((resolve) => {
    img.onload = async () => {
      canvas.width = img.width;
      canvas.height = img.height;
      ctx.drawImage(img, 0, 0);

      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const data = imageData.data;

      let totalBrightness = 0;
      for (let i = 0; i < data.length; i += 4) {
        totalBrightness += (data[i] + data[i + 1] + data[i + 2]) / 3;
      }
      const avgBrightness = totalBrightness / (data.length / 4);

      let minBrightness = 255;
      let maxBrightness = 0;
      for (let i = 0; i < data.length; i += 4) {
        const brightness = (data[i] + data[i + 1] + data[i + 2]) / 3;
        if (brightness < minBrightness) minBrightness = brightness;
        if (brightness > maxBrightness) maxBrightness = brightness;
      }
      const contrast = maxBrightness - minBrightness;

      const issues: string[] = [];

      if (avgBrightness < 50) {
        issues.push('A foto está muito escura. Use um local bem iluminado.');
      } else if (avgBrightness > 220) {
        issues.push('A foto está muito clara. Evite luz direto na câmera.');
      }

      if (contrast < 80) {
        issues.push('A foto tem pouco contraste. Certifique-se de que o rosto esteja bem visível.');
      }

      if (canvas.width < 300 || canvas.height < 300) {
        issues.push('A resolução da foto é muito baixa. Use uma foto de melhor qualidade.');
      }

      const faceDetection = await detectFaceByColorAnalysis(canvas, data);
      if (!faceDetection.hasFace) {
        issues.push(faceDetection.reason || 'Rosto não identificado. A foto deve mostrar claramente o rosto da pessoa.');
      }

      canvas.remove();
      resolve(issues);
    };
    img.onerror = () => {
      canvas.remove();
      resolve([]);
    };
    img.src = src;
  });
}
