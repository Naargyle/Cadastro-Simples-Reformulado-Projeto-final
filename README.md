#  Jelly Home — Loja de Móveis de Gelatina 

Um sistema de cadastro e loja personalizado desenvolvido com o auxilio do github Copilot Pro Education, com alterações contendo implementação da função de recuperação de senha, experiencia personalizada para administrador e cliente, para alterar o modo de acesso, mude o valor de IsAdmin no arquivo db.php ou cadastro.php, sendo 1 para administrador e 0 para usuário.

## Tecnologias Utilizadas
* **Backend:** PHP 8.x (Estrutural)
* **Banco de Dados:** SQLite (via PDO)
* **Frontend:** HTML5 e CSS3 (Efeito Glassmorphism/Gelatina)

##  Estrutura de Arquivos e Fluxo
1. `db.php` — Gerencia a conexão PDO com o banco `database.db` e cria as tabelas automaticamente.
2. `index.php` — Porta de entrada da aplicação. Valida a sessão e redireciona o usuário.
3. `cadastro.php` — Formulário de criação de conta com hashing seguro de senha (`password_hash`).
4. `login.php` — Autenticação do usuário e criação segura da sessão ativa.
5. `loja.php` — Interface principal da loja de móveis com exibição dinâmica e sistema de carrinho.
6. `logout.php` — Encerramento seguro e destruição dos dados da sessão.

## 🚀 Como Executar Localmente
1. Certifique-se de ter o **XAMPP** (ou servidor PHP equivalente) instalado.
2. Clone ou cole a pasta deste projeto dentro do diretório padrão:
   * Windows: `C:\xampp\htdocs\jelly-home\`
3. Abra o **XAMPP Control Panel** e dê **Start** no módulo **Apache**.
4. No seu navegador, acesse:
   ```text
   http://localhost/jelly-home/loja.php
