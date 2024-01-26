# DESAFIO BACKEND

## Configuração do Ambiente

### Requisitos
- _PHP >= 8.0_ e [extensões](https://www.php.net/manual/pt_BR/extensions.php) (**não esquecer de instalar as seguintes extensões: _pdo_, _pdo_sqlite_ e _sqlite3_**);
- _SQLite_;
- _Composer_.

### Instalação
- Instalar dependências pelo composer com `composer install` na raiz do projeto;
- Servir a pasta _public_ do projeto através de algum servidor.
  (_Sugestão [PHP Built in Server](https://www.php.net/manual/en/features.commandline.webserver.)_)

## Sobre o Projeto

- O cliente XPTO Ltda. contratou seu serviço para realizar alguns ajustes em seu sistema de cadastro de produtos;
- O sistema permite o cadastro, edição e remoção de _produtos_ e _categorias de produtos_ para uma _empresa_;
- Para que sejam possíveis os cadastros, alterações e remoções é necessário um usuário administrador;
- O sistema possui categorias padrão que pertencem a todas as empresas, bem como categorias personalizadas dedicadas a uma dada empresa. As categorias padrão são: (`clothing`, `phone`, `computer` e `house`) e **devem** aparecer para todas as _empresas_;
- O sistema tem um relatório de dados dedicado ao cliente.

## Sobre a API
As rotas estão divididas em:
  -  _CRUD_ de _categorias_;
  - _CRUD_ de _produtos_;
  - Rota de busca de um _relatório_ que retorna um _html_.

**Atenção**, é bem importante que se adicione o _header_ `admin_user_id` com o id do usuário desejado ao acessar as rotas para simular o uso de um usuário no sistema.

A documentação da API se encontra na pasta `docs/api-docs.pdf`
  - A documentação assume que a url base é `localhost:8000` mas você pode usar qualquer outra url ao configurar o servidor;
  - O _header_ `admin_user_id` na documentação está indicado com valor `1` mas pode ser usado o id de qualquer outro usuário caso deseje (_pesquisando no banco de dados é possível ver os outros id's de usuários_).
  
Caso opte por usar o [Insomnia](https://insomnia.rest/) o arquivo para importação se encontra em `docs/insomnia-api.json`.
Caso opte por usar o [Postman](https://www.postman.com/) o arquivo para importação se encontra em `docs/postman-api.json`.

## Sobre o Banco de Dados
- O banco de dados é um _sqlite_ simples e já vem com dados preenchidos por padrão no projeto;
- O banco tem um arquivo de backup em `db/db-backup.sqlite` com o estado inicial do projeto caso precise ser "resetado".

## Demandas
Abaixo, as solicitações do cliente:

### Categorias
- [x] A categoria está vindo errada na listagem de produtos para alguns casos
  (_exemplo: produto `blue trouser` está vindo na categoria `phone` e deveria ser `clothing`_);
- [x] Alguns produtos estão vindo com a categoria `null` ao serem pesquisados individualmente (_exemplo: produto `iphone 8`_);
- [x] Cadastrei o produto `king size bed` em mais de uma categoria, mas ele aparece **apenas** na categoria `furniture` na busca individual do produto.

### Filtros e Ordenamento
Para a listagem de produtos:
- [x] Gostaria de poder filtrar os produtos ativos e inativos;
- [x] Gostaria de poder filtrar os produtos por categoria;
- [x] Gostaria de poder ordenar os produtos por data de cadastro.

### Relatório
- [x] O relatório não está mostrando a coluna de logs corretamente, se possível, gostaria de trazer no seguinte formato:
  (Nome do usuário, Tipo de alteração e Data),
  (Nome do usuário, Tipo de alteração e Data),
  (Nome do usuário, Tipo de alteração e Data)
  Exemplo:
  (John Doe, Criação, 01/12/2023 12:50:30),
  (Jane Doe, Atualização, 11/12/2023 13:51:40),
  (Joe Doe, Remoção, 21/12/2023 14:52:50)

### Logs
- [x] Gostaria de saber qual usuário mudou o preço do produto `iphone 8` por último.

### Extra
- [ ] Aqui fica um desafio extra **opcional**: _criar um ambiente com_ Docker _para a api_.

**Seu trabalho é atender às 7 demandas solicitadas pelo cliente.**

Caso julgue necessário, podem ser adicionadas ou modificadas as rotas da api. Caso altere, por favor, explique o porquê e indique as alterações nesse `README`.

Sinta-se a vontade para refatorar o que achar pertinente, considerando questões como arquitetura, padrões de código, padrões restful, _segurança_ e quaisquer outras boas práticas. Levaremos em conta essas mudanças.

Boa sorte! :)

## Suas Respostas, Duvidas e Observações 24/01/2024 - 20:00


Para as atualizações em categorias:
- Para o erro de trazer a categoria errada foi ajustado a query que buscava, pois estava comparando a coluna errada.
- Para os que estavam trazendo NULL, atualizei diretamente no banco (apesar de não ser o recomendado) a coluna de company_id das categorias, como no sistema havia apenas uma empresa registrada, achei pertinente colocar todas as categorias como company_id 1.
- Na rota de buscar o produto específico alterei o código para listar mais de 1 categoria como array, caso exista mais de 1 categoria relacionada na tabela de product_category

Para atualizações em Filtros e Ordenamento:
- Adicionei os 3 campos de filtro para serem recebidos no params (utilizei postaman para testar).
- Para ordenação, criei 2 parametros que podem ser enviados ou não: orderBy e typeOrder. No orderBy será onde será indicado o campo que será ordenado (created_at, id, title, price e etc), no typeOrder será indicado o tipo da ordenação (ASC ou DESC).
- Para o filtro de categoria, criei um parâmetro categoryId, onde será passado o id da categoria que irá filtrar os produtos, serão listados apenas os produtos da categoria enviada
- Para o filtro de ativo, criei o parâmetro active, onde será enviado 1 ou 0 para trazer ativo ou inativo
- Os filtros poderão ser combinados ou enviados individualmente, exceto o orderType que só será considerado caso o orderBy tenha sido enviado também.

Para a atualização no Relatório:
- Fora adicionado manualmente uma estruturação de uma string para o formado requisitado onde percorro o array de objetos por um foreach e monto a lista de log de cada produto
- Ainda no arquivo de ReportController, me dei a liberdade de adicionar a listagem das categorias da mesma forma, para aqueles produtos que possuem mais de 1 categoria.


Para a atualização de Logs:
- Fora solicitado a informação de qual usuário atualizou por ultimo o preço de determinado produto, então criei uma rota /products/lastUpdate/{id} para listar a ultima atualização feita no produto em questão.


Extra:
- na listagem de todos os produtos, alterei a query para que trouxesse todas as categorias dos produtos separada por virgula.



## Alterações adicionais para deixar a API mais estável 26/01/2024 - 13:00

- Quando não era enviado admin_user_id para a API, ocorria um erro, porém agora a API informa ao usuário que o campo deve ser enviado. Apenas nas rotas em que o mesmo era utilizado.

- Ao dar erro em alguma rota (UPDATE, INSERT ou DELETE), ele retornava apenas o número 404 no status, agora retorna também um json com um status ("success" ou "error") e uma message, para que fique claro para o cliente que ocorreu um erro. (foi alterado apenas nas funções de categoria e produto)

- Já nos services, alterei para deixar a API mais estável usando transaction, dessa forma durante um delete de produto por exemplo, caso haja erro durante o delete do produto, poderemos dar rollback no delete da relação produto-categoria. Apliquei apenas nas rotas em que fazem mais de 1 query por vez, pois caso a segunda dê algum erro, poderemos reverter a primeira.

- Na rota de inserção e alteração de produto, alterei a dinâmica do category_id, agora a API aceitará um id único no campo e também um array, caso o produto possua mais de 1 categoria a qual pertença.

- Também adicionei o try, pois dessa forma poderemos ter controle dos erros e não deixar que os mesmos sejam exibidos no retorno da API, apenas as mensagens de erro que retornamos, deixando assim os retornos da API mais "limpos".