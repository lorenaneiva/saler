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

- Toda tabela usa `<x-ui.data-table>` (`id`, `title`, `paginator`, `unit` ou `meta`), que reúne em um único painel o cabeçalho (título e quantidade à esquerda; slot `filters` com `<x-ui.list-search>` e slot `actions` à direita), a tabela e o rodapé de paginação. Não crie caixas de filtro separadas nem `<x-ui.table>` solta em telas novas; `<x-ui.table>` é a primitiva interna.
- Alinhamento: textos, quantidades, datas e situações à esquerda; valores monetários à direita com a classe `money` no `th` e no `td`; coluna Ações à direita (`text-right` no cabeçalho, `.row-actions` na célula). `numeric` apenas aplica algarismos tabulares sem alinhar. Totais ficam em `tfoot.table-totals`, um por linha, com o total final em `.table-grand-total`.
- Situação financeira usa `<x-ui.payment-status>` (Quitada verde, Parcialmente paga amarelo, Pendente vermelho); outras situações usam `<x-ui.badge>`. Estado vazio usa `<x-ui.empty>` (com `title` opcional para título e explicação).
- `.data-table` usa `table-layout: fixed`: todas as colunas têm a mesma largura e o texto quebra dentro da célula. A largura mínima cresce com o número de colunas (32rem até 4, depois +8rem por coluna, até 64rem com 8); abaixo disso a região rola horizontalmente. Não reintroduza `whitespace-nowrap` ou padding próprio em `th` de tabela, pois isso quebra o espaçamento uniforme.
- Linhas alternam branco e azul-acinzentado bem claro (`#f2f5f9`); linhas clicáveis escurecem para `#d9e6f8` no hover/foco.
- Busca e filtros (`<x-ui.list-search>` com `<x-ui.table-filter>` no slot) ficam no cabeçalho da tabela, sem botão Buscar: o texto pesquisa automaticamente após 350 ms e selects aplicam ao mudar, atualizando só as regiões `data-live-region` (contagem, resultados, paginação, botão Limpar) e a URL, sempre voltando à primeira página. Enter aplica na hora. O botão Limpar fica sempre visível e desabilitado sem filtros ativos. Busca textual tem limite de 100 caracteres.
- Paginação (`<x-ui.pagination>`, renderizada pelo `data-table`) fica no rodapé, à direita: intervalo e total, seta anterior, campo com a página atual, total de páginas e seta seguinte. As setas ficam sempre visíveis e desabilitadas quando não há para onde ir; o campo aceita só números, limita ao intervalo válido e navega com Enter ou ao sair do campo. A navegação troca apenas as regiões da tabela, mantendo filtros e posição.
- Carregamento: durante buscas e trocas de página, a seção recebe `aria-busy`, exibe uma barra fina no topo e esmaece os resultados. Scripts por linha (editores em linha, máscaras) são reinicializados pelo evento `live-regions:replaced`.
- Ações de linha são enxutas, com botões pequenos de largura mínima igual (5,75rem) para manter as ações alinhadas entre linhas, e podem quebrar em duas linhas quando a coluna for estreita; em produto, edição usa drawer para não expandir linhas nem ocultar completamente a listagem. Ao salvar, JavaScript atualiza a página filtrada preservando contexto de página/scroll.
- Identificadores de registro (venda, compra, pagamento) aparecem nas tabelas apenas como número, sem `#`. Nenhum link dentro de tabela tem aparência de link: todos herdam cor e peso do texto da célula e não têm sublinhado (regra global em `app.css`; não adicione `text-brand underline` em células). Links secundários dentro de uma linha clicável (como o nome do cliente na listagem de vendas) usam `.cell-link`, que só sublinha no hover/foco e leva a outra tela. Fora de tabelas, links de texto usam `.text-link` (cor da marca, sublinhado no hover). Linhas com `data-row-link` continuam inteiramente clicáveis, com fundo de hover/foco e outline de teclado. Ações em botão mantêm o estilo de botão.
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
