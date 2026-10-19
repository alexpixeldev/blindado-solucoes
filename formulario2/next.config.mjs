const basePath = process.env.NEXT_PUBLIC_BASE_PATH || '';
const isDev = process.env.NODE_ENV === 'development';

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  basePath: basePath || undefined,
  assetPrefix: basePath || undefined,
  trailingSlash: true,
  images: {
    unoptimized: true
  },
  ...(isDev
    ? {
        async rewrites() {
          return [
            {
              source: '/blindado/formulario/:path*',
              destination: 'http://localhost/blindado/formulario/:path*'
            }
          ];
        }
      }
    : {})
};

export default nextConfig;
