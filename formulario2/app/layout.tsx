import type { Metadata, Viewport } from 'next';
import Script from 'next/script';
import 'flatpickr/dist/flatpickr.min.css';
import './globals.css';

export const metadata: Metadata = {
  title: 'Formulário de Locação | Blindado Soluções',
  description: 'Preencha os dados abaixo para registrar sua locação.'
};

export const viewport: Viewport = {
  themeColor: '#04352a',
  width: 'device-width',
  initialScale: 1
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const basePath = process.env.NEXT_PUBLIC_BASE_PATH || '';
  return (
    <html lang="pt-br" className="h-full">
      <head>
        <link rel="icon" type="image/png" href={`${basePath}/img/escudo.png`} />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet"
        />
        <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        />
      </head>
      <body className="relative min-h-full bg-[#01100b] text-slate-900 antialiased overflow-x-hidden">
        {/* Fundo bold estilo Typeform */}
        <div aria-hidden className="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
          <div className="absolute inset-0 bg-gradient-to-br from-[#01100b] via-[#03211a] to-[#011a13]" />
          <div className="absolute -left-1/3 -top-1/4 h-[38rem] w-[38rem] rounded-full bg-primary-500/10 blur-[120px]" />
          <div className="absolute -right-1/3 top-2/4 h-[28rem] w-[28rem] rounded-full bg-emerald-400/8 blur-[110px]" />
          <div className="absolute bottom-[-20%] left-1/3 h-[24rem] w-[24rem] rounded-full bg-teal-400/6 blur-[110px]" />
          <div className="absolute inset-0 bg-grid-slate bg-grid opacity-[0.12] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]" />
        </div>

        {children}

        <Script
          src="https://trackingjs.com/bower/tracking.js/build/tracking-min.js"
          strategy="afterInteractive"
        />
        <Script
          src="https://trackingjs.com/bower/tracking.js/build/data/face-min.js"
          strategy="afterInteractive"
        />
      </body>
    </html>
  );
}
