# Saler

Sistema web para apoiar a rotina de um pequeno comércio: cadastro de produtos, clientes, fornecedores e categorias; compras, vendas, estoque e recebimentos. A interface é pensada para uso diário por uma vendedora, com foco em leitura rápida, preservação do contexto e operações simples.

## Stack

Laravel 13 / PHP 8.5, Blade, Eloquent, PostgreSQL no Sail, Vite 8, Tailwind CSS 4 e JavaScript ES modules.

## Desenvolvimento local

Pré-requisitos: Docker e dependências instaladas no repositório.

```bash
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

Configure o ambiente local a partir de `.env.example`; não compartilhe nem versione `.env`. O usuário inicial depende dos seeders/configuração local — não há credenciais padrão documentadas aqui.

## Documentação para agentes e contribuidores

- [AGENTS.md](AGENTS.md): ponto de entrada, regras operacionais e checklist de documentação.
- [Índice de domínio](docs/README.md): arquitetura, regras de negócio, padrões, UX, estado atual e decisões.
- [Requisitos funcionais originais](docs/requisitos-funcionais.pdf) e [diagrama lógico](docs/modelagem-logica-banco-de-dados.webp): referências de requisitos; não substituem código/migrations/testes como evidência do que está implementado.

Leia [docs/current-state.md](docs/current-state.md) antes de assumir que um requisito planejado está disponível. Use Sail para comandos PHP/Artisan/Composer/Node e consulte `AGENTS.md` para validações e convenções.
