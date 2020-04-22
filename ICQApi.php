<?php

class ICQApi {

    /**
     * Bot token from @metabot
     * @var string
     */
    protected $token = '';

    /**
     * @var int
     */
    public $longPollTime = 20;

    /**
     * @var array
     */
    protected $keyboard = [];

    /**
     * @var array
     */
    protected $longPollEvents = [];

    /**
     * ICQApi constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * An named events is executed before the event - * (any events)
     * @param int $lastEventId
     */
    public function eventLoop(int $lastEventId)
    {
        $anyEventCallback = $this->longPollEvents['*'] ?? function() {};

        while (true) {
            $poll = $this->getEvents(['lastEventId' => $lastEventId]);

            if (!isset($poll['events']) || !is_array($poll['events'])) continue;

            foreach ($poll['events'] as $event) {
                if ($event['eventId'] < $lastEventId) continue;

                $lastEventId = $event['eventId'];

                if (!isset($this->longPollEvents[$event['type']])) {
                    $anyEventCallback($event);
                    continue;
                }

                $this->longPollEvents[$event['type']]($event);
                $anyEventCallback($event);
            }
        }
    }

    /**
     * Event subscription
     * @param string $event
     * @param callable $callback
     * @return $this
     */
    public function on(string $event, callable $callback)
    {
        $this->longPollEvents[$event] = $callback;

        return $this;
    }

    /**
     * All requests
     * @param string $method
     * @param array $params
     * @return mixed
     */
    function api(string $method, array $params = [])
    {
        if (!isset($params['token'])) $params['token'] = $this->token;

        return json_decode(
            file_get_contents('https://api.icq.net/bot/v1/' . $method . '?' . http_build_query($params)),
            true
        );
    }

    /**
     * Long-Polling request
     * @param array $params
     * @return mixed - JSON object
     */
    function getEvents(array $params = [])
    {
        if (!isset($params['pollTime'])) $params['pollTime'] = $this->longPollTime;

        return $this->api('events/get', $params);
    }

    /**
     * @param string $chatId - unique nick | chat id | user id
     * @param string $text
     * @return mixed
     */
    function msg(string $chatId, string $text)
    {
        return $this->api('messages/sendText', [
            'chatId' => $chatId,
            'text' => $text,
            'inlineKeyboardMarkup' => $this->keyboard()
        ]);
    }

    /**
     * @param string $text
     * @param string $callback
     * @param string $style - primary, attention, base
     * @return array
     */
    function btn(string $text, string $callback, $style = 'base')
    {
        return [
            'text' => $text,
            'callbackData' => $callback,
            'style' => in_array($style, ['primary', 'attention', 'base']) ? $style : 'base'
        ];
    }

    /**
     * @param array $buttons
     * @return $this
     */
    function setKeyRow(array $buttons)
    {
        $this->keyboard[] = $buttons;

        return $this;
    }

    /**
     * @return false|string
     */
    function keyboard()
    {
        return json_encode($this->keyboard, JSON_UNESCAPED_UNICODE);
    }

    /**
     * When a button is pressed, an event is generated to which the server must respond
     * @param array $event
     * @return mixed
     */
    function acceptClick(array $event)
    {
        return $this->api('messages/answerCallbackQuery', [
            'queryId' => $event['payload']['queryId']
        ]);
    }
}