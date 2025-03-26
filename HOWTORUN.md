# Ambiente de Desenvolvimento Local do Adb Payment com Magento 2

Este guia descreve como configurar e rodar rapidamente o ambiente de desenvolvimento local do Magento 2 usando Docker e Makefile.

O objetivo desse ambiente é possibilitar uma maior facilidade no desenvolvimento do plugin Adb Payment do Mercado Pago. Isso funciona mapeando o código do repositório para dentro do ambiente Magento que roda dentro do Docker.

## Pré-requisitos

Certifique-se de que as seguintes dependências estejam instaladas em seu sistema:

- **Docker** (com suporte a Docker Compose)
- **OpenSSL** (para geração de certificados SSL)

## Passos para Rodar

### 1. Configuração do Ambiente

#### a.  Arquivo Hosts

Adicione a seguinte linha ao seu arquivo `hosts`:

```
127.0.0.1 magento.local
```

#### b. Variáveis de Ambiente

Copie o arquivo `.env.sample` para `.env`:

```bash
cp build/.env.sample build/.env
```

O arquivo de ambiente contém todas as variáveis que podem ser personalizadas para a instalação e execução do Magento. Abaixo está a explicação de cada variável e como ela se relaciona com o ambiente:

##### Variáveis de Configuração

- **PHP_VERSION**: Define a versão do PHP que será utilizada no ambiente. As versões disponíveis são 7.2; 7.4; 8.1 e 8.2
- **MAGENTO_VERSION**: Define a versão do Magento 2 a ser instalada. A versão escolhida pode ser qualquer uma que seja suportada por uma das versões do PHP listadas acima, lembrando que podem haver divergências por conta de versões das aplicações, seja do Magento com o PHP seja também do Magento com o banco de dados ou elasticsearch. Um exemplo é que versões do Elastic acima da 8.X não funcionam muito bem com o Magento 2. Outro exemplo é que a versão 2.4.5-p4 do Magento não aceita o MariaDB em versões acima da 10.4.
- **ELASTIC_VERSION**: Define a versão do Elasticsearch. Utilizado para melhorar a busca no Magento.
- **REDIS_VERSION**: Define a versão do Redis, utilizado para cache e armazenamento temporário no Magento.
- **MARIADB_VERSION**: Define a versão do MariaDB, utilizado como banco de dados no Magento.
- **HOST_OS**: Define o sistema operacional do host onde o Docker está rodando. Os valores `linux` e `macos` estão disponíveis.
- **DB_USER**: Define o nome de usuário para o banco de dados MySQL.
- **DB_PASSWORD**: Define a senha para o banco de dados MySQL.
- **DB_ROOT_PASSWORD**: Define a senha de root para o banco de dados MySQL.
- **DB_PORT**: Define a porta do banco de dados MySQL que vai estar disponível para acesso da sua maquina local.
- **APP_PORT**: Define a porta do servidor web (Nginx) onde o Magento será acessado. Por exemplo `magento.local:80` ou `magento.local:8000`
- **ADMIN_FIRSTNAME**: Define o primeiro nome do administrador do Magento.
- **ADMIN_LASTNAME**: Define o sobrenome do administrador.
- **ADMIN_USER**: Define o nome de usuário do administrador. Usado para acesso no admin de Magento.
- **ADMIN_PASSWORD**: Define a senha do administrador. Usado para acesso no admin de Magento.
- **ADMIN_EMAIL**: Define o e-mail do administrador de Magento.

### Como as Variáveis São Usadas

Essas variáveis de ambiente são utilizadas para configurar e personalizar os containers Docker e a instalação do Magento. Por exemplo, a variável `DB_USER` é passada como variável de ambiente para o container do banco de dados MySQL, enquanto a `PHP_VERSION` é usada para escolher a versão correta do PHP no container correspondente.

Além disso, o arquivo `docker-compose.yml` utiliza essas variáveis para configurar serviços essenciais como o banco de dados, Elasticsearch, Redis, Nginx e PHP. Veja um exemplo de como as variáveis são utilizadas para configurar o banco de dados:

```yaml
services:
  database:
    image: mariadb:${MARIADB_VERSION}
    restart: always
    environment:
      MARIADB_USER: ${DB_USER}
      MARIADB_PASSWORD: ${DB_PASSWORD}
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MARIADB_DATABASE: magento
    volumes:
      - magento-database-volume:/var/lib/mysql
    networks:
      - default
    ports:
      - "${DB_PORT}:3306"
```

Neste exemplo, as variáveis `DB_USER`, `DB_PASSWORD` e `DB_ROOT_PASSWORD` são passadas para o serviço `database` para configurar o banco de dados MariaDB.

#### c. Arquivo de Autenticação

Crie o arquivo `auth.json` dentro da pasta `build/credentials` com as credenciais de acesso ao repositório do Magento.

Você pode copiar as credenciais acessando: https://commercemarketplace.adobe.com/customer/accessKeys/ <br>
(autentique-se usando o 1Password)

O formato do JSON deve ser:

```json
{
    "http-basic": {
        "repo.magento.com": {
            "username": "public key",
            "password": "private key"
        }
    }
}
```


### 2. Rodar o Ambiente

Você pode usar o `Makefile` para automatizar a configuração do ambiente e a instalação do Magento. Basta rodar o seguinte comando:

```bash
make install
```

Isso irá:

- Verificar se as dependências necessárias (OpenSSL, Docker Compose, etc.) estão disponíveis.
- Gerar certificados SSL caso eles não existam.
- Iniciar os containers do Docker para o banco de dados, PHP, Nginx, e Elasticsearch e Redis.
- Instalar o Magento e configurá-lo com os valores do seu arquivo `.env`.
- Instalar o SDK PHP necessário para o plugin.
- Configurar o Magento com dados de exemplo, atualizar a instalação e limpar o cache.

Esse comando pode demorar dependendo da sua maquina. Após a finalização já é possível acessar o Magento localmente através do link `magento.local`.
Lembrando que por padrão o ambiente roda na porta `80`, se você já tiver alguma aplicação nessa mesma porta é preciso desligar. Também é exposta a porta `3306` para o banco de dados por padrão, se ja tiver algum rodando, é preciso desligar.

### 3. Rodando o Ambiente Manualmente

Se você deseja rodar os containers do Docker (caso já tenha instalado o Magento) execute:

```bash
make run
```

Isso irá iniciar os containers do Docker, mas não realizará a instalação.

### 4. Parar o Ambiente

Para parar os containers em execução:

```bash
make stop
```

Isso irá desligar os containers, mas manterá os volumes intactos.

### 5. Desinstalar o Ambiente

Para remover completamente os containers, volumes e recursos órfãos:

```bash
make uninstall
```

Isso irá desmontar o ambiente, incluindo todos os volumes do Docker.

### 6. Remover Imagens Não Utilizadas

Para remover as imagens Docker personalizadas para Nginx e PHP:

```bash
make remove-images
```

Isso irá deletar as imagens `nginx-magento-mp` e `php-magento-mp` do seu sistema local.

## Solução de Problemas

- **Certificados SSL**: Se os certificados forem deletados, você pode regenerá-los rodando o comando `make install` novamente. Mas tome cuidado, pois ao deletar os certificados e recriá-los, você também precisará deletar a imagem do nginx e recriá-la.
- **Problemas com Containers**: Se um container não estiver rodando, verifique com `docker ps` para garantir que os containers necessários estão ativos.
