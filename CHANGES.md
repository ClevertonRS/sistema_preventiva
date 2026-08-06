# Registro de Alterações — Refatoração V2

## Data: 2026-07-26

### Objetivo
Separar a tabela monolítica `preventivas_rede` em modelo dimensional/fato,
permitindo rastrear corretamente 2 técnicos no fluxo de atendimento
(analista vs executor) e classificar fotos por tipo (análise/execução) e momento (antes/depois).

---

### 1. Migration SQL (`assets/migracao_v2.sql`)

**Tabela Dimensão:**
- `preventivas_rede` — **Original, não alterada**. Agora usada apenas como dimensão
  (dados fixos da OS). Status migrado para: `aberta` | `em_atendimento` | `concluida`
- `atendimentos` — Fato: cada ciclo de atendimento
  - `tecnico_analise_id` → FK `usuarios.id` (quem fez a análise)
  - `tecnico_execucao_id` → FK `usuarios.id` (quem executou)
  - `descricao_analise` — descrição do problema identificado
  - `descricao_execucao` — descrição do que foi executado
  - Status: `analise` | `execucao` | `revisao` | `concluido`

**Alterações em `preventivas_arquivos`:**
- `atendimento_id` (FK → `atendimentos.id`)
- `momento` (varchar 10): `antes` | `depois`
- `tipo` agora usado como: `analise` | `execucao`

**Localização agora por etapa (`atendimentos`):**
- `latitude_analise` / `longitude_analise` — registrada quando o técnico faz a análise
- `latitude_execucao` / `longitude_execucao` — registrada quando o técnico executa/conclui
- Ambos os registros são preservados, permitindo visualizar onde cada etapa foi realizada
- A `preventivas_rede` original mantém seus campos `latitude`/`longitude` intactos (não usados pelo novo código)

**Migração de dados:**
- Dados de `preventivas_rede` → `preventivas` (mapeamento de status)
- Dados de `preventivas_rede` com técnico → `atendimentos`
- Arquivos existentes vinculados ao atendimento correspondente

---

### 2. Arquivos Refatorados

#### `dashboard.php`
- Query agora lê de `preventivas` (GROUP BY status)
- Status counts: 'aberta', 'em_atendimento', 'concluida'
- Tasks em revisão agora consultam `atendimentos WHERE status = 'revisao'`

#### `triagem.php`
- Query: `preventivas WHERE status = 'aberta'` (antes: `preventivas_rede WHERE status = 'Triagem'`)

#### `execucao.php`
- Query: JOIN entre `atendimentos` e `preventivas`
- Filtra por `tecnico_analise_id` OU `tecnico_execucao_id` = usuário logado
- Mostra status do atendimento: 'analise', 'execucao', 'revisao'

#### `execucao_detalhe.php`
- Query com JOIN `preventivas` + `atendimentos`
- Arquivos filtrados por `atendimento_id`

#### `revisao.php`
- Query JOIN `atendimentos` + `preventivas` com status 'revisao'
- Filtro por técnico envolvido

#### `revisao_detalhe.php`
- Mantido com a mesma estrutura, agora lê de `preventivas` + `atendimentos`
- Formulário envia `acao = finalizar_revisao`

#### `concluidas.php`
- Query JOIN `atendimentos` + `preventivas` com status 'concluido'
- Exibe `concluido_em` do atendimento

#### `concluidas_detalhe.php`
- JOIN completo com `usuarios` para exibir nome do analista e executor
- Separa `descricao_analise` e `descricao_execucao`
- Fotos exibidas com badge de `tipo` (analise/execucao) e `momento` (antes/depois)

#### `preventivas.php`
- Status atualizados: 'aberta', 'em_atendimento', 'concluida'
- Filtros por slug: aberta, concluida

#### `detalhe_preventiva.php` (principal — refatoração mais complexa)
- Lê de `preventivas` + `atendimentos` (LEFT JOIN)
- **Status 'aberta':**
  - Radio: "Completo" (análise + execução) vs "Apenas Análise"
  - Completo → envia `modo_atendimento=completo` → `aceitar_finalizar`
  - Apenas Análise → envia `modo_atendimento=analise` → `iniciar_analise`
- **Status 'em_atendimento':**
  - Se usuário é analista e status='analise': exibe análise, formulário para assumir execução
  - Se usuário é executor e status='execucao': formulário de finalização
  - Se status='revisao' e usuário envolvido: formulário de reenvio
  - Se não envolvido: mensagem informativa
- **Status 'concluida':** relatório completo com ambas as descrições e fotos classificadas

#### `salvar_preventiva.php` (lógica de ações)
- **`iniciar_analise`**: Cria atendimento com `tecnico_analise_id`, status='analise', preventiva → 'em_atendimento'. Fotos: tipo='analise', momento='antes'
- **`aceitar_finalizar`**: Cria atendimento com ambos os técnicos = usuário, status='concluido', preventiva → 'concluida'. Fotos: tipo='execucao', momento='depois'
- **`assumir_execucao`**: Atualiza atendimento com `tecnico_execucao_id`, descricao_execucao, status='concluido', preventiva → 'concluida'. Fotos: tipo='execucao', momento='depois'
- **`finalizar`**: Atualiza descricao_execucao, status → 'revisao'. Fotos: tipo='execucao', momento='depois'
- **`finalizar_revisao`**: Atualiza descricao_execucao, mantém 'revisao'. Fotos: tipo='execucao', momento='depois'
- Função auxiliar `salvarFotos()`: centraliza o upload

---

### 3. Fluxos Suportados

#### Fluxo 1 técnico (Completo)
1. Preventiva 'aberta' na Triagem
2. Técnico clica "Detalhar" → escolhe "Completo"
3. Preenche descrições + fotos
4. Clique → atendimento criado com mesmo técnico como analista e executor, preventiva 'concluida'

#### Fluxo 2 técnicos
1. Preventiva 'aberta' na Triagem
2. Técnico A → "Apenas Análise" → preenche descrição + fotos → preventiva 'em_atendimento'
3. Técnico B → "Em Execução" → vê o item → "Assumir Execução" → preenche execução + fotos → preventiva 'concluida'

#### Revisão
1. Após finalizar, atendimento vai para 'revisao'
2. Técnico vê em "Revisão" → pode reenviar descrição + fotos

---

### 4. Próximos Passos Sugeridos
- [ ] Executar a migration SQL no banco de produção
- [ ] Adicionar tela de supervisão para aprovar/rejeitar revisões
- [ ] Adicionar filtro "Disponível para Execução" em `execucao.php`
- [ ] Melhorar classificação de fotos (permitir marcar individualmente antes/depois)
- [ ] Testar todos os fluxos com dados reais
