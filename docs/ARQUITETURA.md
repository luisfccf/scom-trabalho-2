# Arquitetura do Sistema

## 1. Visão geral

O sistema foi organizado em camadas para separar as responsabilidades da aplicação.

A principal mudança realizada durante o desenvolvimento do Trabalho Individual II foi retirar o acesso direto ao banco de dados das páginas e dos arquivos responsáveis pelo processamento das requisições.

Dessa forma, páginas, controladores e API não executam mais comandos SQL diretamente. O acesso aos dados ficou concentrado na camada de repositórios.

A estrutura principal pode ser representada da seguinte forma:

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