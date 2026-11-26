# Padrões de desenvolvimento

## Ambiente e dependências

- PHP `^8.5`, Laravel `^13.17`, PHPUnit `^12.5`, Vite `^8`, Tailwind CSS `^4`; confirme o lock/package manifest antes de depender de API específica.
- O projeto usa Laravel Sail. Prefixe PHP, Artisan, Composer e Node com `vendor/bin/sail`. Não adicione pacotes sem autorização.
- Regras operacionais e comandos estão no [AGENTS.md](../AGENTS.md); estas notas complementam o guia com convenções de domínio.

## Organização e nomes

- Siga estrutura existente: controllers singular por recurso, Form Requests nomeados `XRequest`, models Eloquent, operações multirregistro/financeiras em Actions com `handle`, views Blade por módulo e componentes compartilhados em `resources/views/components/ui`.
- Use rotas nomeadas em links e redirects; valide escrita por Form Request. Buscas simples usam `ListSearchRequest` e preservam query string/paginação.
- PHP segue tipos explícitos, retornos declarados, chaves em estruturas de controle e formatação Pint. JavaScript é ES module; evite lógica duplicada por tela quando já houver componente comum.
- Eloquent usa `#[Fillable]`, métodos `casts()`, relações tipadas e atributos/escopos Laravel atuais. Imite arquivos irmãos antes de escolher convenções novas.

## Dados e operações seguras

- Mantenha lógica de apresentação separada de precisão e aritmética de domínio. Use `Brick\Math\BigDecimal` nos cálculos existentes de custo médio, margens, totais e pagamentos; não converta valor interno para float para calcular.
- Operações que mudam saldo/estoque/pagamentos devem ser transacionais. Preserve locks, idempotência, snapshots, constraints únicas e validação contra saldo insuficiente.
- Não apague registros históricos para simular cancelamento; use estados/movimentos conforme o fluxo estabelecido.
- Não amplie `fillable`, não exponha dados sensíveis e não reduza autenticação de rotas. Credenciais locais ficam fora da documentação e do Git; consulte `.env.example`, nunca copie `.env`.

## Erros, validações e interface

- Validação de entrada permanece no Form Request (ou validação Laravel explícita em endpoints simples como motivos). Falhas de domínio usam `ValidationException` com mensagem compreensível; deixe Laravel renderizar erros de formulário.
- Mantenha respostas JSON e HTML já suportadas pelos editores; preserve códigos de status e mensagens consumidas pelo JavaScript.
- Na UI, priorize reutilização dos componentes e atributos de acessibilidade; preservar filtros, paginação e histórico é parte do comportamento da listagem.

## Testes e documentação

- Testes PHP vivem em `tests/Feature` e `tests/Unit`; funções JS podem ter testes nativos Node em `tests/Unit/*.test.js`, executados com `vendor/bin/sail node --test tests/Unit/*.test.js` (não há script npm para eles; passar só o diretório falha). Consulte `phpunit.xml`, `composer.json` e `package.json` para execução/versionamento.
- Escolha o teste mais estreito pertinente e cubra efeitos de sucesso, rejeição e atomicidade quando a regra envolve múltiplas gravações. Os testes são evidência de cobertura, não prova de fluxo de navegador além do que exercitam.
- Quando modificar PHP, rode `vendor/bin/sail bin pint --dirty --format agent` conforme AGENTS. Documente decisões e discrepâncias em `docs/` quando mudarem; não crie documentos genéricos sem necessidade.

## Deploy (Render + Neon)

- Produção roda no Render como Web Service Docker (`render.yaml`, plano free) a partir da branch `main`; o banco PostgreSQL fica no Neon. A imagem (`Dockerfile`) usa FrankenPHP, instala só dependências `--no-dev`, compila os assets com Vite e roda como usuária sem privilégios na porta `$PORT`.
- Nenhum segredo entra na imagem nem no Git. No painel do Render, cadastre `APP_KEY` (gere com `vendor/bin/sail artisan key:generate --show`), `APP_URL` (URL pública `https://…onrender.com`) e `DB_URL` (connection string do Neon, com `sslmode=require`).
- A cada início, `docker/start.sh` roda `php artisan optimize` e `migrate --force` (só migrations pendentes, com novas tentativas para o Neon acordar). Seeders não rodam em produção.
- O Render termina HTTPS no balanceador; por isso `bootstrap/app.php` confia em todos os proxies e o cookie de sessão é `secure`. O health check é `/up`.
- Para criar a primeira usuária, abra o Shell do serviço no Render e rode `php artisan app:create-user`; a senha é digitada de forma oculta e nunca passada como argumento ou variável de ambiente.
- No plano free o serviço hiberna sem acesso e o primeiro request seguinte demora; filas rodam `sync` porque não há worker.
