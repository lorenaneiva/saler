# Regras de negócio

Este documento separa o que o código atual evidencia do que o requisito original pede. Evidência principal: `app/Actions`, `app/Http/Requests`, `app/Models`, migrations e testes em `tests/Feature`. O levantamento completo está em [requisitos-funcionais.pdf](requisitos-funcionais.pdf).

## Cadastros

- **Categorias — implementado:** nome obrigatório; listagem, criação, edição na linha e exclusão apenas quando não há produtos relacionados (ver `CategoryController` e `CategoryTest`).
- **Produtos — implementado:** nome, categoria existente, preço de venda, fornecedor opcional, código de barras opcional, custo médio, saldo e ativo/inativo. A exclusão funcional desativa o produto; preço/cadastro podem ser editados sem alterar saldo nem custo médio via formulário de catálogo. Compras podem criar o primeiro cadastro do produto.
- **Clientes — implementado:** cadastro e edição, busca, perfil com dados, resumo financeiro, histórico de vendas e pagamentos; operações relacionadas não são apagadas por exclusão física.
- **Fornecedores — implementado:** cadastro/edição e ativar/desativar. Produto guarda no máximo um fornecedor opcional, mas cada compra também guarda seu fornecedor.
- **Cuidados:** há uma diferença entre o requisito “produto poderá ser adquirido de fornecedores diferentes” e o campo de fornecedor no produto: o histórico da compra conserva seu fornecedor, porém a relação direta produto-fornecedor é singular.

## Compras e custo médio

- Uma compra informada é criada com fornecedor, data, itens, custo unitário, desconto e total calculado. Pode incluir produto novo. O controller atual chama `ConfirmPurchase` dentro da transação de criação, portanto a compra comum já entra confirmada e afeta o estoque.
- Compra confirmada soma cada quantidade ao saldo e registra movimento vinculado ao item, usuário e custo unitário efetivo. O valor total do item é `custo unitário × quantidade − desconto`; portanto o desconto informado por item reduz o custo efetivo total.
- O novo custo médio ponderado é `(custo médio anterior × quantidade anterior + total do item) ÷ quantidade nova`, arredondado para 4 casas HALF_UP com `Brick\Math\BigDecimal`. O custo médio exibido como moeda não deve ser usado para recalcular o valor interno.
- Também há estado pendente e endpoint de confirmação; confirmação só aceita compra pendente e é idempotente quando já confirmada. Esse caminho existe, embora a criação atual confirme imediatamente.
- Cancelar compra pendente apenas muda o estado. Cancelar compra confirmada tenta estornar as entradas registradas. O cancelamento falha integralmente se o saldo atual não comportar a reversão. O código não recalcula o custo médio depois do estorno — ponto que requer decisão de domínio antes de modificar, pois a média histórica não pode ser inferida só pelo saldo atual.
- O cancelamento preserva registros e cria movimentos de reversão; não é exclusão física.

## Estoque

- Compra confirmada aumenta o saldo; venda concluída reduz; movimentos registram produto, usuário, data, tipo, quantidade delta e custo de referência.
- Entrada/saída manual exige motivo registrado. O campo de justificativa é opcional no código (limite de 500 caracteres), apesar do RF006 exigir justificativa para ajustes manuais; ajuste armazena quantidade anterior e contada. Esta é uma divergência explícita entre requisito e implementação.
- Movimento manual não pode levar saldo abaixo de zero nem ultrapassar o limite inteiro aceito. Entrada/saída manual não altera custo médio.
- Perda manual calcula valor histórico usando o custo unitário do movimento (custo médio no registro); venda e compra preservam vínculos aos itens correspondentes.
- Não há autorização/flag para permitir estoque negativo. Ajuste genérico legado não é aceito pelo formulário atual.
- Não há fluxo de devolução de venda ou cancelamento de venda nas rotas atuais.

## Vendas e margem

- Cada venda pertence a um cliente e usuário, tem data, itens, desconto e observação opcional. A escrita é transacional. Cada produto deve estar ativo, aparecer uma vez e ter estoque suficiente; qualquer falha impede gravação parcial.
- O preço de venda vigente e o custo médio de cada produto são copiados para o item na hora da venda. Quantidade é descontada e movimento de saída é gravado. Alterar catálogo depois não reescreve esses snapshots.
- Subtotal é preço × quantidade; total é subtotal agregado menos desconto da venda. O desconto não pode exceder o subtotal. Valores financeiros são decimal de 2; custo histórico do item é decimal de 4.
- `Product::profitMargin()` calcula `(preço de venda − custo médio) ÷ preço de venda × 100`, arredondado a 2 casas HALF_UP. Preço zero resulta em margem indisponível. Esta é margem sobre preço de venda, não markup sobre custo.
- O status operacional da venda tem apenas `completed` no código observado. Não há cancelamento de venda implementado.

## Pagamentos e vendas a prazo

- Pagamento pertence a um cliente e usuário; guarda valor, data, método, observação e token de submissão. Métodos existentes: dinheiro, Pix, cartão de crédito, cartão de débito, transferência e outros. Não existe parcela como método de pagamento.
- Token único por cliente torna reenvio da mesma submissão idempotente. O pagamento é aplicado às vendas concluídas mais antigas primeiro, por data e ID. Uma única entrada financeira pode criar várias alocações.
- O valor deve ser positivo, data não futura, método permitido e não pode exceder o saldo total aberto do cliente. Excesso rejeita toda a transação; não há crédito excedente.
- Saldo de venda é total menos somatório de alocações. Status financeiro derivado: pendente, parcialmente paga ou quitada. Venda não concluída não tem saldo reportado pelo método atual.
- Perfil do cliente mostra resumo de comprado/recebido/em aberto e lista vendas abertas/históricos e pagamentos com alocações. A busca por contas em aberto é no perfil, não há rota dedicada de clientes devedores.
- Não existe fluxo de estorno de pagamento; não há flag/status de pagamento cancelado.

## Requisitos ainda planejados ou incompletos

Os itens abaixo constam no PDF, mas não têm implementação completa evidenciada pelo conjunto atual de migrations, rotas, Actions e testes:

- parcelamento com número de parcelas, vencimentos e pagamentos parciais por parcela (RF012); não há entidade de parcela nem vencimentos;
- cancelamento de venda com estorno/devolução e tratamento dos pagamentos (RF009/RN005);
- distribuição manual de pagamento, vencimentos e inadimplência; hoje a alocação automática é por ordem cronológica;
- relatório dedicado de clientes devedores com vencidos/datas de vencimento (RF014);
- relatórios gerenciais, rankings, baixa saída, taxas de cartão/repasses e atalho WhatsApp (RF018–RF024);
- estorno de pagamento com histórico identificável.

Não introduza os itens planejados por inferência: defina fluxo, persistência, invariantes, migração e testes antes de afirmar suporte.
