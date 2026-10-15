# Documentação da API - Mundo Pokémon

## Visão geral

A API permite realizar operações CRUD sobre os treinadores cadastrados no sistema.

URL base:

```text
http://localhost/T2-SCOM/scom-trabalho-2/api/treinadores.php
```

A API utiliza sessão PHP para autenticação.

Os endpoints são restritos ao perfil `admin`.

As respostas são retornadas no formato JSON.

Para operações que modificam dados (`POST`, `PUT` e `DELETE`), é necessário enviar o token CSRF no cabeçalho:

```text
X-CSRF-Token
```

---

# 1. Listar treinadores

## Método

```text
GET
```

## URL

```text
/api/treinadores.php
```

## Exemplo de resposta

Status:

```text
200 OK
```

```json
{
  "sucesso": true,
  "total": 2,
  "dados": [
    {
      "id": 9,
      "nome": "Luis",
      "email": "usuario@exemplo.com",
      "perfil_acesso": "admin",
      "nivel": 1,
      "regiao": "galar",
      "experiencia": "iniciante",
      "criado_em": "2026-10-09 21:17:56"
    }
  ]
}
```

---

# 2. Buscar treinador por ID

## Método

```text
GET
```

## URL

```text
/api/treinadores.php?id=9
```

## Parâmetros

`id`: identificador numérico do treinador.

## Resposta de sucesso

Status:

```text
200 OK
```

```json
{
  "sucesso": true,
  "dados": {
    "id": 9,
    "nome": "Luis",
    "email": "usuario@exemplo.com",
    "perfil_acesso": "admin",
    "nivel": 1,
    "regiao": "galar",
    "experiencia": "iniciante"
  }
}
```

## Treinador inexistente

Status:

```text
404 Not Found
```

```json
{
  "sucesso": false,
  "mensagem": "Treinador não encontrado."
}
```

---

# 3. Criar treinador

## Método

```text
POST
```

## URL

```text
/api/treinadores.php
```

## Cabeçalhos

```text
Content-Type: application/json
X-CSRF-Token: TOKEN_DA_SESSAO
```

## Exemplo de requisição

```json
{
  "nome": "Treinador API",
  "email": "treinador.api@exemplo.com",
  "senha": "teste123",
  "nivel": 10,
  "regiao": "kanto",
  "experiencia": "iniciante",
  "data_nascimento": "2000-01-10",
  "horario_preferido": "18:00",
  "motivacao": "Quero me tornar um grande treinador."
}
```

O perfil criado pela API é definido como `treinador`.

A senha é armazenada utilizando `password_hash()`.

## Resposta de sucesso

Status:

```text
201 Created
```

```json
{
  "sucesso": true,
  "mensagem": "Treinador criado com sucesso.",
  "dados": {
    "id": 20,
    "nome": "Treinador API",
    "email": "treinador.api@exemplo.com",
    "perfil_acesso": "treinador",
    "nivel": 10,
    "regiao": "kanto",
    "experiencia": "iniciante"
  }
}
```

## Dados inválidos

Status:

```text
422 Unprocessable Entity
```

```json
{
  "sucesso": false,
  "mensagem": "Existem dados inválidos.",
  "erros": [
    "E-mail inválido."
  ]
}
```

## E-mail duplicado

Status:

```text
409 Conflict
```

```json
{
  "sucesso": false,
  "mensagem": "E-mail já cadastrado."
}
```

---

# 4. Atualizar treinador

## Método

```text
PUT
```

## URL

```text
/api/treinadores.php?id=20
```

## Cabeçalhos

```text
Content-Type: application/json
X-CSRF-Token: TOKEN_DA_SESSAO
```

## Exemplo de requisição

```json
{
  "nome": "Treinador API Editado",
  "email": "treinador.api@exemplo.com",
  "nivel": 25,
  "regiao": "johto",
  "experiencia": "intermediario",
  "data_nascimento": "2000-01-10",
  "horario_preferido": "18:00",
  "motivacao": "Treinador atualizado através da API."
}
```

## Resposta de sucesso

Status:

```text
200 OK
```

```json
{
  "sucesso": true,
  "mensagem": "Treinador atualizado com sucesso.",
  "dados": {
    "id": 20,
    "nome": "Treinador API Editado",
    "email": "treinador.api@exemplo.com",
    "nivel": 25,
    "regiao": "johto",
    "experiencia": "intermediario"
  }
}
```

---

# 5. Excluir treinador

## Método

```text
DELETE
```

## URL

```text
/api/treinadores.php?id=20
```

## Cabeçalho

```text
X-CSRF-Token: TOKEN_DA_SESSAO
```

## Resposta de sucesso

Status:

```text
200 OK
```

```json
{
  "sucesso": true,
  "mensagem": "Treinador excluído com sucesso.",
  "dados": {
    "id": 20,
    "nome": "Treinador API Editado"
  }
}
```

O sistema impede que o administrador autenticado exclua a própria conta através da API.

---

# Autenticação e autorização

Caso o usuário não esteja autenticado:

Status:

```text
401 Unauthorized
```

```json
{
  "sucesso": false,
  "mensagem": "Usuário não autenticado."
}
```

Caso esteja autenticado sem perfil de administrador:

Status:

```text
403 Forbidden
```

```json
{
  "sucesso": false,
  "mensagem": "Acesso negado."
}
```

---

# Proteção CSRF

As operações `POST`, `PUT` e `DELETE` exigem um token CSRF válido.

Caso o token seja inexistente ou inválido:

Status:

```text
403 Forbidden
```

```json
{
  "sucesso": false,
  "mensagem": "Token CSRF inválido."
}
```

---

# Outros códigos HTTP

ID inválido:

```text
400 Bad Request
```

JSON inválido:

```text
400 Bad Request
```

Método HTTP não suportado:

```text
405 Method Not Allowed
```

Erro inesperado no servidor:

```text
500 Internal Server Error
```

---

# Métodos implementados

```text
GET     → leitura
POST    → criação
PUT     → atualização
DELETE  → exclusão
```

A API utiliza consultas parametrizadas com PDO para acesso seguro ao banco de dados e retorna respostas padronizadas em JSON.