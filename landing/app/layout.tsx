import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  variable: "--font-inter",
});

const titulo =
  "Blindado Soluções | Portaria Remota e Segurança Condominial";

const descricao =
  "Portaria remota 24/7, videomonitoramento e controle de acesso para condomínios. Modernize a gestão e corte despesas com tecnologia e equipe treinada.";

export const metadata: Metadata = {
  metadataBase: new URL("https://blindadosolucoes.com.br"),
  title: titulo,
  description: descricao,
  applicationName: "Blindado Soluções",
  alternates: { canonical: "/" },
  openGraph: {
    type: "website",
    locale: "pt_BR",
    url: "https://blindadosolucoes.com.br",
    siteName: "Blindado Soluções",
    title: titulo,
    description: descricao,
    images: [{ url: "/img/logo_horizontal.png", width: 930, height: 243 }],
  },
  twitter: {
    card: "summary_large_image",
    title: titulo,
    description: descricao,
    images: ["/img/logo_horizontal.png"],
  },
  robots: { index: true, follow: true },
};

export const viewport: Viewport = {
  themeColor: "#1a3c34",
  width: "device-width",
  initialScale: 1,
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="pt-BR" className={inter.variable}>
      <head>
        <link rel="icon" type="image/png" href="/img/escudo.png" />
        <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        />
      </head>
      <body className="font-sans">{children}</body>
    </html>
  );
}
