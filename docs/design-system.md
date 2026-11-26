# Design System e UX/UI

Este registro descreve o padrão visível em `resources/css/app.css`, componentes Blade e JS atuais, somado às preferências de produto registradas no histórico do projeto. A prioridade é uma vendedora que usa o sistema diariamente: leitura rápida, ações previsíveis, poucos passos e preservação do contexto.

## Identidade visual e layout

- Marca azul `#2058ad`, hover `#17468d`, sidebar azul-marinho `#142941`; superfícies em cinza-azulado claro (`#f6f8fc`) e branco, texto escuro (`#18263b`), texto secundário `#4d607a` e linhas `#dbe3ef`.
- Fonte `Instrument Sans` com fallbacks de sistema; tamanhos compactos e legíveis, títulos de página em torno de 24–29 px e corpo de formulário/tabela geralmente 14 px.
- Shell com navegação lateral fixa em desktop e navegação recolhível em viewport pequeno. Área de conteúdo centralizada até 100 rem; grade de formulário cresce conforme breakpoint.
- Componentes atualmente usam cantos quadrados ou discretos, sem sombra decorativa forte. Tokens CSS estão na camada base de `resources/css/app.css`.

## Ações e campos

- Botões primário azul, secundário branco com borda, destrutivo vermelho e quiet para ações de menor peso. Altura padrão mínima de 40 px; botão pequeno 32 px. Estados disabled reduzem opacidade e bloqueiam cursor.
- Inputs têm borda cinza, altura mínima de 40 px e espaçamento horizontal; foco usa sombra cinza sutil de 4 px/baixa opacidade e transição global de 180 ms `ease-in-out`. Não deve haver anel externo grande que cubra texto ou mova layout.
- Foco de teclado global é visível por outline azul `:focus-visible`; campos tratam o foco próprio sem deslocamento. Campo inválido usa borda vermelha e erros próximos ao controle.
- Componente reutilizável `<x-ui.money>` centraliza exibição `R$ #.###,##`, exatamente duas casas na apresentação. Margem usa percentual com duas casas. Preservar casas adicionais em custo armazenado e cálculos internos.

## Autocomplete

`resources/js/autocomplete.js` transforma selects nativos para uma combobox pesquisável, preservando o select original como valor submetido. `autocomplete-search.js` contém normalização e navegação. Busca local ignora caixa/acentos; fontes com URL consultam `/opcoes/{source}`, com paginação/busca assíncrona nos fluxos configurados. A lista fica em camada fixa/top-layer para evitar corte por overflow; resultados têm estados selecionado/ativo, mensagem sem resultados, limpar em seleção opcional, rolagem e carregamento.

Manter `role=combobox/listbox/option`, `aria-expanded`, seleção ativa, rótulo e anúncios de status. Setas, Enter, Tab e Escape precisam permanecer funcionais; clique fora fecha. Pesquisa deve continuar disponível mesmo com poucas opções. Confira `resources/js/autocomplete.js`, `autocomplete-search.js` e a cobertura de `AutocompleteTest` antes de mudar.

## Tabelas, filtros e dados

- Listagens usam `<x-ui.table>`, cabeçalho contrastante, linhas compactas e paginação reutilizável. Padrão de referência: tabela de Fornecedores — todos os dados (texto, números, valores, situação) ficam alinhados à esquerda em colunas de largura igual, e apenas a coluna Ações fica à direita (`text-right` no cabeçalho, `.row-actions` na célula). A classe `numeric` só aplica algarismos tabulares e evita quebra; não alinha à direita. Exceção: em tabelas com rodapé de totais (`tfoot.table-totals`, como os produtos da venda), a coluna totalizada e os valores do rodapé ficam à direita (`text-right`), cada total em sua própria linha e o total final destacado (`.table-grand-total`).
- `.data-table` usa `table-layout: fixed`: todas as colunas têm a mesma largura e o texto quebra dentro da célula. A largura mínima cresce com o número de colunas (32rem até 4, depois +8rem por coluna, até 64rem com 8); abaixo disso a região rola horizontalmente. Não reintroduza `whitespace-nowrap` ou padding próprio em `th` de tabela, pois isso quebra o espaçamento uniforme.
- Linhas alternam branco e azul-acinzentado bem claro (`#f2f5f9`); linhas clicáveis escurecem para `#d9e6f8` no hover/foco.
- Barra de busca/filtros fica no topo; `<x-ui.list-search>` aceita filtros extras no slot (ex.: situação em Categorias), enviados junto com a busca; busca textual tem limite de 100 caracteres e consultas paginadas preservam query string. Produtos têm filtros de categoria, fornecedor e situação.
- Ações de linha são enxutas, com botões pequenos de largura mínima igual (5,75rem) para manter as ações alinhadas entre linhas, e podem quebrar em duas linhas quando a coluna for estreita; em produto, edição usa drawer para não expandir linhas nem ocultar completamente a listagem. Ao salvar, JavaScript atualiza a página filtrada preservando contexto de página/scroll.
- Identificadores de registro (venda, compra, pagamento) aparecem nas tabelas apenas como número, sem `#`. Nenhum link dentro de tabela tem aparência de link: todos herdam cor e peso do texto da célula e não têm sublinhado (regra global em `app.css`; não adicione `text-brand underline` em células). Linhas com `data-row-link` continuam inteiramente clicáveis, com fundo de hover/foco e outline de teclado. Ações em botão mantêm o estilo de botão.
- O perfil do cliente organiza compras, contas em aberto, pagamentos e cadastro em abas; abre direto na aba Compras. Não comprimir campos dentro de células de listagem.

## Dialogs, drawers e motion

- `<x-ui.dialog>` usa elemento `dialog` nativo, backdrop e controle de foco. Drawer de produto entra pelo lado direito, largura máxima aproximada de 460 px e largura total em mobile; conteúdo rola independentemente e rodapé fica fixo.
- Edição de produto agrupa identificação, informações comerciais e disponibilidade; estoque/custo são distinguidos como leitura quando não editáveis no catálogo. Novo produto reutiliza o mesmo editor. Há confirmação ao descartar mudanças e feedback após salvar.
- Transições usam token de 180 ms. Evite movimentos grandes, layout shift ou animações chamativas; preserve foco visível e fechamento acessível.

## Estados, responsividade e acessibilidade

- Feedback usa notices de sucesso/erro/info, com `role=status`/`alert` e regiões vivas quando aplicável. Formulários sinalizam busy, foco primeiro erro e mantêm valores submetidos.
- Listas vazias têm mensagem; autocomplete anuncia carregamento/resultado. Botões de ícone têm nome acessível e dialogs título associado.
- Layouts de tabela permitem rolagem horizontal quando necessário; em telas pequenas a navegação/formulários se ajustam e drawer usa viewport inteira. Evite depender só de cor para estados.
- `resources/css/app.css` já reduz animações e transições a praticamente zero sob `prefers-reduced-motion: reduce` e define outlines com `Highlight` sob `forced-colors: active` (foco de campo e opção ativa do autocomplete). Novas animações devem continuar cobertas por essas regras globais.

## Critério de decisão de interface

Escolha o menor número de cliques e campos visíveis necessários para a tarefa, mantenha contexto de busca/posição e deixe ações primárias distinguíveis. Prefira edição contextual a transportar a pessoa para outra página quando isso acelerar a rotina sem sacrificar legibilidade. Reutilize componentes globais em vez de criar variações por tela.
