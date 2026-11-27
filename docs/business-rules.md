# Regras de negócio

Este documento separa o que o código atual evidencia do que o requisito original pede. Evidência principal: `app/Actions`, `app/Http/Requests`, `app/Models`, migrations e testes em `tests/Feature`. O levantamento completo está em [requisitos-funcionais.pdf](requisitos-funcionais.pdf).

## Cadastros

- **Categorias — implementado:** nome obrigatório; listagem, criação e edição na linha (ver `CategoryController` e `CategoryTest`).
  - Situação ativa/inativa (`categories.is_active`), campo que não existia na modelagem inicial (adicionado em 26/11/2026). A listagem filtra por situação (`?situacao=ativas|inativas|todas`) e mostra só as ativas por padrão.
  - Exclusão física só é permitida para categoria que **nunca** teve produto. O marcador `categories.has_had_products` é ligado automaticamente pelo model `Product` ao criar um produto ou trocar sua categoria, e nunca é desligado; a migration preencheu as categorias que já tinham produtos. Categorias que já tiveram produto (mesmo que o produto tenha mudado de categoria depois) só podem ser desativadas/ativadas.
  - Categoria com algum produto **ativo** não pode ser desativada: o botão Desativar aparece desabilitado (com explicação em `title`/`aria-label`) e a rota `categories.deactivate` rejeita a operação dentro de transação com lock da categoria. É preciso desativar os produtos antes. A criação de produto não bloqueia a categoria, então uma corrida exata entre as duas operações não é impedida no banco.
  - Categoria inativa não aparece para seleção em novo produto (cadastro ou compra) e é rejeitada na validação. Produto que já usa uma categoria inativa a mantém e pode ser editado sem trocá-la; no editor ela aparece como “(inativa)”. Desativar não altera produtos.
- **Produtos — implementado:** nome, categoria existente, preço de venda, fornecedor opcional, código de barras opcional, custo médio, saldo e ativo/inativo. A exclusão funcional desativa o produto; preço/cadastro podem ser editados sem alterar saldo nem custo médio via formulário de catálogo (o custo médio nunca é editável). Compras podem criar o primeiro cadastro do produto.
  - **Compra inicial (opcional, só no cadastro):** o drawer de novo produto oferece “Registrar compra inicial deste produto” (data, quantidade, custo unitário e desconto total do item). Ativada, torna o fornecedor do produto obrigatório e ativo; `CreateProduct` cria o produto e chama `RegisterPurchase` — o mesmo fluxo da tela de compras, que confirma a compra via `ConfirmPurchase`, gera a entrada de estoque e o custo médio — tudo em uma transação: se a compra falha, o produto não é gravado. A compra aparece normalmente no histórico de compras. Na edição a compra inicial é recusada.
  - **Custo registrado:** o custo médio só é conhecido depois de alguma entrada de compra (`stock_movements.type = purchase_entry`); antes disso a interface mostra “Sem custo registrado” e lucro/margem “—” (catálogo, estoque, detalhes e drawer), em vez de tratar o custo como zero e exibir margem de 100%. `Product::hasRegisteredCost()` / escopo `withRegisteredCost()` centralizam essa regra; `grossProfit()` e `profitMargin()` retornam `null` sem custo registrado. Um custo zero vindo de compra real continua válido.
  - **Resumo comercial:** drawer de produto e bloco “Novo produto” da compra mostram custo unitário líquido, preço, lucro bruto unitário, margem e (no drawer) estoque inicial, recalculados ao digitar por `resources/js/product-pricing.js`. Custo total líquido = quantidade × custo unitário − desconto; custo unitário líquido = custo total ÷ quantidade; lucro bruto = preço − custo unitário líquido; margem = lucro ÷ preço × 100 (margem sobre o preço, não markup). Os cálculos usam inteiros exatos e só arredondam (HALF_UP, 2 casas) na exibição; preço abaixo do custo destaca lucro/margem negativos e mostra alerta.
  - **Envio duplicado:** produtos e compras aceitam `submission_token` único (UUID gerado no formulário); reenviar o mesmo formulário não cria produto, compra ou movimentação novos.
- **Clientes — implementado:** cadastro e edição, busca, perfil com dados, resumo financeiro, histórico de vendas e pagamentos; operações relacionadas não são apagadas por exclusão física.
- **Fornecedores — implementado:** cadastro/edição e ativar/desativar. Produto guarda no máximo um fornecedor opcional, mas cada compra também guarda seu fornecedor.
- **Cuidados:** há uma diferença entre o requisito “produto poderá ser adquirido de fornecedores diferentes” e o campo de fornecedor no produto: o histórico da compra conserva seu fornecedor, porém a relação direta produto-fornecedor é singular.

## Compras e custo médio

- Uma compra informada é criada com fornecedor, data, itens, custo unitário, desconto e total calculado. Pode incluir um ou mais produtos novos, que recebem o fornecedor da compra; o bloco do novo produto não repete fornecedor, quantidade nem custo (vêm do item). `RegisterPurchase` concentra a criação e chama `ConfirmPurchase` na mesma transação, portanto a compra comum já entra confirmada e afeta o estoque. O desconto do item não pode superar quantidade × custo unitário.
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
- Token único por cliente torna reenvio da mesma submissão idempotente. Pagamento registrado pelo perfil do cliente é aplicado às vendas concluídas com saldo em aberto na ordem do menor saldo atual (empate: venda mais antiga, depois menor ID), quitando cada uma antes de passar à próxima e abatendo parcialmente a última; `PlanPaymentDistribution` calcula essa ordem tanto para a prévia do drawer quanto para o registro, e o registro recusa a operação se a distribuição mudou desde a prévia (assinatura `distribution_signature`). Pagamento registrado na tela da venda vai só para aquela venda, limitado ao saldo dela. Pagamentos anteriores nunca são redistribuídos. Uma única entrada financeira pode criar várias alocações.
- O valor deve ser positivo, data não futura, método permitido e não pode exceder o saldo total aberto do cliente. Excesso rejeita toda a transação; não há crédito excedente.
- Saldo de venda é total menos somatório de alocações. Status financeiro derivado: pendente, parcialmente paga ou quitada. Venda não concluída não tem saldo reportado pelo método atual.
- Perfil do cliente mostra resumo de comprado/recebido/em aberto e organiza abas Compras, Contas em aberto, Pagamentos e Cadastro (`?secao=`). Ao abrir o perfil sem seção, a aba Compras já vem aberta com todas as compras do cliente; parâmetro ausente ou inválido cai em `compras`. Pagamentos com alocações ficam em `secao=pagamentos`. A busca por contas em aberto é no perfil, não há rota dedicada de clientes devedores.
- Não existe fluxo de estorno de pagamento; não há flag/status de pagamento cancelado.

## Segurança, cargos e acesso (implementado em 27/11/2026)

Evidência: `app/Models/Cargo.php`, `app/Models/User.php`, `app/Actions/RegisterUser.php`, `app/Actions/RevokeUserSessions.php`, `app/Http/Controllers/UserController.php`, `PasswordController`, middlewares `EnsureUserIsActive`/`EnsurePasswordIsChanged`, gates em `AppServiceProvider`, migrations de 27/11/2026 e os testes `SecurityUsersTest`, `PasswordChangeTest`, `CargoMigrationTest` e `CreateUserCommandTest`.

- **Cargos:** cada usuária tem exatamente um cargo (`users.cargo_id`, obrigatório). Existem dois, entregues pelas migrations (não por seeder): **Root** (`root`) e **Administrador** (`administrador`). O código identifica cargos pelo slug, nunca pelo ID. Não há tela de gestão de cargos, e um cargo em uso não pode ser excluído (FK `restrict`).
- **Matriz de permissões:**

  | Recurso | Root | Administrador |
  | --- | --- | --- |
  | Funcionalidades comerciais e operacionais (cadastros, compras, vendas, estoque, pagamentos, dashboard e futuros relatórios) | sim | sim |
  | Módulo Segurança › Usuários (listar, cadastrar, editar nome/e-mail, desativar/reativar, redefinir senha) | sim | não (HTTP 403, menu oculto) |
  | Alterar a própria senha (com a senha atual) | sim | sim |
  | Alterar o próprio cargo/situação ou de outra conta | não | não |
  | Alterar, desativar ou redefinir a senha da conta Root pela interface | não | não |
  | Criar Root | só pelo comando `app:create-user --role=root` | não |

- **Root único:** no máximo uma conta Root, garantido no banco pelo índice único parcial `users_single_root_unique` (vale também para execuções concorrentes) e verificado antes, com lock no cargo, por `RegisterUser`. O comando recusa um segundo Root antes de pedir a senha.
- **Usuárias existentes antes do módulo:** a migration preservou todos os registros e atribuiu o cargo Administrador (nunca Root), mantendo o acesso operacional. `ativo = true` e sem troca obrigatória de senha.
- **Cadastro pela interface (Root):** nome completo, e-mail único (gravado em minúsculas), cargo (apenas Administrador), situação e senha inicial com confirmação. Política de senha compartilhada com o comando (`PasswordRules`: mínimo de 10 caracteres, confirmação). Quem é cadastrada pela interface **deve trocar a senha no primeiro acesso** (`must_change_password`).
- **Gerenciamento (Root):** editar nome/e-mail (edição na linha; campos de cargo, situação ou senha enviados junto são ignorados), desativar/reativar, redefinir senha (ação independente, em drawer próprio) e consultar o último acesso. Não há exclusão definitiva de usuárias, para preservar os históricos (vendas, compras, pagamentos e movimentações referenciam `user_id`).
- **Desativação:** impede novos logins (mensagem específica só para quem digitou a senha correta; senha errada recebe a mensagem genérica) e encerra as sessões: apaga as linhas da usuária em `sessions` (driver `database`), troca o `remember_token` e `EnsureUserIsActive` derruba qualquer sessão restante na requisição seguinte, em qualquer driver.
- **Redefinição administrativa de senha:** grava a nova senha, exige a troca no próximo acesso e encerra as sessões da usuária. Sessões em outros drivers são invalidadas pelo `AuthenticateSession` (hash da senha mudou).
- **Troca da própria senha:** exige a senha atual, a nova deve ser diferente da atual e seguir a política. Encerra as sessões de outros aparelhos e mantém a sessão atual. Enquanto a troca obrigatória estiver pendente, qualquer rota autenticada redireciona para `/minha-senha` (JSON recebe 403).
- **Último acesso:** `users.last_login_at` é gravado a cada login bem-sucedido.
- **Cadastro público:** não existe rota de registro.
- **Autorização:** sempre no backend. As rotas `security.users.*` passam pelo gate `manage-users` (Root ativo), e cada ação sobre uma conta pelo gate `manage-user` (alvo não pode ser Root). Respostas não expõem senha nem hash (`#[Hidden]`).

### Histórico de operações e proposta de auditoria (planejado, não implementado)

Já registram a responsável: `sales.user_id`, `purchases.user_id`, `payments.user_id` e `stock_movements.user_id` (entradas e estornos de compra, saídas por venda e movimentações manuais com motivo). Não registram quem fez: confirmação de compra (só `confirmed_at`), cancelamento de compra (pendente não deixa rastro; confirmada deixa as movimentações de estorno), edições de cadastro e preço (produtos, clientes, fornecedores, categorias), ativações/desativações e as ações do módulo Segurança.

Proposta para uma etapa futura: tabela `auditoria` (append-only) com `user_id`, ação (`purchase.cancelled`, `product.price_changed`, `user.deactivated`, `user.password_reset` etc.), entidade (`tipo` + `id`), valores anteriores e novos em JSON (sem senhas ou hashes), IP e data. A gravação ficaria nas Actions e nos controllers, dentro da mesma transação da operação. Para compras, acrescentar `confirmed_by`/`cancelled_by` e `cancelled_at`. Exibir no módulo Segurança com filtros por pessoa, ação e período, e definir uma política de retenção.

## Requisitos ainda planejados ou incompletos

Os itens abaixo constam no PDF, mas não têm implementação completa evidenciada pelo conjunto atual de migrations, rotas, Actions e testes:

- parcelamento com número de parcelas, vencimentos e pagamentos parciais por parcela (RF012); não há entidade de parcela nem vencimentos;
- cancelamento de venda com estorno/devolução e tratamento dos pagamentos (RF009/RN005);
- distribuição manual de pagamento, vencimentos e inadimplência; hoje a alocação automática é por ordem cronológica;
- relatório dedicado de clientes devedores com vencidos/datas de vencimento (RF014);
- relatórios gerenciais, rankings, baixa saída, taxas de cartão/repasses e atalho WhatsApp (RF018–RF024);
- estorno de pagamento com histórico identificável.

Não introduza os itens planejados por inferência: defina fluxo, persistência, invariantes, migração e testes antes de afirmar suporte.
