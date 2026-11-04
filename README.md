# Reserva de máquinas

Aplicação para agendar máquinas agrícolas, com backend Laravel, frontend Angular e banco SQLite. Permite criar reservas, filtrar por máquina e status, confirmar, cancelar e concluir agendamentos.

## Requisitos

- PHP compatível com `backend/composer.json`, com a extensão PDO SQLite.
- Composer.
- Node.js compatível com a versão do Angular do projeto e npm.

Os comandos abaixo usam PowerShell no Windows com Laravel Herd. Sem Herd, use `composer` e `php` no lugar de `herd composer` e `herd php`.

## Instalação

Na primeira instalação, a partir da raiz do projeto:

```powershell
cd backend
herd composer install
Copy-Item .env.example .env
```

Configure o `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DB_URL=
```

Crie o arquivo do banco, caso ainda não exista, e prepare a aplicação:

```powershell
if (-not (Test-Path .\database\database.sqlite)) {
    New-Item -ItemType File .\database\database.sqlite | Out-Null
}
herd php artisan key:generate
herd php artisan config:clear
herd php artisan migrate --seed
```

Os seeders cadastram as máquinas e os clientes iniciais.

Instale o frontend:

```powershell
cd ..\frontend
npm.cmd install
```

## Execução

Em um terminal, dentro de `backend`:

```powershell
herd php artisan serve --host=127.0.0.1 --port=8000
```

Em outro terminal, dentro de `frontend`:

```powershell
npm.cmd start
```

- Frontend: http://localhost:4200
- API: http://127.0.0.1:8000/api

O frontend utiliza esse endereço da API. A configuração de CORS deve permitir a origem do frontend e os métodos GET, POST, PATCH e OPTIONS.

## Endpoints

| Método | Rota | Operação |
| --- | --- | --- |
| GET | `/api/machines` | Listar máquinas |
| GET | `/api/clients` | Listar clientes |
| GET | `/api/reservations` | Listar reservas |
| POST | `/api/reservations` | Criar reserva |
| PATCH | `/api/reservations/{reservation}/status` | Alterar status |

A listagem aceita `machine_id` e `status` como filtros e é ordenada pelo início. Exemplo: `/api/reservations?machine_id=1&status=pending`.

Para testar a API, envie `Accept: application/json`. No POST e PATCH, use também `Content-Type: application/json`.

## Regras

- Máquina e cliente precisam existir.
- O início não pode estar no passado. O término deve ser depois do início e a duração máxima é de sete dias.
- Toda reserva é criada como `pending`.
- Reservas `pending` e `confirmed` bloqueiam períodos sobrepostos da mesma máquina. Uma reserva pode começar exatamente quando outra termina.
- Uma pendente pode ser confirmada ou cancelada.
- Uma confirmada pode ser concluída somente depois do término, ou cancelada com pelo menos 24 horas de antecedência do início.
- Reservas canceladas liberam o período. Outras transições são recusadas.

## Testes

Antes de executar, confira o `phpunit.xml`: os testes de banco devem usar SQLite em memória (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), com `APP_ENV=testing` e `DB_URL` vazio.

Dentro de `backend`:

```powershell
herd php artisan config:clear
herd php artisan test
```

Para conferir a compilação do frontend, dentro de `frontend`:

```powershell
npm.cmd run build
```

## Decisões e limitações

As validações e regras de reserva ficam no `ReservationController`. Os models definem os relacionamentos e a conversão das datas. No Angular, as chamadas HTTP e o estado da tela ficam no `app.ts`, para manter a implementação simples. As datas são normalizadas para UTC; a interface utiliza o horário local do computador.

O projeto usa seeders para máquinas e clientes e não inclui autenticação nem os itens opcionais, como CRUDs, paginação, agenda ou sugestão de horário livre. A interface tem apresentação simples. O uso de transações não substitui a validação de concorrência com requisições simultâneas.

Com mais tempo, separaria as responsabilidades do componente Angular, ampliaria os testes e validaria o comportamento sob concorrência.
