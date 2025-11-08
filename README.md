# Agenda API (Laravel + Sanctum)

API simples de contatos com autenticação via token (Bearer). Stack: Laravel 12, Sanctum, MySQL.

__Link do vídeo da apresentação do teste: https://youtu.be/BsesJC2BuJg__

## Requisitos
- PHP 8.2+, Composer, MySQL 8 (ou Docker)
- .env com `APP_KEY` e credenciais do `DB`

## Setup local (sem Docker)
1. Instalar dependências:
   - composer install
2. Configurar .env:
   - APP_KEY: execute `php artisan key:generate`
   - Envs `DB_*` conforme o MySQL
3. Migrar e preparar:
   - php artisan migrate
   - php artisan storage:link
4. Subir:
   - php artisan serve
   - Health: GET http://localhost:8000/up

## Autenticação
- Bearer Token (Sanctum Personal Access Token)
- Headers necessários:
  - Authorization: Bearer SEU_TOKEN
  - Accept: application/json (em requisições que não exijam `multipart/form-data`)

## Endpoints

Auth
- POST /api/register
  - body: { name, email, password, password_confirmation }
- POST /api/login
  - body: { email, password }
  - resp: { token, token_type: "Bearer", user }
- POST /api/logout (auth)
- GET /api/me (auth)

Contatos (auth)
- GET /api/contacts?per_page=5&q=marc
  - q busca substring em name/email e dígitos em phone
- GET /api/contacts/{id}
- POST /api/contacts
  - JSON: { name, phone, email? }
  - ou multipart (upload de foto):
    - fields: name, phone, email?, photo(file)
- PUT /api/contacts/{id}
  - JSON quando sem arquivo
  - Multipart com arquivo: usar method spoof
    - FormData: _method=PUT, name, phone, email?, photo(file)
- DELETE /api/contacts/{id}

Exemplos
````bash
# Registrar
curl -sX POST http://localhost:8000/api/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@mail.com","password":"secret123","password_confirmation":"secret123"}'

# Login
TOKEN=$(curl -sX POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"alice@mail.com","password":"secret123"}' | jq -r '.data.token')

# Listar
curl -s "http://localhost:8000/api/contacts?per_page=5&q=mar" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" | jq

# Criar com upload
curl -sX POST http://localhost:8000/api/contacts \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -F "name=Marcelo" -F "phone=82999999999" -F "email=marcelo@email.com" \
  -F "photo=@/caminho/foto.jpg"

# Atualizar com arquivo (method spoofing)
curl -sX POST http://localhost:8000/api/contacts/2 \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -F "_method=PUT" -F "name=Marcelo Jr" -F "photo=@/caminho/novo.jpg"
````

## Respostas e paginação
- Estrutura comum:
  - success (bool), message (opcional), data, meta, links
- Paginação: per_page (1–100), meta.links retornados no índice
- Exemplo:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "João Silva",
            "phone": "28302390293",
            "email": null,
            "photo_url": "site-app.com/storage/contacts/imagem.jpg"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 5,
        "total": 1,
        "last_page": 1,
        "from": 1,
        "to": 1 
    },
    "links": {
        "first": "site-app.com/api/contacts?per_page=5&page=1",
        "last": "site-app.com/api/contacts?per_page=5&page=1",
        "prev": null,
        "next": null
    }
}
```

## Imagens (photo_url)
- Servidas em /storage/... pelo Nginx (alias para storage/app/public)
- Produção: use `FILESYSTEM_DISK=public` e mantenha o volume storage compartilhado entre app e web
- Se 404 em /storage/...: confirme arquivo no container web em /var/www/html/storage/app/public/contacts.
