# Estado atual do projeto

Revisão documental do código, testes, migrations, requisitos e histórico Git até 26/11/2026. “Implementado” abaixo significa que existe fluxo de código e cobertura relacionada; não representa homologação em produção ou validação manual completa de todos os navegadores.

## Implementado com cobertura identificada

- Login/logout por sessão, proteção de rotas e bloqueio após cinco tentativas de login inválidas (`AuthenticationTest`). Não há matriz de papéis/permissões.
- Cadastros de categoria, produto, cliente e fornecedor; busca/paginação; desativação de produtos e fornecedores; reativação de fornecedor; edição contextual de categoria/fornecedor/cliente e drawer de produto (`CategoryTest`, `ProductTest`, `CustomerTest`, `SupplierTest`, `InlineEditingTest`, `ProductDrawerTest`).
- Compra com múltiplos itens, cadastro de produto pela compra, desconto, atualização imediata de estoque/custo médio e cancelamento com reversão condicionada a saldo (`PurchaseTest`). Há estados pending/confirmed/cancelled, embora o fluxo de criação confirme imediatamente.
- Consulta de estoque, histórico de movimentos, motivos e entradas/saídas manuais com auditoria (`StockTest`).
- Venda com múltiplos itens, snapshots de preço/custo, desconto, baixa de estoque e rejeição atômica por saldo insuficiente (`SaleTest`).
- Pagamentos de cliente com métodos definidos, alocação automática da dívida mais antiga, saldo em aberto, idempotência da submissão e rejeição de excesso (`CustomerPaymentTest`). Perfil apresenta compra/pagamento e resumo financeiro.
- Combobox/autocomplete global com busca local/assíncrona; drawer de produto reaproveita formulário; busca nas listagens (`AutocompleteTest`, `ListSearchTest`).
- Componente comum de moeda com saída de duas casas brasileiras (`MoneyFormattingTest`) e cálculos JS de preço/margem cobertos por testes unitários.

## Parcial ou não implementado perante o requisito

- Requisitos RF012 de parcelamento, vencimentos e alocação por parcela não aparecem no esquema nem nos fluxos.
- Cancelamento de venda com preservação do histórico e estorno/devolução não tem rota/Action.
- Distribuição manual de pagamento, estorno de pagamentos, datas de vencimento/atraso e lista dedicada de clientes devedores não estão implementados. Pagamento é distribuído automaticamente entre vendas abertas antigas.
- Relatórios RF018–RF023 e cobrança via WhatsApp RF024 são evoluções futuras no PDF, sem telas/rotas correspondentes identificadas.
- Produto guarda um fornecedor direto opcional; a compra preserva seu fornecedor. Não há relação produto-fornecedores múltipla como cadastro de fornecimento.
- Requisito diz permitir cancelar compra sem entrada; o caminho da compra criada hoje confirma de imediato, embora exista estado pending e rota para cancelar/confirmar.
- A busca assíncrona de autocomplete tem um conjunto de fontes whitelist e precisa ser mantida alinhada a cada nova relação; não equivale a carregamento remoto universal para todo select.

## Bugs conhecidos e dívidas técnicas observáveis

- `tests/Feature/CustomerTest.php` existe, mas a revisão não encontrou métodos de teste nomeados; a cobertura efetiva de cliente parece estar espalhada em `InlineEditingTest`/`CustomerPaymentTest`. Confirme o arquivo quando ampliar testes do módulo.
- `tests/Feature/ExampleTest.php` e `tests/Unit/ExampleTest.php` são testes de scaffold, não evidência de requisito de domínio.
- `README.md` original era o README padrão do Laravel (substituído por overview e instruções neste trabalho); o nome/descrição Composer ainda identificam o esqueleto `laravel/laravel`.
- O PDF e o diagrama são documentos de referência originais, não especificação executável; algumas regras planejadas divergem do código, listadas em [regras de negócio](business-rules.md).
- `ConfirmPurchase` arredonda custo médio a 4 casas; `CancelPurchase` retira quantidade, mas não recalcula custo médio. Definir política antes de mexer, considerando vendas/entradas subsequentes e auditabilidade.
- A exibição monetária em `<x-ui.money>` converte para float antes de `number_format`; embora testes cubram valores usuais e a camada seja somente apresentação, vale avaliar limites de precisão para valores extremos permitidos pelas colunas.
- Testes cobrem controllers/HTML/ações e utilitários, mas não foi identificado teste automatizado real de navegador para foco, leitor de tela, posicionamento/responsividade do drawer e autocomplete. Acessibilidade visual precisa de verificação manual.

Não foi executada suíte de testes nesta tarefa, pois o escopo é exclusivamente documentação. Não há bug de interface assumido sem evidência; itens acima são lacunas ou riscos a validar.

## Próximos passos recomendados

1. Definir com o negócio a política de custo médio após estorno de compra confirmada.
2. Priorizar e especificar vencimentos/parcelamento, cancelamento/estorno e relatórios antes de implementar tabelas e fluxos novos.
3. Adicionar testes de domínio diretamente em clientes e cobertura de navegador para fluxos críticos de drawer/autocomplete e acessibilidade.
4. Rever precisão de apresentação em limites extremos e documentar versões/ambientes de produção quando existirem dados confiáveis.
