# Trabalho Individual II - SCOM

Projeto desenvolvido para a disciplina **SCOM**, dando continuidade à aplicação web Mundo Pokémon.

Nesta segunda etapa, o foco do projeto está no desenvolvimento do backend, persistência de dados, autenticação, controle de acesso, segurança, API e organização da arquitetura da aplicação.

## Autor

**Luís Felipe Cunha Canno Ferreira**

## Objetivo

O objetivo do Trabalho Individual II é transformar a aplicação desenvolvida anteriormente em um sistema web com backend funcional.

A aplicação permite cadastrar e gerenciar treinadores, realizar autenticação de usuários, controlar diferentes perfis de acesso e disponibilizar operações através de uma API.

## Tecnologias utilizadas

- PHP
- MySQL
- PDO
- HTML5
- CSS3
- JavaScript
- XAMPP
- Git
- GitHub

O backend foi desenvolvido em PHP sem utilização de framework.

## Principais funcionalidades

- Cadastro de treinadores.
- Persistência das informações em MySQL.
- Login de usuários.
- Senhas armazenadas utilizando hash.
- Sessões PHP.
- Opção "Manter-me conectado".
- Logout.
- Área autenticada para treinadores.
- Área administrativa.
- Perfis de acesso `treinador` e `admin`.
- Listagem de treinadores.
- Edição de treinadores.
- Exclusão de treinadores.
- Validação de dados no backend.
- Proteção contra CSRF.
- Consultas preparadas com PDO.
- Escape de conteúdo exibido em HTML.
- API com operações CRUD.
- Logs da aplicação.
- Testes automatizados.
- Configuração através de `.env`.
- Separação da camada de acesso aos dados.

## Arquitetura

O projeto foi organizado buscando separar as responsabilidades da aplicação.

Fluxo simplificado:

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

As páginas e arquivos responsáveis pelo processamento das requisições não executam consultas SQL diretamente.

O acesso ao banco está concentrado na pasta:

```text
repositories/
```

Os principais repositories são:

```text
repositories/TreinadorRepository.php
repositories/TokenPersistenteRepository.php
```

Uma descrição mais detalhada está disponível em:

```text
docs/ARQUITETURA.md
```

## Estrutura principal

```text
scom-trabalho-2/
│
├── api/
│   └── treinadores.php
│
├── assets/
│
├── config/
│   └── conexao.php
│
├── css/
│   └── styles.css
│
├── database/
│   └── banco.sql
│
├── docs/
│   ├── API.md
│   └── ARQUITETURA.md
│
├── evidencias/
│
├── includes/
│   ├── autenticacao-persistente.php
│   ├── cabecalho.php
│   ├── logger.php
│   ├── rodape.php
│   └── validacoes.php
│
├── repositories/
│   ├── TokenPersistenteRepository.php
│   └── TreinadorRepository.php
│
├── tests/
│   ├── teste-erro-interno.php
│   └── testes-validacoes.php
│
├── area-admin.php
├── area-treinador.php
├── editar-treinador.php
├── login.php
├── logout.php
├── processar-cadastro.php
├── processar-edicao.php
├── processar-exclusao.php
├── processar-login.php
├── .env.example
├── .gitignore
└── README.md
```

## Como executar

### 1. Requisitos

É necessário possuir:

- Apache
- PHP
- MySQL

Durante o desenvolvimento foi utilizado o **XAMPP**.

### 2. Clonar o repositório

Dentro da pasta desejada:

```bash
git clone https://github.com/luisfccf/scom-trabalho-2.git
```

### 3. Configurar o banco

Inicie no XAMPP:

```text
Apache
MySQL
```

Acesse:

```text
http://localhost/phpmyadmin/
```

Utilize o script:

```text
database/banco.sql
```

para criar a estrutura necessária.

O banco utilizado pela aplicação é:

```text
pokemon_scom
```

## Configuração do `.env`

O projeto utiliza variáveis de ambiente para os dados de conexão com o banco.

Existe um arquivo de exemplo:

```text
.env.example
```

Crie uma cópia chamada:

```text
.env
```

No PowerShell:

```powershell
Copy-Item .env.example .env
```

Exemplo:

```env
DB_HOST=localhost
DB_NAME=pokemon_scom
DB_USER=root
DB_PASS=
```

O arquivo `.env` não é enviado ao GitHub.

## Executando no XAMPP

Neste ambiente de desenvolvimento, o projeto está localizado em:

```text
C:\xampp\htdocs\T2-SCOM\scom-trabalho-2
```

Com Apache e MySQL iniciados, a aplicação pode ser acessada em:

```text
http://localhost/T2-SCOM/scom-trabalho-2/
```

## Banco de dados

A aplicação utiliza MySQL para persistência.

As principais tabelas são:

```text
treinadores
tokens_persistentes
```

A tabela `treinadores` armazena os usuários e suas informações.

A tabela `tokens_persistentes` é utilizada pela funcionalidade:

```text
Manter-me conectado
```

O banco utiliza recursos como:

- chave primária;
- chave estrangeira;
- `UNIQUE`;
- `NOT NULL`;
- restrições de domínio.

O script SQL está disponível em:

```text
database/banco.sql
```

## Autenticação

O sistema possui autenticação através de e-mail e senha.

As senhas são protegidas utilizando:

```php
password_hash()
password_verify()
```

Após o login correto, a sessão é regenerada utilizando:

```php
session_regenerate_id(true);
```

## Login persistente

O usuário pode selecionar:

```text
Manter-me conectado
```

Nesse caso, um token aleatório é criado.

O banco armazena somente o hash desse token.

As operações relacionadas ao login persistente estão concentradas em:

```text
repositories/TokenPersistenteRepository.php
```

O cookie utiliza mecanismos como:

```text
HttpOnly
SameSite=Lax
```

## Controle de acesso

Existem dois perfis:

```text
treinador
admin
```

### Treinador

Possui acesso à área autenticada do sistema.

### Administrador

Além da área autenticada, pode acessar a área administrativa e gerenciar os treinadores.

O sistema também impede o acesso direto às páginas administrativas por usuários sem a permissão adequada.

## API

A API está disponível em:

```text
api/treinadores.php
```

Métodos implementados:

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

As operações que alteram dados utilizam proteção CSRF através do cabeçalho:

```text
X-CSRF-Token
```

A documentação dos endpoints está disponível em:

```text
docs/API.md
```

## Segurança

Foram implementadas medidas de segurança no backend, incluindo:

- prepared statements utilizando PDO;
- proteção contra SQL Injection;
- proteção CSRF;
- validação de dados no servidor;
- escape de conteúdo HTML;
- hash seguro de senhas;
- controle de sessão;
- regeneração do ID da sessão;
- controle de acesso por perfil;
- proteção de rotas administrativas;
- tokens persistentes armazenados através de hash;
- mensagens de erro sem exposição de detalhes internos.

Também foram realizados testes envolvendo:

```text
Acesso sem autenticação
Acesso sem permissão
SQL Injection
XSS
CSRF
```

## Validações

As funções compartilhadas de validação estão em:

```text
includes/validacoes.php
```

Entre as informações verificadas estão:

- nome;
- e-mail;
- nível;
- perfil de acesso;
- experiência.

## Testes automatizados

Os testes automatizados de validação estão em:

```text
tests/testes-validacoes.php
```

Para executar:

```powershell
C:\xampp\php\php.exe .\tests\testes-validacoes.php
```

Os testes possuem casos válidos e inválidos.

## Logs

O sistema possui registro de eventos através de:

```text
includes/logger.php
```

O arquivo de log utilizado localmente é:

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

## Documentação

A documentação adicional está disponível na pasta:

```text
docs/
```

Arquivos principais:

```text
docs/API.md
docs/ARQUITETURA.md
```

## Git e GitHub

O projeto utiliza Git para controle de versão.

As diferentes funcionalidades foram desenvolvidas e registradas através de branches e commits ao longo do desenvolvimento.

Repositório:

```text
https://github.com/luisfccf/scom-trabalho-2
```

## Uso de Inteligência Artificial

A ferramenta ChatGPT, da OpenAI, foi utilizada como apoio durante o desenvolvimento do Trabalho Individual II.

O uso envolveu principalmente:

- esclarecimento de dúvidas;
- apoio em PHP e MySQL;
- auxílio na implementação da API;
- autenticação e controle de acesso;
- mecanismos de segurança;
- testes;
- organização da arquitetura;
- resolução de erros;
- Git e GitHub;
- apoio na documentação.

As sugestões foram revisadas, adaptadas e testadas pelo estudante antes de serem incorporadas ao projeto.

O uso de inteligência artificial também será declarado no relatório técnico da atividade.

## Autor

Luís Felipe Cunha Canno Ferreira
