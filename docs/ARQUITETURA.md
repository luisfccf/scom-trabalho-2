# Arquitetura do Sistema

## 1. Visão geral

O sistema do Trabalho Individual II foi organizado em camadas para separar as responsabilidades da aplicação.

Durante a evolução do projeto, o acesso direto ao banco de dados foi retirado das páginas, controladores e da API.

A estrutura principal ficou:

```text
Interface
    ↓
Lógica da aplicação
    ↓
Repositories
    ↓
PDO
    ↓
MySQL
```

Essa organização facilita a manutenção do sistema e evita que consultas SQL fiquem espalhadas por diferentes arquivos.

---

## 2. Camada de interface

A camada de interface é responsável pela interação com o usuário.

Entre os principais arquivos estão:

```text
login.php
area-treinador.php
area-admin.php
editar-treinador.php
```

Também fazem parte dessa camada as páginas relacionadas ao cadastro e à apresentação do conteúdo do sistema.

Esses arquivos exibem:

- formulários;
- informações dos treinadores;
- opções administrativas;
- mensagens para o usuário;
- links de navegação.

A interface não executa consultas SQL diretamente.

---

## 3. Camada de aplicação

A camada de aplicação recebe e processa as ações realizadas pelos usuários.

Alguns dos principais arquivos são:

```text
processar-login.php
processar-cadastro.php
processar-edicao.php
processar-exclusao.php
logout.php
api/treinadores.php
includes/autenticacao-persistente.php
```

Essa camada é responsável por tarefas como:

- receber dados enviados pelos formulários;
- validar dados;
- verificar autenticação;
- verificar permissões;
- verificar tokens CSRF;
- controlar sessões;
- controlar cookies;
- definir códigos HTTP;
- registrar eventos em logs;
- chamar os repositories para acessar o banco.

Os comandos SQL não ficam nessa camada.

---

## 4. Validações

As validações compartilhadas estão principalmente no arquivo:

```text
includes/validacoes.php
```

Esse módulo contém funções utilizadas em diferentes partes da aplicação.

Entre os dados validados estão:

- nome;
- e-mail;
- nível;
- perfil de acesso;
- experiência.

A validação é realizada no backend, não dependendo somente das regras existentes no HTML ou JavaScript.

---

## 5. Camada de acesso aos dados

O acesso ao banco foi centralizado na pasta:

```text
repositories/
```

Atualmente existem dois repositories principais.

### 5.1 TreinadorRepository

Arquivo:

```text
repositories/TreinadorRepository.php
```

É responsável pelas operações relacionadas aos treinadores.

Entre elas:

- buscar treinador por ID;
- buscar treinador por e-mail;
- listar treinadores;
- verificar duplicidade de e-mail;
- cadastrar treinador;
- atualizar treinador;
- excluir treinador.

As páginas e a API utilizam esse repository quando precisam acessar informações da tabela `treinadores`.

### 5.2 TokenPersistenteRepository

Arquivo:

```text
repositories/TokenPersistenteRepository.php
```

É responsável pelas operações relacionadas ao recurso "Manter-me conectado".

Entre suas funções estão:

- salvar token persistente;
- localizar token pelo selector;
- excluir token por selector;
- excluir token por ID;
- excluir tokens anteriores de um treinador;
- remover tokens expirados.

As consultas relacionadas à tabela `tokens_persistentes` ficam concentradas nesse repository.

---

## 6. Banco de dados

A aplicação utiliza MySQL como banco de dados relacional.

A conexão é realizada através de PDO no arquivo:

```text
config/conexao.php
```

O banco utilizado é:

```text
pokemon_scom
```

As principais tabelas são:

```text
treinadores
tokens_persistentes
```

O script para criação da estrutura está disponível em:

```text
database/banco.sql
```

O banco utiliza recursos como:

- chave primária;
- chave estrangeira;
- `UNIQUE`;
- `NOT NULL`;
- `CHECK`;
- índices;
- `ON DELETE CASCADE`.

A tabela `tokens_persistentes` possui uma chave estrangeira para a tabela `treinadores`.

---

## 7. Configuração através de .env

As configurações de conexão com o banco não ficam diretamente no código-fonte.

O sistema utiliza:

```text
.env
```

com variáveis como:

```text
DB_HOST
DB_NAME
DB_USER
DB_PASS
```

O arquivo `.env` não é enviado ao GitHub.

Existe também:

```text
.env.example
```

para indicar quais variáveis precisam ser configuradas.

---

## 8. Fluxo de autenticação

O processo principal de login funciona da seguinte forma:

```text
login.php
    ↓
processar-login.php
    ↓
TreinadorRepository
    ↓
Banco de dados
    ↓
password_verify()
    ↓
Sessão autenticada
```

A senha não é armazenada em texto puro.

O sistema utiliza:

```php
password_hash()
password_verify()
```

Após a autenticação correta, também é utilizado:

```php
session_regenerate_id(true);
```

para reduzir riscos relacionados à fixação de sessão.

---

## 9. Login persistente

O sistema possui a opção:

```text
Manter-me conectado
```

Quando ela é utilizada, o sistema cria:

- um `selector`;
- um token secreto.

O token secreto não é salvo diretamente no banco.

É armazenado apenas seu hash SHA-256.

Fluxo simplificado:

```text
processar-login.php
        ↓
TokenPersistenteRepository
        ↓
tokens_persistentes
```

O cookie do navegador contém o selector e o token.

Quando não existe uma sessão ativa, o sistema pode tentar restaurar o login através de:

```text
includes/autenticacao-persistente.php
```

O fluxo é:

```text
Cookie persistente
        ↓
autenticacao-persistente.php
        ↓
TokenPersistenteRepository
        ↓
TreinadorRepository
        ↓
Sessão restaurada
```

No logout, o token persistente também é removido.

---

## 10. Controle de acesso

O sistema possui dois perfis:

```text
treinador
admin
```

### Treinador

Pode acessar sua área autenticada.

### Administrador

Pode acessar a área administrativa e realizar operações de gerenciamento dos treinadores.

As rotas administrativas verificam:

```php
$_SESSION['perfil_acesso']
```

antes de permitir o acesso.

Dessa forma, um usuário autenticado com perfil `treinador` não pode utilizar funções exclusivas de `admin`.

---

## 11. API

A aplicação possui uma API localizada em:

```text
api/treinadores.php
```

Os métodos implementados são:

```text
GET
POST
PUT
DELETE
```

Exemplos:

```text
GET /api/treinadores.php
GET /api/treinadores.php?id=9
POST /api/treinadores.php
PUT /api/treinadores.php?id=9
DELETE /api/treinadores.php?id=9
```

A API é responsável por:

- autenticação;
- autorização;
- validação dos dados;
- verificação CSRF;
- códigos HTTP;
- respostas JSON.

O acesso ao banco é feito pelo:

```text
TreinadorRepository
```

Fluxo simplificado:

```text
Requisição HTTP
       ↓
api/treinadores.php
       ↓
Validação e autorização
       ↓
TreinadorRepository
       ↓
PDO
       ↓
MySQL
       ↓
Resposta JSON
```

A documentação detalhada dos endpoints está em:

```text
docs/API.md
```

---

## 12. Segurança

O projeto utiliza diferentes mecanismos de segurança.

Entre eles estão:

- prepared statements com PDO;
- proteção contra SQL Injection;
- `password_hash()` e `password_verify()`;
- sessões PHP;
- regeneração do ID da sessão após login;
- proteção CSRF;
- controle de acesso por perfil;
- validação de dados no backend;
- escape de conteúdo exibido em HTML;
- cookie persistente com `HttpOnly`;
- `SameSite=Lax`;
- token persistente armazenado através de hash;
- mensagens de erro que não expõem detalhes internos.

Durante o desenvolvimento também foram testados:

```text
Acesso sem autenticação
Acesso sem permissão
SQL Injection
XSS
CSRF
```

---

## 13. Tratamento de erros

O sistema utiliza diferentes códigos HTTP de acordo com o tipo de erro.

Entre os utilizados estão:

```text
400 - Requisição inválida
401 - Não autenticado
403 - Acesso negado
404 - Recurso não encontrado
405 - Método não permitido
409 - Conflito
422 - Dados inválidos
500 - Erro interno
```

Erros internos não exibem diretamente informações como:

- mensagem da exceção do banco;
- consulta SQL;
- stack trace;
- detalhes internos do servidor.

As mensagens apresentadas ao usuário são simplificadas.

---

## 14. Logs

O sistema possui um mecanismo de logs em:

```text
includes/logger.php
```

O arquivo local utilizado é:

```text
logs/app.log
```

Entre os eventos registrados estão:

```text
LOGIN_SUCESSO
FALHA_AUTENTICACAO
CRIACAO_ENTIDADE
ERRO_INTERNO
```

Os registros possuem data e hora e podem auxiliar na identificação de eventos importantes ou problemas da aplicação.

---

## 15. Testes

As validações possuem testes automatizados em:

```text
tests/testes-validacoes.php
```

Os testes possuem casos válidos e inválidos.

Existe também um teste controlado para geração de erro interno:

```text
tests/teste-erro-interno.php
```

Esse arquivo é restrito à execução pelo terminal.

---

## 16. Benefícios da arquitetura adotada

A organização em camadas trouxe benefícios como:

- centralização das consultas SQL;
- menor repetição de código;
- melhor organização;
- maior facilidade de manutenção;
- separação entre interface e banco de dados;
- maior facilidade para testes;
- melhor controle das regras de segurança;
- maior facilidade para evolução futura do sistema.

Antes da refatoração, diferentes páginas e controladores realizavam consultas SQL diretamente.

Após a reorganização, o acesso aos dados ficou concentrado nos repositories.

A separação final pode ser resumida por:

```text
Interface
    ↓
Aplicação
    ↓
Repositories
    ↓
PDO
    ↓
MySQL
```

Essa estrutura foi utilizada para atender à necessidade de separação entre interface, lógica da aplicação e acesso aos dados no Trabalho Individual II.