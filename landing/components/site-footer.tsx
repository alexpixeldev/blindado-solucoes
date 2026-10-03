import { CONTATOS, NAV_LINKS, REDES } from "@/lib/site";

export function SiteFooter() {
  return (
    <footer className="bg-brand-900 pb-10">
      <div className="container-page">
        <div className="grid gap-10 py-14 md:grid-cols-12">
          <div className="md:col-span-4">
            <img
              src="/img/logo_horizontal.png"
              alt="Blindado Soluções"
              width={930}
              height={243}
              className="h-10 w-auto brightness-0 invert"
            />
            <p className="mt-5 max-w-sm text-sm leading-relaxed text-brand-100/70">
              Portaria remota, videomonitoramento e controle de acesso para
              condomínios. Operando 24 horas por dia, todos os dias.
            </p>

            <div className="mt-6 flex gap-3">
              {REDES.map((r) => (
                <a
                  key={r.label}
                  href={r.href}
                  aria-label={r.label}
                  className="grid h-10 w-10 place-items-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                >
                  <i className={r.icon} aria-hidden />
                </a>
              ))}
            </div>
          </div>

          <nav className="md:col-span-3" aria-label="Menu do rodape">
            <h4 className="text-sm font-bold tracking-widest text-brand-300 uppercase">
              Menu
            </h4>
            <ul className="mt-5 space-y-3">
              {NAV_LINKS.map((l) => (
                <li key={l.href}>
                  <a
                    href={l.href}
                    className="text-sm text-brand-100/80 transition-colors hover:text-white"
                  >
                    {l.label}
                  </a>
                </li>
              ))}
            </ul>
          </nav>

          <div className="md:col-span-5">
            <h4 className="text-sm font-bold tracking-widest text-brand-300 uppercase">
              Contato
            </h4>
            <ul className="mt-5 grid gap-4 lg:grid-cols-2">
              {CONTATOS.map((c) => {
                const conteudo = (
                  <>
                    <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white/10 text-brand-300">
                      <i className={`fa-solid ${c.icon} text-sm`} aria-hidden />
                    </span>
                    <div className="min-w-0">
                      <p className="text-sm font-semibold text-white">{c.titulo}</p>
                      {c.linhas.map((l) => (
                        <p key={l} className="text-sm break-words text-brand-100/70">
                          {l}
                        </p>
                      ))}
                    </div>
                  </>
                );

                return c.href ? (
                  <li key={c.titulo}>
                    <a
                      href={c.href}
                      className="flex items-start gap-3 transition-opacity hover:opacity-80"
                    >
                      {conteudo}
                    </a>
                  </li>
                ) : (
                  <li key={c.titulo} className="flex items-start gap-3">
                    {conteudo}
                  </li>
                );
              })}
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 pt-7">
          <p className="text-center text-xs text-brand-100/60">
            © {new Date().getFullYear()} Blindado Soluções - Todos os direitos
            reservados.
          </p>
        </div>
      </div>
    </footer>
  );
}
