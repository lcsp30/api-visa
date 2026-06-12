# Especificação Técnica — API VISA (Vigilância Sanitária)

> **Versão:** 1.0  
> **Data:** 12/06/2026  
> **Repositório:** [api-visa](https://github.com/lcsp30/api-visa)  
> **Stack:** Laravel 12 + PHP 8.2 + MySQL 8

---

## 1. Visão Geral e Domínio de Negócio

### 1.1 O que é o Sistema VISA

O **VISA** (Vigilância Sanitária) é um sistema de gestão utilizado por secretarias municipais/estaduais de saúde para controlar o ciclo completo de fiscalização sanitária de estabelecimentos comerciais e profissionais liberais.

A API `api-visa` é o backend que serve um front-end React SPA, operando como único ponto de persistência e regras de negócio do ecossistema.

### 1.2 Domínio de Negócio

#### Divisões Técnicas

A Vigilância Sanitária atua em **4 divisões técnicas**, cada uma responsável por um escopo específico de fiscalização:

| Sigla | Nome | Escopo de Atuação |
|-------|------|-------------------|
| **DCQA** | Alimentos | Restaurantes, mercados, açougues, padarias, lanchonetes |
| **DCSEP** | Estabelecimentos de Saúde | Clínicas, hospitais, laboratórios, consultórios |
| **DCDM** | Medicamentos | Farmácias, drogarias, distribuidoras de medicamentos |
| **DCSHT** | Saúde do Trabalhador | Empresas com risco ocupacional, indústrias |

#### Tipos de Estabelecimento

Os estabelecimentos fiscalizados dividem-se em dois tipos:

- **CNPJ (Pessoa Jurídica):** Empresas com 15 categorias possíveis (Restaurante, Drogaria, Farmácia, Hospital, etc.)
- **CPF (Pessoa Física / Profissional Liberal):** Profissionais autônomos com 2 categorias (Profissional Liberal e afins)

#### Fluxo de Negócio Resumido

```
CADASTRO → CRIAÇÃO DE DOCS DO ANO → UPLOAD DE DOCUMENTOS → GERAÇÃO DE LICENÇA
                                                                    │
                                              ┌─────────────────────┤
                                              ▼                     ▼
                                    LICENÇA ATIVA            FISCALIZAÇÃO
                                              │               (Intimações/
                                              ▼                Constatações)
                                      VENCIMENTO
                                      (automático)
```

1. **Cadastro:** Servidores administrativos cadastram estabelecimentos com dados completos (CNPJ/CPF, endereço, responsável, categoria, divisão técnica)
2. **Documentos:** A cada ano, são criados registros de documentos obrigatórios por categoria. Os servidores fazem upload dos arquivos comprobatórios
3. **Licença:** Quando documentação está completa, o sistema gera uma licença de funcionamento (.docx) com número sequencial por divisão técnica
4. **Fiscalização:** Intimações (ordens de correção) e constatações (registro de infrações) são emitidas contra estabelecimentos

### 1.3 Funcionalidades Principais

| Funcionalidade | Descrição |
|---------------|-----------|
| **Cadastro de Estabelecimentos** | CRUD completo para CNPJ e CPF com validação de dados |
| **Gestão Documental** | Upload, download e controle de status de documentos obrigatórios |
| **Geração de Licenças** | Emissão de licenças de funcionamento em .docx com numeração sequencial |
| **Geração de Requerimentos** | Formulários de solicitação preenchidos automaticamente em .docx |
| **Geração de Protocolos** | Comprovantes de atendimento em .docx |
| **Intimações e Constatações** | Emissão e finalização de notificações fiscais |
| **Controle de Vencimento** | Atualização automática de situação de estabelecimentos com licenças vencidas |
| **Dashboard** | Métricas consolidadas de cadastros, licenças e notificados |
| **Gestão de Usuários** | CRUD de usuários com níveis de acesso (Admin/Servidor) |

### 1.4 Público-Alvo e Atores

| Ator | Descrição | Nível de Acesso |
|------|-----------|----------------|
| **Servidor Administrativo** | Funcionário da secretaria que opera o dia-a-dia: cadastros, upload de documentos, emissão de licenças | `nivel_acesso = 2` |
| **Administrador do Sistema** | Gerencia usuários do sistema via painel admin | `nivel_acesso = 1` |

> ⚠️ **Nota de segurança:** Atualmente, a distinção Admin/Servidor é aplicada apenas no front-end. O backend não verifica `nivel_acesso` nas rotas — qualquer usuário autenticado pode acessar qualquer endpoint (ver [6. Autenticação e Segurança](#6-autenticação-e-segurança)).

### 1.5 Como Este Documento Está Organizado

| Seção | Conteúdo | Para quem |
|-------|----------|-----------|
| [2. Arquitetura e Stack](#2-arquitetura-e-stack) | Stack, estrutura de diretórios, padrões de código | Desenvolvedores |
| [3. Modelo de Dados](#3-modelo-de-dados) | Diagrama ER, enums, schema completo de todas as tabelas | Desenvolvedores, DBAs |
| [4. Catálogo de Endpoints](#4-catálogo-de-endpoints) | Todas as 40 rotas com request/response | Desenvolvedores front-end, integradores |
| [5. Fluxos de Negócio](#5-fluxos-de-negócio) | Diagrama de estados, regras de licença, intimações | Desenvolvedores, analistas de negócio |
| [6. Autenticação e Segurança](#6-autenticação-e-segurança) | Sanctum, senhas, rate limiting, vulnerabilidades | Desenvolvedores, segurança |
| [7. Guia de Setup e Desenvolvimento](#7-guia-de-setup-e-desenvolvimento) | Como rodar localmente, testes, migrations | Desenvolvedores (onboarding) |

---

## 2. Arquitetura e Stack

### 2.1 Stack Tecnológica

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Linguagem | PHP | 8.2+ |
| Framework | Laravel | 12.x |
| Banco de Dados | MySQL / MariaDB | 8.0 / 10.6+ |
| Autenticação | Laravel Sanctum | 4.x (token API) |
| Geração de Documentos | PhpOffice/PhpWord | dev-master |
| Testes | Pest PHP | 4.x |
| Estilo de Código | Laravel Pint | 1.x |
| Ambiente Docker (opcional) | Laravel Sail | 1.x |

### 2.2 Estrutura de Diretórios

```
api-visa/
├── app/
│   ├── Enums/
│   │   ├── DivisaoTecnicaEnum.php
│   │   ├── SituacaoEstabelecimentoEnum.php
│   │   └── TipoEstabelecimentoEnum.php
│   ├── Helpers/
│   │   ├── ApiResponse.php          # Wrapper de resposta padronizada
│   │   └── FormatadorHelper.php     # Formatação de CPF/CNPJ
│   ├── Http/
│   │   ├── Controllers/             # 7 controllers (camada de entrada)
│   │   │   ├── DashboardController.php
│   │   │   ├── DocumentosController.php
│   │   │   ├── Estabelecimento_cnpjController.php
│   │   │   ├── Estabelecimento_cpfController.php
│   │   │   ├── Estabelecimento_notificadosController.php
│   │   │   └── UserController.php
│   │   ├── Requests/                # Form Requests (validação)
│   │   └── Resources/               # API Resources (serialização)
│   ├── Models/                      # 13 Models Eloquent
│   ├── Providers/                   # Service Providers
│   ├── Repositories/                # 8 Repositories (acesso a dados)
│   └── Services/                    # 5 Services (regras de negócio)
├── bootstrap/
├── config/
├── database/
│   ├── migrations/                  # 17 migrations
│   └── seeders/                     # 5 seeders
├── public/
├── resources/
├── routes/
│   └── api.php                      # 40 rotas (prefixo /api)
├── storage/
│   └── app/public/
│       ├── documentos/              # Arquivos anexados
│       │   ├── licencas/            # Licenças geradas (.docx)
│       │   ├── protocolos/          # Protocolos gerados (.docx)
│       │   └── requerimentos/       # Requerimentos gerados (.docx)
│       └── templates/               # Templates Word (.docx)
└── tests/
```

### 2.3 Padrões de Arquitetura

O projeto adota uma arquitetura em **4 camadas** com responsabilidades bem definidas:

```
┌─────────────────────────────────────────────────────────┐
│                     HTTP Layer                           │
│  Routes (api.php) → Controllers → Form Requests          │
│  Responsável por: roteamento, validação de entrada,      │
│  serialização da resposta (API Resources)                │
├─────────────────────────────────────────────────────────┤
│                   Service Layer                          │
│  Services (5 classes)                                    │
│  Responsável por: regras de negócio, orquestração,       │
│  geração de documentos .docx, cálculo de licenças        │
├─────────────────────────────────────────────────────────┤
│                 Repository Layer                         │
│  Repositories (8 classes)                                │
│  Responsável por: queries SQL, joins, acesso a dados,    │
│  isolamento do ORM Eloquent                              │
├─────────────────────────────────────────────────────────┤
│                    Data Layer                            │
│  Models (13 classes) + Migrations (17)                   │
│  Responsável por: mapeamento objeto-relacional (ORM),    │
│  estrutura e versionamento do banco de dados             │
└─────────────────────────────────────────────────────────┘
```

#### Repository Pattern

Cada agregado de domínio possui um Repository que encapsula queries SQL via Eloquent ou Query Builder. Os Services injetam os Repositories por injeção de dependência no construtor.

**Exemplo — `DocumentosService`:**

```php
public function __construct(
    private DocumentosCnpjRepository $documentosCnpjRepo,
    private DocumentosCpfRepository $documentosCpfRepo,
    private IntimacaoConstatacaoRepository $intimacaoRepo,
    private LicencaRepository $licencaRepo,
    private EstabelecimentoCnpjRepository $estabelecimentoCnpjRepo,
    private EstabelecimentoCpfRepository $estabelecimentoCpfRepo,
) {}
```

**Vantagens observadas no projeto:**
- **Testabilidade:** Repositories podem ser mockados nos testes
- **Separação de responsabilidades:** Controllers finos (só roteiam), Services com lógica, Repositories com queries
- **Reuso:** `LicencaRepository` é consumido tanto por `DocumentosService` quanto por `EstabelecimentoCnpjService`
- **Manutenibilidade:** Mudanças de query afetam apenas o Repository

#### Service Layer

Centraliza as regras de negócio. Principais responsabilidades:

| Service | Responsabilidade |
|---------|-----------------|
| `DocumentosService` | Upload/download de documentos, criação de docs do ano, intimações/constatações, geração de protocolo, listagem de licenças |
| `EstabelecimentoCnpjService` | CRUD CNPJ, resolução de situação, geração de licença e requerimento CNPJ |
| `EstabelecimentoCpfService` | CRUD CPF, resolução de situação, geração de licença e requerimento CPF |
| `EstabelecimentoNotificadosService` | CRUD de notificados |
| `UserService` | CRUD de usuários, autenticação (login/register/logout) |

#### Form Requests

Validação de entrada desacoplada dos Controllers. Cada operação de escrita possui regras de validação específicas (ex: `StoreUserRequest`, `UpdateUserRequest`, `LicencaRequest`).

#### API Resources

Transformação consistente de Models em respostas JSON, garantindo que campos sensíveis (como `password`) nunca vazem nas responses.

### 2.4 Fluxo de uma Requisição Típica

```
1. HTTP Request
   │
   ▼
2. Middleware (auth:sanctum, throttle, cors)
   │
   ▼
3. Route → Controller@method
   │
   ▼
4. Form Request (validação)
   │
   ▼
5. Service (regra de negócio)
   │
   ├── Repository (query/ persistência)
   │   └── Model (ORM)
   │
   └── Retorna dados ou caminho de arquivo
   │
   ▼
6. Controller (resposta HTTP)
   │
   ├── API Resource (serialização JSON)
   └── Storage::download() (arquivos .docx)
   │
   ▼
7. HTTP Response (JSON ou Binary)
```

### 2.5 Relação com o Front-end

O backend (`api-visa`) serve uma **SPA React 18 + Vite** separada. A comunicação ocorre exclusivamente via HTTP com Bearer Token (Sanctum).

```
┌─────────────────────────┐         ┌─────────────────────────┐
│  Frontend (React 18)    │  HTTP   │  Backend (Laravel 12)   │
│                         │ ──────► │                         │
│  • AuthContext (token)  │  JSON   │  • 7 Controllers        │
│  • Axios + interceptors │ ◄────── │  • 5 Services           │
│  • 11 Telas             │  .docx  │  • 8 Repositories       │
│  • 10 Modais            │         │  • 13 Models            │
└─────────────────────────┘         └─────────────────────────┘
```

**Fluxo de autenticação:**
1. Front-end chama `POST /api/login` com `name_user` + `password`
2. Backend retorna `user` + `token` (Sanctum)
3. Front-end armazena token no `AuthContext` (React Context API)
4. Axios interceptor anexa `Authorization: Bearer {token}` em toda requisição
5. Se receber `401`, redireciona para `/login`

**Geração de documentos:**
- Endpoints de geração (.docx) retornam o arquivo binário via `Storage::download()`
- Front-end dispara download via `window.open(url, "_blank")`

### 2.6 Injeção de Dependência e Service Container

O projeto utiliza o **Service Container** do Laravel para resolver automaticamente as dependências. Todos os Services e Repositories são registrados no container e injetados via type-hint nos construtores, sem necessidade de bindings manuais em Service Providers.

### 2.7 Middleware Global

| Middleware | Escopo | Função |
|-----------|--------|--------|
| `auth:sanctum` | Grupo de rotas protegidas | Verifica token Bearer |
| `throttle:5,1` | Rotas de login/register | Limita a 5 tentativas/minuto |
| CORS | Global | Configurado via `config/cors.php` |

### 2.8 Observações sobre a Arquitetura Atual

**Pontos fortes:**
- Separação clara de responsabilidades (Controller → Service → Repository)
- Uso consistente de injeção de dependência
- Testabilidade (mocking de repositories)

**Pontos de atenção:**
- **Duplicação CPF/CNPJ:** A separação em tabelas e camadas distintas para CPF e CNPJ gera ~60% de código duplicado em Models, Repositories, Services, Controllers, Migrations e Seeders. Uma refatoração para tabela única com campo `tipo_pessoa` reduziria significativamente a carga de manutenção
- **`resolverSituacao()` na listagem:** Regras de negócio de atualização de estado são disparadas no momento da consulta (listagem), e não em jobs agendados ou eventos de domínio. Isso acopla a mutação de estado à leitura
- **Ausência de autorização granular:** O campo `nivel_acesso` é armazenado mas não verificado nas rotas — a proteção Admin vs Servidor depende exclusivamente do front-end

---

## 3. Modelo de Dados

### 3.1 Diagrama de Relacionamentos

```
users
  │
  └── (sem FK para estabelecimentos — autenticação isolada)

categorias (CNPJ)                    categorias_cpf (CPF)
  │                                     │
  ├── doc_categoria                     ├── doc_categoria_cpf
  │   (docs obrigatórios por cat)       │   (docs obrigatórios por cat)
  │                                     │
  └── estabelecimentos_cnpj             └── estabelecimentos_cpf
        │                                     │
        ├── documentos_cnpj                   ├── documentos_cpf
        │   (1:N)                             │   (1:N)
        │                                     │
        ├── intimacao_constatacao             ├── intimacao_constatacao_cpf
        │   (1:N, tipo=1 ou 2)                │   (1:N, tipo=1 ou 2)
        │                                     │
        └── controle_licencas                 └── controle_licencas
            (1:1 via estabelecimento_id_cnpj)     (1:1 via estabelecimento_id_cpf)

estabelecimentos_notificados
  └── (tabela isolada, sem FK para estabelecimentos)
```

> **Nota sobre duplicação CPF/CNPJ:** Praticamente todas as tabelas de domínio existem em pares (CNPJ e CPF), com estruturas ~60% idênticas. Trata-se de decisão histórica, não técnica. A refatoração para tabela única com campo `tipo_pessoa` seria desejável mas de alto custo.

---

### 3.2 Enums do Sistema

#### `SituacaoEstabelecimentoEnum` (int)

| Valor | Constante | Label |
|-------|-----------|-------|
| `0` | `PENDENTE` | Pendente |
| `1` | `ATIVO` | Ativo |
| `3` | `VENCIDO` | Vencido |
| `4` | `BLOQUEADO` | Bloqueado |

#### `DivisaoTecnicaEnum` (string)

| Valor | Label |
|-------|-------|
| `DCQA` | DCQA - Alimentos |
| `DCSEP` | DCSEP - Estabelecimentos de Saúde |
| `DCDM` | DCDM - Medicamentos |
| `DCSHT` | DCSHT - Saúde do Trabalhador |

#### `TipoEstabelecimentoEnum` (string)

| Valor | Label |
|-------|-------|
| `cpf` | Pessoa Física (CPF) |
| `cnpj` | Pessoa Jurídica (CNPJ) |

---

### 3.3 Schemas das Tabelas

#### `users`

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `name` | `VARCHAR(255)` | NOT NULL | Nome completo do usuário |
| `name_user` | `VARCHAR(50)` | NOT NULL | Nome de usuário para login |
| `email` | `VARCHAR(255)` | UNIQUE, NULLABLE | E-mail (opcional) |
| `email_verified_at` | `TIMESTAMP` | NULLABLE | Data de verificação do e-mail |
| `password` | `VARCHAR(255)` | NOT NULL | Hash Bcrypt da senha |
| `nivel_acesso` | `INTEGER` | DEFAULT 2 | 1 = Admin, 2 = Servidor padrão |
| `remember_token` | `VARCHAR(100)` | NULLABLE | Token "lembrar-me" |
| `created_at` | `TIMESTAMP` | NULLABLE | Data de criação |
| `updated_at` | `TIMESTAMP` | NULLABLE | Data de atualização |

#### `categorias` (CNPJ)

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id_categoria` | `INTEGER` | PK, AUTO_INCREMENT | Identificador único |
| `nome_categoria` | `VARCHAR(150)` | NULLABLE | Nome da categoria (ex: Restaurante, Drogaria) |

#### `categorias_cpf` (CPF)

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id_categoria` | `INTEGER` | PK, AUTO_INCREMENT | Identificador único |
| `nome_categoria` | `VARCHAR(150)` | NULLABLE | Nome da categoria (ex: Profissional Liberal) |

#### `estabelecimentos_cnpj`

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id` | `INTEGER UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `categoria_id` | `INTEGER` | FK → `categorias.id_categoria` | Categoria do estabelecimento |
| `situacao` | `INTEGER` | DEFAULT 0 | 0=Pendente, 1=Ativo, 3=Vencido, 4=Bloqueado |
| `cnpj` | `CHAR(14)` | UNIQUE, NOT NULL | CNPJ sem pontuação |
| `razao_social` | `VARCHAR(150)` | NOT NULL | Razão social |
| `atividade_principal` | `VARCHAR(200)` | NULLABLE | Descrição da atividade principal |
| `divisao_tecnica` | `VARCHAR(10)` | NULLABLE | DCQA, DCSEP, DCDM ou DCSHT |
| `tipo_estabelecimento` | `VARCHAR(70)` | NULLABLE | Tipo do estabelecimento |
| `nome_fantasia` | `VARCHAR(150)` | NULLABLE | Nome fantasia |
| `natureza_juridica` | `VARCHAR(150)` | NULLABLE | Natureza jurídica |
| `data_inicio_funcionamento` | `DATE` | NULLABLE | Data de início das atividades |
| `insc_estadual` | `VARCHAR(30)` | NULLABLE | Inscrição estadual |
| `insc_municipal` | `VARCHAR(30)` | NULLABLE | Inscrição municipal |
| `cnes` | `VARCHAR(20)` | NULLABLE | Cadastro Nacional de Estabelecimentos de Saúde |
| `nome_responsavel` | `VARCHAR(150)` | NOT NULL | Nome do responsável legal |
| `cpf` | `CHAR(11)` | NULLABLE | CPF do responsável |
| `rg` | `VARCHAR(20)` | NULLABLE | RG do responsável |
| `orgao_expedidor` | `VARCHAR(30)` | NULLABLE | Órgão expedidor do RG |
| `data_expedicao_rg` | `DATE` | NULLABLE | Data de expedição do RG |
| `endereco` | `VARCHAR(180)` | NULLABLE | Logradouro |
| `localidade` | `VARCHAR(150)` | NULLABLE | Localidade |
| `numero_endereco` | `VARCHAR(20)` | NULLABLE | Número do endereço |
| `bairro` | `VARCHAR(100)` | NULLABLE | Bairro |
| `municipio` | `VARCHAR(120)` | NULLABLE | Município |
| `cep` | `CHAR(8)` | NULLABLE | CEP sem pontuação |
| `telefone` | `VARCHAR(20)` | NULLABLE | Telefone |
| `email` | `VARCHAR(150)` | NULLABLE | E-mail |
| `obs` | `TEXT` | NULLABLE | Observações |
| `created_at` | `TIMESTAMP` | NULLABLE | Data de criação |
| `updated_at` | `TIMESTAMP` | NULLABLE | Data de atualização |

#### `estabelecimentos_cpf`

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id` | `INTEGER UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `situacao` | `INTEGER` | DEFAULT 0 | 0=Pendente, 1=Ativo, 3=Vencido, 4=Bloqueado |
| `categoria_id` | `INTEGER` | FK → `categorias_cpf.id_categoria` | Categoria do estabelecimento |
| `tipo_estabelecimento` | `VARCHAR(70)` | NULLABLE | Tipo do estabelecimento |
| `nome` | `VARCHAR(150)` | NOT NULL | Nome do profissional |
| `cpf` | `CHAR(11)` | UNIQUE, NOT NULL | CPF sem pontuação |
| `rg` | `VARCHAR(20)` | NOT NULL | RG |
| `orgao_expedidor` | `VARCHAR(30)` | NOT NULL | Órgão expedidor do RG |
| `data_expedicao_rg` | `DATE` | NOT NULL | Data de expedição do RG |
| `escolaridade` | `VARCHAR(80)` | NULLABLE | Nível de escolaridade |
| `formacao_profissional` | `VARCHAR(120)` | NULLABLE | Formação (ex: Médico, Enfermeiro) |
| `registro_conselho` | `VARCHAR(50)` | NULLABLE | Registro no conselho de classe |
| `especializacao` | `VARCHAR(120)` | NULLABLE | Especialização |
| `nome_fantasia` | `VARCHAR(150)` | NULLABLE | Nome fantasia |
| `atividade_principal` | `VARCHAR(200)` | NULLABLE | Descrição da atividade principal |
| `divisao_tecnica` | `VARCHAR(10)` | NULLABLE | DCQA, DCSEP, DCDM ou DCSHT |
| `data_inicio_funcionamento` | `DATE` | NULLABLE | Data de início das atividades |
| `endereco` | `VARCHAR(180)` | NULLABLE | Logradouro |
| `localidade` | `VARCHAR(150)` | NULLABLE | Localidade |
| `numero_endereco` | `VARCHAR(10)` | NULLABLE | Número |
| `bairro` | `VARCHAR(100)` | NULLABLE | Bairro |
| `complemento_endereco` | `VARCHAR(120)` | NULLABLE | Complemento |
| `municipio` | `VARCHAR(120)` | NULLABLE | Município |
| `cep` | `CHAR(8)` | NULLABLE | CEP sem pontuação |
| `telefone` | `VARCHAR(20)` | NULLABLE | Telefone |
| `email` | `VARCHAR(150)` | NULLABLE | E-mail |
| `obs` | `VARCHAR(1000)` | NULLABLE | Observações |
| `created_at` | `TIMESTAMP` | NULLABLE | Data de criação |
| `updated_at` | `TIMESTAMP` | NULLABLE | Data de atualização |

#### `documentos_cnpj`

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id_documento` | `INTEGER UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `estabelecimento_id` | `INTEGER UNSIGNED` | FK → `estabelecimentos_cnpj.id` ON DELETE CASCADE | Estabelecimento vinculado |
| `ano` | `CHAR(4)` | NULLABLE | Ano de exercício (ex: 2026) |
| `nome_doc` | `VARCHAR(150)` | NOT NULL | Nome do documento |
| `doc_local` | `VARCHAR(200)` | NULLABLE | Caminho relativo do arquivo no storage |
| `doc_fixo` | `TINYINT` | NULLABLE | 1 = fixo (herdado entre anos), 0 = não-fixo |
| `data_doc` | `DATE` | NULLABLE | Data do documento |
| `url` | `VARCHAR(500)` | NULLABLE | URL alternativa |
| `status` | `TINYINT` | NULLABLE | Status do documento |

> **Nota:** A tabela **não possui** `timestamps` (created_at/updated_at). O Model define `public $timestamps = false;`.

#### `documentos_cpf`

Estrutura **idêntica** a `documentos_cnpj`, exceto pela FK:

| Campo | Tipo | Constraints |
|-------|------|-------------|
| `id_documento` | `INTEGER UNSIGNED` | PK, AUTO_INCREMENT |
| `estabelecimento_id` | `INTEGER UNSIGNED` | FK → `estabelecimentos_cpf.id` ON DELETE CASCADE |
| `ano` | `CHAR(4)` | NULLABLE |
| `nome_doc` | `VARCHAR(150)` | NOT NULL |
| `doc_local` | `VARCHAR(200)` | NULLABLE |
| `doc_fixo` | `TINYINT` | NULLABLE |
| `data_doc` | `DATE` | NULLABLE |
| `url` | `VARCHAR(500)` | NULLABLE |
| `status` | `TINYINT` | NULLABLE |

#### `doc_categoria` (CNPJ)

Mapeamento de **documentos obrigatórios por categoria** — define quais documentos cada categoria de estabelecimento precisa apresentar.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `categoria_id` | `INTEGER` | FK → `categorias.id_categoria` | Categoria vinculada |
| `nome_doc` | `VARCHAR(200)` | NOT NULL | Nome do documento obrigatório |
| `fixo` | `TINYINT` | NULLABLE | 1 = documento permanente (herdado entre anos) |

#### `doc_categoria_cpf` (CPF)

Estrutura **idêntica** a `doc_categoria`, exceto pela FK para `categorias_cpf`.

| Campo | Tipo | Constraints |
|-------|------|-------------|
| `categoria_id` | `INTEGER` | FK → `categorias_cpf.id_categoria` |
| `nome_doc` | `VARCHAR(200)` | NOT NULL |
| `fixo` | `TINYINT` | NULLABLE |

#### `controle_licencas`

Registro de licenças emitidas. Usa **FK polimórfica** para suportar CNPJ e CPF na mesma tabela.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `estabelecimento_id_cnpj` | `INTEGER UNSIGNED` | FK → `estabelecimentos_cnpj.id` ON DELETE CASCADE, NULLABLE | Estabelecimento CNPJ |
| `estabelecimento_id_cpf` | `INTEGER UNSIGNED` | FK → `estabelecimentos_cpf.id` ON DELETE CASCADE, NULLABLE | Estabelecimento CPF |
| `ano` | `CHAR(4)` | NOT NULL | Ano da licença |
| `numero_licenca` | `INTEGER` | NOT NULL | Número sequencial (por divisão + ano) |
| `validade` | `DATE` | NOT NULL | Data de validade da licença |

> **Regra:** Apenas um dos campos `estabelecimento_id_cnpj` ou `estabelecimento_id_cpf` é preenchido por registro. A validação é feita em código (Service Layer), não por constraint no banco.

#### `intimacao_constatacao` (CNPJ)

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id_intimacao_constatacao` | `INTEGER UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `estabelecimento_id` | `INTEGER UNSIGNED` | FK → `estabelecimentos_cnpj.id` ON DELETE CASCADE | Estabelecimento vinculado |
| `ano` | `CHAR(4)` | NULLABLE | Ano de exercício |
| `status` | `TINYINT` | NOT NULL | 1 = ativa, 0 = finalizada |
| `tipo` | `INTEGER` | NULLABLE | 1 = Intimação, 2 = Constatação/Advertência |
| `descricao` | `VARCHAR(355)` | NOT NULL | Descrição da intimação/constatação |
| `data_inicial` | `DATE` | NULLABLE | Data de emissão |
| `data_expiracao` | `DATE` | NULLABLE | Prazo para resposta |

#### `intimacao_constatacao_cpf` (CPF)

Estrutura **idêntica** a `intimacao_constatacao`, exceto pela FK para `estabelecimentos_cpf`.

#### `estabelecimentos_notificados`

Tabela auxiliar **isolada**, sem FK para as tabelas de estabelecimentos principais.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Identificador único |
| `nome_estabelecimento` | `VARCHAR(120)` | NULLABLE | Nome do estabelecimento notificado |
| `nome_proprietario` | `VARCHAR(100)` | NULLABLE | Nome do proprietário |
| `contato` | `VARCHAR(11)` | NULLABLE | Telefone de contato |
| `data_notificacao` | `DATE` | NULLABLE | Data da notificação |
| `situacao` | `VARCHAR(100)` | NULLABLE | Situação (texto livre) |

#### Tabelas do Laravel (framework)

| Tabela | Descrição |
|--------|-----------|
| `sessions` | Sessões (driver database) |
| `cache` | Cache (driver database) |
| `cache_locks` | Lock de cache atômico |
| `jobs` | Jobs da fila (driver database) |
| `job_batches` | Lotes de jobs |
| `failed_jobs` | Jobs que falharam |
| `personal_access_tokens` | Tokens Sanctum |
| `password_reset_tokens` | Tokens de reset de senha |

---

## 4. Catálogo de Endpoints

> **Base URL:** `http://{host}:8000/api`  
> **Autenticação:** Bearer Token (Sanctum), exceto rotas públicas.  
> **Content-Type:** `application/json` (exceto uploads: `multipart/form-data`)

### 4.1 Autenticação (Pública)

#### `POST /api/login`

Autentica um usuário e retorna token Sanctum.

**Body (JSON):**
```json
{
  "name_user": "string (obrigatório)",
  "password": "string (obrigatório)"
}
```

**Response (200):**
```json
{
  "user": { "id": 1, "name": "...", "name_user": "...", "nivel_acesso": 2 },
  "token": "1|abc123..."
}
```

**Erros:** `401` credenciais inválidas, `429` rate limit (5 tentativas/min).

---

#### `POST /api/register`

Registra um novo usuário.

**Body (JSON):**
```json
{
  "name": "string (obrigatório)",
  "name_user": "string (obrigatório, único)",
  "password": "string (obrigatório, min:8, confirmado)",
  "nivel_acesso": "integer (opcional, default: 2, valores: 1 ou 2)"
}
```

**Response (201):**
```json
{
  "user": { "id": 2, "name": "...", "name_user": "...", "nivel_acesso": 2 },
  "token": "1|def456..."
}
```

**Erros:** `422` validação, `429` rate limit.

---

### 4.2 Autenticação (Protegida)

#### `POST /api/logout`

Revoga o token atual do usuário autenticado.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{ "message": "Logout realizado com sucesso" }
```

---

### 4.3 Usuários (CRUD)

**Base:** `/api/user` | **Controller:** `UserController` (apiResource)

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/user` | Lista todos os usuários |
| `POST` | `/api/user` | Cria novo usuário |
| `GET` | `/api/user/{id}` | ❌ **Quebrado** — `show()` não implementado |
| `PUT` | `/api/user/{id}` | Atualiza usuário |
| `DELETE` | `/api/user/{id}` | Remove usuário |

#### `GET /api/user`

**Response (200):**
```json
[
  { "id": 1, "name": "Admin", "name_user": "admin", "nivel_acesso": 1, "email": null }
]
```

#### `POST /api/user`

Mesmo body de `/api/register`, mas exige autenticação.

#### `PUT /api/user/{id}`

**Body (JSON):**
```json
{
  "name": "string",
  "name_user": "string",
  "password": "string (opcional, min:8)",
  "nivel_acesso": "integer (1 ou 2)"
}
```

#### `DELETE /api/user/{id}`

**Response (200):**
```json
{ "message": "Usuário deletado com sucesso" }
```

> ⚠️ **Vulnerabilidade:** Não há middleware de autorização por `nivel_acesso`. Qualquer usuário autenticado pode manipular outros usuários.

---

### 4.4 Estabelecimentos CNPJ

**Base:** `/api/estabelecimentos_cnpj` | **Controller:** `Estabelecimento_cnpjController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/estabelecimentos_cnpj` | Lista todos (com join de licença, intimação, categoria) |
| `POST` | `/api/estabelecimentos_cnpj` | Cria estabelecimento |
| `PUT` | `/api/estabelecimentos_cnpj/{id}` | Atualiza estabelecimento |
| `DELETE` | `/api/estabelecimentos_cnpj/{id}` | Remove estabelecimento |
| `GET` | `/api/gerar_requerimento_cnpj/{id}` | Gera requerimento .docx |
| `GET` | `/api/getCategorias` | Lista categorias CNPJ |
| `GET` | `/api/licenca` | Gera licença CNPJ .docx |
| `GET` | `/api/getEstabelecimentos_cnpj/{id}` | Busca estabelecimento por ID |

#### `GET /api/estabelecimentos_cnpj`

Retorna lista com joins que incluem: dados do estabelecimento, licença (`ano_doc`, `validade`, `numero_licenca`, `doc_local`), status da intimação ativa, nome da categoria.

**Response (200):**
```json
[
  {
    "id": 1,
    "cnpj": "12345678000190",
    "razao_social": "Empresa Exemplo LTDA",
    "nome_fantasia": "Exemplo",
    "situacao": 1,
    "categoria_id": 3,
    "divisao_tecnica": "DCQA",
    "ano_doc": "2026",
    "validade": "2027-03-30",
    "numero_licenca": 42,
    "doc_local": "documentos/licencas/licenca_1.docx",
    "status": null,
    "nome_categoria": "Restaurante"
  }
]
```

> **Nota:** O método `resolverSituacao()` é executado durante a listagem, atualizando automaticamente estabelecimentos vencidos ou bloqueados.

#### `POST /api/estabelecimentos_cnpj`

**Body (JSON):** Ver schema em [3.3 `estabelecimentos_cnpj`](#33-schemas-das-tabelas).

Campos obrigatórios: `cnpj` (14 dígitos, único), `razao_social`, `nome_responsavel`, `categoria_id`.

#### `GET /api/gerar_requerimento_cnpj/{id}`

Gera arquivo .docx com dados do estabelecimento + checkboxes do formulário.

**Query params:** Variáveis dinâmicas dos checkboxes (ex: `?item1=X&item2=X`).

**Response:** Download do arquivo .docx.

#### `GET /api/getCategorias`

**Response (200):**
```json
[
  { "id_categoria": 1, "nome_categoria": "Restaurante" },
  { "id_categoria": 2, "nome_categoria": "Drogaria e Ervanaria" }
]
```

#### `GET /api/licenca`

Gera licença de funcionamento .docx.

**Query params:** `id` (estabelecimento), `ano` (ex: 2026), `categoria`.

**Response:** Download do arquivo .docx.

Regras de negócio: ver [5.4 Geração de Licença](#54-geração-de-licença).

#### `GET /api/getEstabelecimentos_cnpj/{id}`

Busca um estabelecimento específico por ID com os mesmos joins da listagem.

---

### 4.5 Estabelecimentos CPF

**Base:** `/api/estabelecimentos_cpf` | **Controller:** `Estabelecimento_cpfController`

Estrutura espelhada ao CNPJ:

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/estabelecimentos_cpf` | Lista todos |
| `POST` | `/api/estabelecimentos_cpf` | Cria estabelecimento CPF |
| `PUT` | `/api/estabelecimentos_cpf/{id}` | Atualiza |
| `DELETE` | `/api/estabelecimentos_cpf/{id}` | Remove |
| `GET` | `/api/gerar_requerimento/{id}` | Gera requerimento CPF .docx |
| `GET` | `/api/getCategorias_cpf` | Lista categorias CPF |
| `GET` | `/api/licenca_cpf` | Gera licença CPF .docx |
| `GET` | `/api/getEstabelecimentos_cpf/{id}` | Busca por ID |

**Diferenças vs CNPJ:**
- `POST` requer `cpf` (11 dígitos), `nome`, `rg`, `orgao_expedidor`, `data_expedicao_rg`
- `gerar_requerimento/{id}` usa template específico (Profissional Liberal ou Autônomo)
- `/api/getCategorias_cpf` retorna 2 categorias (ex: Profissional Liberal)
- Comportamento de listagem, licença e busca é análogo ao CNPJ

---

### 4.6 Documentos

**Base:** `/api/doc` | **Controller:** `DocumentosController`

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/doc` | Lista documentos com joins |
| `POST` | `/api/doc` | Upload de documento |
| `PUT` | `/api/doc/{id}` | ⚠️ Atualiza documento individual |
| `DELETE` | `/api/doc/{id}` | Remove documento + arquivo físico |
| `GET` | `/api/download` | Download de arquivo |
| `PUT` | `/api/doc/alterar` | Altera status em lote |
| `POST` | `/api/intimacao_constatacao` | Cria/finaliza intimação ou constatação |
| `POST` | `/api/criarDocsNovoAno` | Cria docs para novo ano |
| `GET` | `/api/gerarProtocolo/{id}` | Gera protocolo .docx |
| `GET` | `/api/getLicencas` | Lista licenças do ano agrupadas por divisão |

#### `GET /api/doc`

**Query params:** `ano` (obrigatório), `id` (estabelecimento), `tipo` (CPF/CNPJ).

**Response (200):**
```json
[
  {
    "id_documento": 10,
    "estabelecimento_id": 1,
    "ano": "2026",
    "nome_doc": "Alvará de Funcionamento",
    "doc_local": "documentos/1/alvara.pdf",
    "doc_fixo": 1,
    "data_doc": "2025-06-15",
    "url": null,
    "status": 1
  }
]
```

#### `POST /api/doc`

Upload de arquivo.

**Body (multipart/form-data):**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `estabelecimento_id` | integer | ID do estabelecimento |
| `ano` | string(4) | Ano de exercício |
| `nome_doc` | string | Nome do documento |
| `arquivo` | file | Arquivo binário |
| `tipo` | string | `"CPF"` ou `"CNPJ"` |

**Response (201):** Confirmação de upload.

#### `GET /api/download`

**Query params:** `url` — caminho relativo do arquivo no storage.

**Response:** Download do arquivo.

#### `PUT /api/doc/alterar`

Altera status de múltiplos documentos de uma vez.

**Body (JSON):**
```json
{
  "ids": [10, 11, 12],
  "status": 1
}
```

#### `DELETE /api/doc/{id}`

Remove o registro do banco **e o arquivo físico** do storage.

> ⚠️ Documentos fixos referenciados por múltiplos anos terão o ponteiro quebrado nos outros anos.

#### `POST /api/intimacao_constatacao`

Cria ou finaliza uma intimação (tipo=1) ou constatação (tipo=2).

**Body — Criação:**
```json
{
  "id": 1,
  "ano": "2026",
  "tipo": 1,
  "descricao": "Apresentar laudo técnico",
  "data_inicial": "2026-06-01",
  "data_expiracao": "2026-07-01",
  "tipoEstabelecimento": "CNPJ"
}
```

**Body — Finalização:**
```json
{
  "id": 1,
  "finalizar": 1,
  "tipoEstabelecimento": "CNPJ"
}
```

**Response (200):**
```json
{ "Menssagem": "Salvo com sucesso!" }
```
ou
```json
{ "Menssagem": "Finalizado com sucesso!" }
```

> **Nota:** A chave de resposta é `Menssagem` (com 2 's') — manter por compatibilidade com o front-end.

#### `POST /api/criarDocsNovoAno`

Cria registros de documentos para um estabelecimento em um novo ano.

**Body (JSON):**
```json
{
  "id": 1,
  "ano": "2026",
  "id_categoria": 3,
  "tipo": "CNPJ"
}
```

**Response (200):**
```json
{ "Menssagem": "Documentos do Ano de 2026 criados!" }
```

Fluxo detalhado: ver [5.3.1 Criação de Documentos do Ano](#531-criação-de-documentos-do-ano-criardocsnovoano).

#### `GET /api/gerarProtocolo/{id}`

Gera protocolo .docx.

**Query params:** `n` (número do protocolo), `ano`, `id_estabelecimento`, `tipo` (CPF/CNPJ), `itens` (array).

**Response:** Download do arquivo .docx.

#### `GET /api/getLicencas`

Lista licenças do ano corrente, agrupadas por divisão técnica.

**Query params:** `ano` (obrigatório).

**Response (200):**
```json
{
  "DCQA": [ { "id": 1, "numero_licenca": 42, "nome_fantasia": "...", "validade": "..." } ],
  "DCSEP": [ ... ]
}
```

---

### 4.7 Estabelecimentos Notificados

**Base:** `/api/estabelecimentos_notificados` | **Controller:** `Estabelecimento_notificadosController`

CRUD simples (apiResource, exceto `show`). Tabela isolada, sem FK para estabelecimentos principais.

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/estabelecimentos_notificados` | Lista todos |
| `POST` | `/api/estabelecimentos_notificados` | Cria notificado |
| `PUT` | `/api/estabelecimentos_notificados/{id}` | Atualiza |
| `DELETE` | `/api/estabelecimentos_notificados/{id}` | Remove |

**Body (JSON) — POST/PUT:**
```json
{
  "nome_estabelecimento": "string",
  "nome_proprietario": "string",
  "contato": "string (11)",
  "data_notificacao": "date",
  "situacao": "string"
}
```

---

### 4.8 Dashboard

#### `GET /api/metricas`

**Controller:** `DashboardController@index`

**Response (200):**
```json
{
  "totalCadastrados": 1234,
  "licencasAtivas": 567,
  "licencasVencidas": 89,
  "notificados": 45
}
```

| Métrica | Cálculo |
|---------|---------|
| `totalCadastrados` | `COUNT(estabelecimentos_cnpj) + COUNT(estabelecimentos_cpf)` |
| `licencasAtivas` | `COUNT(controle_licencas WHERE validade > NOW())` |
| `licencasVencidas` | `COUNT(controle_licencas WHERE validade < NOW())` |
| `notificados` | `COUNT(estabelecimentos_notificados)` |

> Sem filtros por ano, divisão técnica ou tipo. Consolidado geral simples.

---

### 4.9 Resumo de Rotas

| Método | Rota | Auth | Grupo |
|--------|------|------|-------|
| `POST` | `/api/login` | ❌ | Auth |
| `POST` | `/api/register` | ❌ | Auth |
| `POST` | `/api/logout` | ✅ | Auth |
| `GET` | `/api/user` | ✅ | Usuários |
| `POST` | `/api/user` | ✅ | Usuários |
| `GET` | `/api/user/{id}` | ✅ | ⚠️ Quebrado |
| `PUT` | `/api/user/{id}` | ✅ | Usuários |
| `DELETE` | `/api/user/{id}` | ✅ | Usuários |
| `GET` | `/api/estabelecimentos_cnpj` | ✅ | CNPJ |
| `POST` | `/api/estabelecimentos_cnpj` | ✅ | CNPJ |
| `PUT` | `/api/estabelecimentos_cnpj/{id}` | ✅ | CNPJ |
| `DELETE` | `/api/estabelecimentos_cnpj/{id}` | ✅ | CNPJ |
| `GET` | `/api/gerar_requerimento_cnpj/{id}` | ✅ | CNPJ |
| `GET` | `/api/getCategorias` | ✅ | CNPJ |
| `GET` | `/api/licenca` | ✅ | CNPJ |
| `GET` | `/api/getEstabelecimentos_cnpj/{id}` | ✅ | CNPJ |
| `GET` | `/api/estabelecimentos_cpf` | ✅ | CPF |
| `POST` | `/api/estabelecimentos_cpf` | ✅ | CPF |
| `PUT` | `/api/estabelecimentos_cpf/{id}` | ✅ | CPF |
| `DELETE` | `/api/estabelecimentos_cpf/{id}` | ✅ | CPF |
| `GET` | `/api/gerar_requerimento/{id}` | ✅ | CPF |
| `GET` | `/api/getCategorias_cpf` | ✅ | CPF |
| `GET` | `/api/licenca_cpf` | ✅ | CPF |
| `GET` | `/api/getEstabelecimentos_cpf/{id}` | ✅ | CPF |
| `GET` | `/api/doc` | ✅ | Docs |
| `POST` | `/api/doc` | ✅ | Docs |
| `PUT` | `/api/doc/{id}` | ✅ | Docs |
| `DELETE` | `/api/doc/{id}` | ✅ | Docs |
| `GET` | `/api/download` | ✅ | Docs |
| `PUT` | `/api/doc/alterar` | ✅ | Docs |
| `POST` | `/api/intimacao_constatacao` | ✅ | Docs |
| `POST` | `/api/criarDocsNovoAno` | ✅ | Docs |
| `GET` | `/api/gerarProtocolo/{id}` | ✅ | Docs |
| `GET` | `/api/getLicencas` | ✅ | Docs |
| `GET` | `/api/estabelecimentos_notificados` | ✅ | Notif. |
| `POST` | `/api/estabelecimentos_notificados` | ✅ | Notif. |
| `PUT` | `/api/estabelecimentos_notificados/{id}` | ✅ | Notif. |
| `DELETE` | `/api/estabelecimentos_notificados/{id}` | ✅ | Notif. |
| `GET` | `/api/metricas` | ✅ | Dashboard |

---

## 5. Fluxos de Negócio

### 5.1 Diagrama de Estados do Estabelecimento

O campo `situacao` rege o ciclo de vida de cada estabelecimento, com 4 estados e transições automáticas disparadas pelo método `resolverSituacao()` nos Services:

```
                   ┌──────────────────┐
                   │ 0 = PENDENTE     │ ← Cadastro recém-criado
                   └────────┬─────────┘
                            │ (licença anexada + ano atual)
                            ▼
                   ┌──────────────────┐
                   │ 1 = ATIVO        │ ← Licença com arquivo, validade > hoje
                   └────────┬─────────┘
                            │
            ┌───────────────┴───────────────┐
            │                               │
   (licença sem doc_local          (intimação ativa
    e ano = ano atual)              E situacao = 1)
            │                               │
            ▼                               ▼
   ┌──────────────────┐           ┌──────────────────┐
   │ 0 = PENDENTE     │           │ 4 = BLOQUEADO    │ ← Intimação em aberto
   └──────────────────┘           └────────┬─────────┘
                                           │ (intimação finalizada)
                                           ▼
                                  ┌──────────────────┐
                                  │ 1 = ATIVO        │
                                  └────────┬─────────┘
                                           │
                                    (validade <= hoje)
                                           │
                                           ▼
                                  ┌──────────────────┐
                                  │ 3 = VENCIDO      │ ← Irreversível até renovar
                                  └──────────────────┘
```

| Código | Estado | Gatilho |
|--------|--------|---------|
| **0** | Pendente | Criado no cadastro; ou licença sem arquivo no ano corrente |
| **1** | Ativo | Licença possui `doc_local` preenchido e validade futura |
| **3** | Vencido | `validade <= now()` — verificado automaticamente a cada listagem |
| **4** | Bloqueado | Intimação (`tipo=1` com `status=1`) ativa em estabelecimento ativo |

**Importante:** A transição para Vencido (3) ocorre apenas quando alguém carrega a listagem de estabelecimentos. Não há job agendado (cron) para isso.

---

### 5.2 Cadastro de Estabelecimentos

**CNPJ (Pessoa Jurídica):** `POST /api/estabelecimentos_cnpj`

Campos principais: `cnpj`, `razao_social`, `nome_fantasia`, `atividade_principal`, `divisao_tecnica`, `categoria_id`, `nome_responsavel`, `cpf`, `rg`, `endereco`, `numero_endereco`, `bairro`, `municipio`, `cep`, `telefone`, `email`.

- Pertence a uma das 15 categorias (ex: Restaurante, Drogaria, Farmácia, Hospital)
- Vinculado a uma divisão técnica (DCQA, DCSEP, DCDM, DCSHT)
- `situacao` inicial = `0` (Pendente)

**CPF (Pessoa Física / Profissional Liberal):** `POST /api/estabelecimentos_cpf`

Campos principais: `cpf`, `nome`, `nome_fantasia`, `atividade_principal`, `divisao_tecnica`, `categoria_id`, `formacao_profissional`, `registro_conselho`, `endereco`, `numero_endereco`, `bairro`, `municipio`, `cep`, `telefone`, `email`.

- Pertence a uma das 2 categorias (ex: Profissional Liberal)
- Mesmo vínculo com divisão técnica
- `situacao` inicial = `0` (Pendente)

---

### 5.3 Gestão de Documentos

#### 5.3.1 Criação de Documentos do Ano (`criarDocsNovoAno`)

Quando um servidor inicia o atendimento de um estabelecimento em um novo ano, o endpoint `POST /api/criarDocsNovoAno` é chamado:

1. Recebe `id` do estabelecimento, `ano`, `id_categoria` e `tipo` (CPF/CNPJ)
2. Verifica se já existem documentos para esse estabelecimento no ano informado — se sim, retorna erro
3. Busca a lista de **documentos obrigatórios** da categoria nas tabelas `doc_categoria` / `doc_categoria_cpf`
4. Para cada documento obrigatório:
   - **Documento fixo (`fixo=1`):** Copia a referência (`doc_local`, `data_doc`) do ano anterior. Ambos os registros apontam para o **mesmo arquivo físico** no storage — o arquivo NÃO é duplicado
   - **Documento não-fixo (`fixo=0`):** Cria registro vazio (`doc_local=null`, `data_doc=null`) para upload posterior

> ⚠️ **Risco:** Se um documento fixo for excluído via `DELETE /doc/{id}`, o arquivo físico é removido do storage, quebrando o ponteiro de todos os outros anos que o referenciavam.

#### 5.3.2 Upload e Gestão de Documentos

**Upload:** `POST /api/doc` (multipart/form-data)
- Campos: `estabelecimento_id`, `ano`, `nome_doc`, `arquivo` (binário), `tipo` (CPF/CNPJ)
- O arquivo é salvo em `storage/app/public/documentos/{id_estabelecimento}/`
- `doc_local` armazena o caminho relativo; `status` é atualizado

**Download:** `GET /api/download?url={caminho_relativo}`
- Serve o arquivo via `Storage::disk('public')->download()`

**Alteração de status em lote:** `PUT /api/doc/alterar`
- Permite marcar múltiplos documentos como anexados/pendentes de uma vez

**Exclusão:** `DELETE /api/doc/{id}`
- Remove o registro do banco **e o arquivo físico** do storage
- ⚠️ Documentos fixos referenciados por múltiplos anos serão corrompidos nos outros anos

---

### 5.4 Geração de Licença

#### 5.4.1 Fluxo Principal

O endpoint `GET /api/licenca` (CNPJ) ou `GET /api/licenca_cpf` (CPF) dispara o método `baixarLicenca()`:

1. **Busca licença existente** em `controle_licencas` para o estabelecimento
2. **Calcula o número da licença:**
   - Se já existe licença no **mesmo ano**: mantém o número
   - Se é um **ano novo**: busca `MAX(numero_licenca)` para aquela divisão técnica + ano, e incrementa (+1)
   - Se é **ano anterior**: retorna erro
3. **Calcula a validade:**
   - Drogaria e Ervanaria: 30 de Abril do ano seguinte (4 meses)
   - Demais categorias: 30 de Março do ano seguinte (3 meses)
4. **Preenche o template .docx** com `TemplateProcessor` (PhpOffice/PhpWord), substituindo placeholders como `${nomeEstabelecimento}`, `${cnpj}`, `${numeroLicenca}`, `${anoV}`, `${mesV}`
5. **Salva o .docx gerado** em `storage/app/public/documentos/licencas/`
6. **Dá UPDATE** em `controle_licencas` (relação 1:1 com estabelecimento — o registro anterior é sobrescrito)
7. **Atualiza `situacao`** do estabelecimento para `1` (Ativo)

#### 5.4.2 Regra do Número da Licença

| Parâmetro | Comportamento |
|-----------|--------------|
| **Divisão Técnica** | Cada divisão (DCQA, DCSEP, DCDM, DCSHT) tem sequência própria |
| **Ano** | A sequência reinicia a cada ano |
| **CPF e CNPJ** | Compartilham a mesma tabela `controle_licencas` — sequência unificada por divisão+ano |
| **Primeira licença** | Se não há licenças na divisão/ano, `max()` retorna 0 → número = 1 |
| **Renovação mesmo ano** | Mantém o mesmo número (não incrementa) |
| **Renovação ano seguinte** | Gera novo número sequencial |

> ⚠️ **Race condition:** Não há lock pessimista (`lockForUpdate()`). Duas gerações simultâneas para a mesma divisão no mesmo ano podem produzir números duplicados.

#### 5.4.3 Validade e Vencimento Automático

- **Cálculo:** Feito no momento da geração da licença, com base na categoria
- **Vencimento automático:** A cada carregamento da listagem de estabelecimentos, o método `resolverSituacao()` verifica se `validade <= now()`. Se sim, atualiza `situacao` para `3` (Vencido)
- **Não há cron job** — a verificação só ocorre quando alguém abre a tela de listagem

#### 5.4.4 Renovação de Licença

| Cenário | Comportamento |
|---------|--------------|
| **Mesmo ano** (ex: reemissão por correção) | Mantém o número, sobrescreve o arquivo .docx |
| **Ano seguinte** (ex: licença 2025 → 2026) | Gera novo número sequencial, dá UPDATE no registro existente |
| **Ano anterior** | Bloqueado — retorna erro |

> ⚠️ O histórico de licenças passadas é **perdido** a cada renovação, pois a tabela `controle_licencas` tem relação 1:1 com estabelecimento e o registro é sobrescrito via UPDATE.

---

### 5.5 Intimação e Constatação

Ambas utilizam o mesmo endpoint `POST /api/intimacao_constatacao`, diferenciadas pelo campo `tipo`.

#### 5.5.1 Intimação (`tipo=1`)

- **Propósito:** Ordem formal para o estabelecimento apresentar documentos ou corrigir irregularidade
- **Efeito colateral:** Se o estabelecimento está Ativo (situacao=1), a existência de uma intimação com `status=1` força `situacao=4` (Bloqueado) na próxima listagem
- **Finalização:** `POST /api/intimacao_constatacao` com `finalizar=1` → seta `status=0` → desbloqueia o estabelecimento (volta para situacao=1 na próxima listagem)

#### 5.5.2 Constatação/Advertência (`tipo=2`)

- **Propósito:** Registro de infração constatada, caráter documental
- **Sem efeito colateral:** Constatações **não afetam** a situação do estabelecimento (a query em `listarEstbCnpj()` filtra apenas `tipo=1`)
- **Finalização:** Mesmo mecanismo da intimação (`finalizar=1` → `status=0`)

---

### 5.6 Geração de Documentos Oficiais (.docx)

O sistema gera três tipos de documentos Word a partir de templates armazenados em `storage/app/public/templates/`:

| Documento | Endpoint | Template | Descrição |
|-----------|----------|----------|-----------|
| **Requerimento** | `GET /gerar_requerimento/{id}` | `templete_requerimento_doc_{cnpj/cpf}_{of/au/pl}.docx` | Formulário de solicitação com dados do estabelecimento + checkboxes |
| **Licença** | `GET /licenca` | `templete_licenca.docx` | Licença de funcionamento com número, validade e dados do estabelecimento |
| **Protocolo** | `GET /gerarProtocolo/{id}` | `templete_protocolo.docx` | Comprovante de entrega/atendimento com itens selecionados |

**Mecanismo de geração (TemplateProcessor):**
1. Abre o template .docx com `new TemplateProcessor($caminhoTemplate)`
2. Substitui placeholders `${variavel}` pelos dados do estabelecimento
3. Variáveis não preenchidas recebem espaços em branco (non-breaking spaces `\u{00A0}`)
4. Salva o arquivo gerado no storage e retorna o caminho para download

---

### 5.7 Estabelecimentos Notificados

CRUD auxiliar (`/api/estabelecimentos_notificados`) para uma lista separada de estabelecimentos notificados, sem vínculo direto com as tabelas de estabelecimentos principais. Usado para controle paralelo de notificações.

---

### 5.8 Dashboard de Métricas

`GET /api/metricas` retorna um consolidado geral simples:

```json
{
  "totalCadastrados": 1234,
  "licencasAtivas": 567,
  "licencasVencidas": 89,
  "notificados": 45
}
```

| Métrica | Escopo | Observação |
|---------|--------|------------|
| `totalCadastrados` | Global (todos os tempos) | Soma CPF + CNPJ, sem filtro por ano/situação |
| `licencasAtivas` | Global | Licenças com `validade > hoje` |
| `licencasVencidas` | Global | Inclui licenças de anos passados já vencidas |
| `notificados` | Global | Total de registros em `estabelecimentos_notificados` |

**Não há filtros** por ano, divisão técnica ou tipo de estabelecimento.

---

## 6. Autenticação e Segurança

### 6.1 Modelo de Autenticação

O sistema utiliza **Laravel Sanctum** com autenticação stateless via **Bearer Token**.

- Tokens são armazenados na tabela `personal_access_tokens`
- Cada token possui escopo definido (`auth_token`)
- Não há refresh token — o front-end obtém novo token via login quando o atual expira ou é revogado

**Fluxo de autenticação:**
```
1. POST /api/register  →  Cria usuário + retorna token
2. POST /api/login     →  Autentica e retorna token
3. Authorization: Bearer {token}  →  Header em todas as requisições
4. POST /api/logout    →  Revoga token atual
```

### 6.2 Senhas

- **Hash:** Bcrypt com 12 rounds (configuração padrão do Laravel)
- **Validação:** Mínimo 8 caracteres, confirmação obrigatória no registro
- **Model:** O cast `password => hashed` no Model `User` aplica o hash automaticamente

### 6.3 Rate Limiting

| Rota | Limite | Middleware |
|------|--------|-----------|
| `POST /api/login` | 5 tentativas/minuto | `throttle:5,1` |
| `POST /api/register` | 5 tentativas/minuto | `throttle:5,1` |

Demais rotas não possuem rate limiting explícito.

### 6.4 Níveis de Acesso

| Nível | Valor | Descrição |
|-------|-------|-----------|
| **Admin** | `1` | Gerencia usuários (Painel Admin) |
| **Servidor** | `2` | Operações diárias (cadastro, docs, licenças) — **default** |

> ⚠️ **Vulnerabilidade crítica:** Não há middleware de autorização verificando `nivel_acesso` nas rotas. Qualquer usuário autenticado (nível 1 ou 2) pode acessar todos os endpoints, incluindo `DELETE /api/user/{id}`. A proteção Admin vs Servidor depende exclusivamente do front-end esconder o `PanelAdmin`. Um servidor nível 2 pode, via Postman/curl, manipular ou excluir usuários.

### 6.5 CORS

Configurado via `config/cors.php`. Para produção, ajustar os domínios permitidos (origens do front-end).

### 6.6 Vulnerabilidades Conhecidas

| # | Vulnerabilidade | Impacto | Severidade |
|---|----------------|---------|-----------|
| 1 | **Ausência de autorização por `nivel_acesso`** | Servidor nível 2 pode gerenciar/deletar usuários | 🔴 Alta |
| 2 | **`GET /api/user/{id}` quebrado** | Erro 500 — `show()` não implementado no Controller | 🟡 Média |
| 3 | **Race condition no número da licença** | Licenças duplicadas se dois servidores gerarem simultaneamente para mesma divisão+ano | 🟡 Média |
| 4 | **Exclusão de documento fixo corrompe anos anteriores** | Arquivo físico deletado quebra ponteiros de outros anos | 🟡 Média |
| 5 | **`window.open(url, "_blank")` sem `noopener`** | Risco de tabnabbing no front-end | 🟢 Baixa |
| 6 | **`baseURL` hardcoded no front-end** | IP fixo (`10.11.10.75:8000`) no `api.js` — deveria ser variável de ambiente | 🟢 Baixa |

### 6.7 Recomendações de Segurança

1. **Implementar middleware de autorização** baseado em `nivel_acesso` nas rotas de `/api/user`
2. **Adicionar `lockForUpdate()`** na query de `MAX(numero_licenca)` para prevenir race condition
3. **Implementar `show()`** no `UserController` ou remover a rota do `apiResource`
4. **Adicionar contagem de referências** ou soft delete para documentos fixos compartilhados entre anos
5. **Substituir `window.open`** por `window.open(url, "_blank", "noopener,noreferrer")` no front-end
6. **Mover `baseURL`** para variável de ambiente no front-end

---

## 7. Guia de Setup e Desenvolvimento

### 7.1 Pré-requisitos

- **PHP 8.2+** com extensões: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`
- **Composer 2**
- **MySQL 8** ou **MariaDB 10.6+**
- **Node.js + NPM** (para assets front-end)
- Opcional: **Redis** (cache/queue — não obrigatório em dev)
- Opcional: **Docker** (via Laravel Sail)

### 7.2 Instalação Rápida

```bash
# Clone o repositório
git clone https://github.com/lcsp30/api-visa.git
cd api-visa

# Setup completo (instala dependências, configura .env, gera key, roda migrations)
composer setup
```

O comando `composer setup` executa:
1. `composer install`
2. Cópia de `.env.example` → `.env` (se não existir)
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install && npm run build`

### 7.3 Instalação Passo a Passo

```bash
# 1. Instalar dependências PHP
composer install

# 2. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 3. Configurar banco de dados no .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=api_visa
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Rodar migrations
php artisan migrate

# 5. (Opcional) Popular banco com dados de teste
php artisan db:seed

# 6. Subir servidor
php artisan serve
# API disponível em http://localhost:8000
```

### 7.4 Ambiente com SQLite (Dev Rápido)

Para subir sem MySQL, edite o `.env`:

```env
DB_CONNECTION=sqlite
# DB_DATABASE não precisa ser definido (usa database/database.sqlite)
```

O comando `composer setup` já cria o arquivo `database/database.sqlite`.

### 7.5 Ambiente com Docker (Sail)

```bash
./vendor/bin/sail up -d
```

### 7.6 Variáveis de Ambiente Principais

```env
# Aplicação
APP_NAME=api-visa
APP_ENV=local
APP_KEY=                     # Gerado por php artisan key:generate
APP_URL=http://localhost:8000

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_visa
DB_USERNAME=root
DB_PASSWORD=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8000

# Sessão, Cache e Filas (database driver — não requer Redis em dev)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 7.7 Comando `composer dev`

Sobe simultaneamente: servidor, fila e Vite:

```bash
composer dev
```

Equivalente a executar em paralelo:
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `npm run dev`

### 7.8 Testes

```bash
# Rodar todos os testes
php artisan test

# Rodar com cobertura (requer Xdebug)
php artisan test --coverage

# Rodar arquivo específico
php artisan test tests/Feature/AuthTest.php

# Rodar com detalhes
php artisan test -p --verbose
```

**Cobertura atual:** 20 testes (Pest PHP) cobrindo autenticação, CRUD de estabelecimentos, upload de documentos e métricas. Fluxos críticos como geração de licença, intimações e criação de docs do ano **não possuem cobertura de testes**.

**Configuração do banco de testes:** SQLite em memória (`phpunit.xml`):
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### 7.9 Estilo de Código

```bash
# Corrigir automaticamente o estilo
./vendor/bin/pint

# Verificar sem corrigir
./vendor/bin/pint --test
```

### 7.10 Estrutura do Storage

```
storage/app/public/
├── documentos/           # Arquivos anexados pelos servidores
│   ├── {id}/            # Por estabelecimento
│   ├── licencas/        # Licenças geradas (.docx)
│   ├── protocolos/      # Protocolos gerados (.docx)
│   └── requerimentos/   # Requerimentos gerados (.docx)
└── templates/           # Templates Word (.docx)
    ├── templete_licenca.docx
    ├── templete_licenca_cpf.docx
    ├── templete_protocolo.docx
    ├── templete_requerimento_doc_cnpj_of.docx
    ├── templete_requerimento_doc_cpf_au_of.docx
    └── templete_requerimento_doc_cpf_pl_of.docx
```

> ⚠️ Os templates são essenciais para o funcionamento do sistema. Se ausentes, os endpoints de geração de documentos retornarão erro.

### 7.11 Coleção Insomnia/Postman

Use `routes/api.php` como referência para criar uma coleção. Fluxo:

1. `POST /api/register` com `name`, `name_user`, `password`, `password_confirmation`
2. `POST /api/login` com `name_user` e `password`
3. Use o token retornado no header: `Authorization: Bearer {token}`

### 7.12 Referência Rápida de Comandos

| Comando | Descrição |
|---------|-----------|
| `composer setup` | Setup completo do projeto |
| `composer dev` | Sobe servidor + fila + Vite |
| `php artisan serve` | Servidor de desenvolvimento (porta 8000) |
| `php artisan migrate` | Roda migrations |
| `php artisan migrate:rollback` | Reverte última migration |
| `php artisan db:seed` | Popula banco com dados de teste |
| `php artisan test` | Roda testes |
| `php artisan route:list` | Lista todas as rotas |
| `php artisan make:model Nome -a` | Cria Model + Migration + Controller + Factory + Seeder |
