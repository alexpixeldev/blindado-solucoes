import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Hospedagem compartilhada (FTP) nao roda Node: geramos HTML estatico.
  output: "export",
  // Sem servidor de otimizacao de imagem, que exige runtime Node.
  images: { unoptimized: true },
  // Raiz do dominio: https://blindadosolucoes.com.br/
  trailingSlash: false,
};

export default nextConfig;
