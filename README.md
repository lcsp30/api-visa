# api-visa — API de Vigilância Sanitária

API privada responsável pelo gerenciamento de estabelecimentos (CNPJ/CPF), licenças de funcionamento, controle documental, emissão de requerimentos/protocolos e notificações da Vigilância Sanitária. Serve o front-end web do **Sistema VISA** e opera como único ponto de persistência e regras de negócio do ecossistema.

> 📚 **Documentação técnica completa** em [`docs/ESPECIFICACAO-TECNICA.md`](docs/ESPECIFICACAO-TECNICA.md) — arquitetura, modelo de dados, catálogo de endpoints, fluxos de negócio e segurança.

---

## 1. 🛠️ Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.2+ |
| Framework | Laravel 12 |
| Banco de Dados | MySQL 8 (padrão) / SQLite (dev) |
| Autenticação | Laravel Sanctum (Bearer token) |
| Geração de Documentos | PhpOffice/PhpWord (templates `.docx`) |
| Testes | Pest PHP 4.x |
| Estilo de Código | Laravel Pint |

---

## 2. 📦 Pré-requisitos e Infra Local

- **PHP 8.2+** com extensões: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`
- **Composer 2**
- **MySQL 8** ou **MariaDB 10.6+** (ou SQLite para dev rápido)
- **Node.js + NPM** (para assets front-end via Vite)

**Alternativa Docker (Laravel Sail):**
```bash
./vendor/bin/sail up -d
```

---

## 3. 🔑 Configuração e Variáveis de Ambiente

```bash
cp .env.example .env
php artisan key:generate
```

### Variáveis essenciais do `.env`

```env
# Aplicação
APP_NAME=api-visa
APP_ENV=local
APP_KEY=                          # Gerado automaticamente
APP_URL=http://localhost:8000

# Banco de Dados (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_visa
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (domínios do front-end que consomem a API)
SANCTUM_STATEFUL_DOMAINS=localhost:8000

# Sessão, Cache e Filas (database driver — dispensa Redis em dev)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### SQLite para dev rápido (sem MySQL)

```env
DB_CONNECTION=sqlite
# O banco database/database.sqlite é criado automaticamente pelo composer setup
```

---

## 4. 🚀 Como Rodar e Migrations

### Instalação rápida

```bash
git clone https://github.com/lcsp30/api-visa.git
cd api-visa
composer setup
```

O script `composer setup` executa:
1. `composer install`
2. Cópia de `.env.example` → `.env` (se não existir)
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install && npm run build`

### Passo a passo manual

```bash
composer install
cp .env.example .env
php artisan key:generate

# Edite .env com as credenciais do banco e rode:
php artisan migrate

# (Opcional) Dados de teste (categorias e documentos obrigatórios)
php artisan db:seed

# Subir servidor
php artisan serve
# API disponível em http://localhost:8000
```

### Subir servidor + fila + Vite simultaneamente

```bash
composer dev
```

---

## 5. 📑 Documentação da API

### Onde consultar os endpoints

| Recurso | Local |
|---------|-------|
| **Catálogo completo** (request/response, query params, regras) | [`docs/ESPECIFICACAO-TECNICA.md`](docs/ESPECIFICACAO-TECNICA.md) — Seção 4 |
| **Rotas em código** | `routes/api.php` |
| **Listar todas as rotas** | `php artisan route:list` |

### Resumo dos endpoints

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| `POST` | `/api/login` | ❌ | Login (retorna token) |
| `POST` | `/api/register` | ❌ | Registro de usuário |
| `POST` | `/api/logout` | ✅ | Revoga token |
| `*` | `/api/user` | ✅ | CRUD de usuários |
| `*` | `/api/estabelecimentos_cnpj` | ✅ | CRUD CNPJ |
| `*` | `/api/estabelecimentos_cpf` | ✅ | CRUD CPF |
| `*` | `/api/doc` | ✅ | CRUD de documentos |
| `POST` | `/api/criarDocsNovoAno` | ✅ | Cria docs para novo ano |
| `GET` | `/api/licenca` | ✅ | Gera licença CNPJ (.docx) |
| `GET` | `/api/licenca_cpf` | ✅ | Gera licença CPF (.docx) |
| `GET` | `/api/gerar_requerimento{_cnpj}/{id}` | ✅ | Gera requerimento (.docx) |
| `GET` | `/api/gerarProtocolo/{id}` | ✅ | Gera protocolo (.docx) |
| `POST` | `/api/intimacao_constatacao` | ✅ | Intimação/constatação |
| `*` | `/api/estabelecimentos_notificados` | ✅ | CRUD de notificados |
| `GET` | `/api/metricas` | ✅ | Dashboard |

> Legenda: `*` = `GET`, `POST`, `PUT`, `DELETE` (apiResource)

### Fluxo de autenticação

1. `POST /api/register` com `name`, `name_user`, `password`
2. `POST /api/login` com `name_user` e `password`
3. Use o token no header: `Authorization: Bearer {token}`

---

## 6. 🧪 Testes

```bash
# Rodar todos os testes
php artisan test

# Com cobertura (requer Xdebug)
php artisan test --coverage

# Arquivo específico
php artisan test tests/Feature/AuthTest.php
```

**Configuração:** O banco de testes usa SQLite em memória. Verifique em `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Cobertura atual:** 20 testes (Pest PHP) — autenticação, CRUD de estabelecimentos, upload de documentos e métricas.

---

## 🏗️ Arquitetura (Resumo)

```
app/
├── Enums/               # Enumerações (Situacao, DivisaoTecnica, Tipo)
├── Helpers/              # Formatação CPF/CNPJ, resposta padronizada
├── Http/
│   ├── Controllers/      # 7 Controllers (camada de entrada fina)
│   ├── Requests/         # Form Requests (validação)
│   └── Resources/        # API Resources (serialização JSON)
├── Models/               # 13 Models Eloquent
├── Repositories/         # 8 Repositories (acesso a dados)
├── Services/             # 5 Services (regras de negócio)
└── Providers/

database/
├── migrations/           # 17 migrations
└── seeders/              # 5 seeders

routes/
└── api.php               # 40 rotas

storage/app/public/
├── documentos/           # Arquivos anexados (PDF, imagens)
│   ├── licencas/         # Licenças geradas (.docx)
│   ├── protocolos/       # Protocolos gerados (.docx)
│   └── requerimentos/    # Requerimentos gerados (.docx)
└── templates/            # Templates Word (.docx)
```

### Padrões utilizados

- **Repository Pattern**: Desacopla acesso a dados
- **Service Layer**: Centraliza regras de negócio (licenças, intimações, docs)
- **Form Requests**: Validação nos Controllers
- **API Resources**: Serialização consistente

---

## 🔒 Segurança

- **Autenticação**: Laravel Sanctum (Bearer token stateless)
- **Senhas**: Bcrypt (12 rounds)
- **Rate Limiting**: Login e registro limitados a 5 tentativas/minuto
- **CORS**: Configurável via `config/cors.php`

> ⚠️ Consulte a [documentação técnica](docs/ESPECIFICACAO-TECNICA.md) para vulnerabilidades conhecidas e recomendações de correção.