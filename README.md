# ICQApi
Моя попытка сделать класс для работы с icq new bot api.

// Токен брать у @metabot
$icq = new ICQApi(TOKEN);

// Подписка на конкретное событие
$icq->on('newMessage', function($event) {
    ...
});

// Подписка на все события
$icq->on('*', function($event) {
    ...
});

// Запуск цикла long-poll запросов и обработки событий
$icq->eventLoop($lastEventId = 0);
