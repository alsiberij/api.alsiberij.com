# Открытый API интернет магазина фотографий [alsiberij.com](https://alsiberij.com)

###### **ПОКА НЕ ПОДКЛЮЧЕН К ОСНОВНОМУ САЙТУ, РАБОТАЕТ АВТОНОМНО**
___
## Описание

Проект был разработан с целью изучения REST архитектуры.

### Аутентификация

Аутентификация происходит по:
- Идентификатору существующей сессии - параметр **session**
- Токену доступа - параметр **accessToken**

Один из параметров должен быть передан в POST или GET запросе. Например: `/users/get?userIDS=[1]&accessToken=12345`
или `/users/getAll?session=12345`

_Ни токен ни идентификатор сессии пока получить невозможно. Следовательно пока доступны
только методы, не требующие авторизации. Если у вас есть желание получить токен доступа, обращайтесь ко мне лично._

### Процесс обработки запроса

1. В файле `.htacess` настроена перезапись URL таким образом, что все запросы обрабатываются
   `index.php`.
2. Удаляются существующие сессионные cookie-файлы.
3. Запускается новая сессия, если в параметрах POST или GET запроса был передан ее идентификатор.
4. Из строки запроса выделяются имена сущности и метода. Если данные не корректны, сервер ответит ошибкой
`400`
5. Происходит обращение к фабрике сущностей, которая вернет либо объект сущности, либо null.
Во втором случае сервер ответит ошибкой `400`.
6. На объекте сущности вызывается метод `respond($string methodName): void`, в который передается
имя метода.

### Описание классов

Каждая сущность представляется двумя классами: непосредственно сама сущность и ее API.
Первый класс содержит всю информацию о сущности, т.е. её поля, а также методы для получения изменения
этих полей. Второй же содержит API методы, которые применимы к данной сущности. Рассмотрим подробнее API класс. Базовый
класс для всех сущностей - `API`. В нем содержатся три поля: объект авторизованного пользователя (может быть null),
объект соединения с базой данных и абстрактную фабрику сущностей, а также абстрактный метод `respond()`. Любой класс,
который наследуется от `API` должен реализовать метод `respond()` и проинициализировать поле фабрики. Таким образом,
любой API класс будет иметь возможность конструировать объекты соответствующих сущностей, вызывать свои методы внутри
переопределенного `respond()` и, благодаря объекту авторизованного пользователя, соблюдать права доступа.

### Доступные объекты и методы

- User API -  **/users/**
    - Метод `get`: Получить информацию о пользователях с идентификаторами `userIDs`.
        - Параметры: `userIDs` - массив идентификаторов пользователей в формате JSON
        - Пример запроса: `/users/get&userIDs=[1,5]`
        - Формат ответа: `{"response": [ {"ID": 1, ...}, {"ID": 5, ...} ]}`
    - Метод `getAll`: Получить информацию о всех пользователях
        - Параметры: НЕТ
        - Пример запроса: `/users/getAll`
        - Формат ответа: `{"response": [{"ID": 1, ...}, {"ID": 2, ...}, ...]}`
    - Метод `create`: Зарегистрироваться
        - Параметры `nickname` - имя пользователя, `email` - электронная почта, `password` - пароль
        - Пример запроса: `/users/create?nickname=alexander&email=alex@gmail.com&password=123`
        - Формат ответа: `{"response": "Success"}`. На `email` будет отправлено письмо с кодом активации
    - Метод `delete`: Удалить аккаунт. Сначала необходимо запросить код удаления, он будет отправлен на электронную
      почту, а затем воспользоваться им.
        - Параметры: `attempt` - какое действие требуется предпринять, а именно запросить код для удаления аккаунта
          (**request**) либо удалить аккаунт используя код (**process**), `deletionToken` - код удаления аккаунта.
        - Пример запроса: `/users/delete?accessToken=123&attempt=process&deletionToken=456`
        - Формат ответа `{"response": "Success"}`
    - Метод `activate`: Активировать аккаунт по токену `activationToken`
        - Параметры: `activationToken` - токен активации, отправленный на электронную почту при регистрации
        - Пример запроса: `/users/activate?activationToken=789`
        - Формат ответа: `{"response": "Success"}`
    - Метод `auth`: Провести аутентификацию пользователя по `email` и `password`. Возвращает либо объект пользователя,
      либо пустой объект
        - Параметры: `email` - электронная почта, `password` - пароль
        - Пример запроса: `/users/auth?email=alex@gmail.com&password=123`
        - Формат ответа: `{"response": {"ID": 1} }`


...

Документ будет дополняться по мере разработки.