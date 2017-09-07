Все настройки подключения к БД указаны в файле `db.ini`. Предполагается, что конфиг-файлы недоступны посторонним,
но я все равно рекомендую создать для генерации сайтмэпа отдельного пользователя исключительно с правом SELECT:

```
CREATE USER 'sitemapcreator'@'localhost' IDENTIFIED BY 'sitemappassword';
GRANT SELECT ON database.* TO `sitemapcreator`@`localhost`;
FLUSH PRIVELEGES;
```
