<?php

namespace Jiyis\Illuminate\Websocket;

use InvalidArgumentException;
use Illuminate\Support\Facades\App;
use Jiyis\Illuminate\Websocket\Rooms\RoomContract;

class Websocket
{

    const PUSH_ACTION = 'push';

    /**
     * Determine if to broadcast.
     *
     * @var boolean
     */
    protected $isBroadcast = false;

    /**
     * Scoket sender's fd.
     *
     * @var integer
     */
    protected $sender;

    /**
     * Recepient's fd or room name.
     *
     * @var array
     */
    protected $to = [];

    /**
     * Websocket event callbacks.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Room adapter.
     *
     * @var Jiyis\Illuminate\Websocket\Rooms\RoomContract
     */
    protected $room;

    // https://gist.github.com/alexpchin/3f257d0bb813e2c8c476
    // https://github.com/socketio/socket.io/blob/master/docs/emit.md
    public function __construct(RoomContract $room)
    {
        $this->room = $room;
    }

    /**
     * Set broadcast to true.
     */
    public function broadcast()
    {
        $this->isBroadcast = true;

        return $this;
    }

    /**
     * Set a recepient's fd or a room name.
     * @param $value
     * @return $this
     */
    public function to($value)
    {
        $this->toAll([$value]);

        return $this;
    }

    /**
     * Set multiple recepients' fdd or room names.
     * @param array $values
     * @return $this
     */
    public function toAll(array $values)
    {
        foreach ($values as $value) {
            if (!in_array($value, $this->to)) {
                $this->to[] = $value;
            }
        }

        return $this;
    }

    /**
     * Join sender to a room.
     * @param string $room
     * @return $this
     */
    public function join(string $room)
    {
        $this->room->add($this->sender, $room);

        return $this;
    }

    /**
     * Join sender to multiple rooms.
     * @param array $rooms
     * @return $this
     */
    public function joinAll(array $rooms)
    {
        $this->room->addAll($this->sender, $rooms);

        return $this;
    }

    /**
     *  Make sender leave a room.
     * @param string $room
     * @return $this
     */
    public function leave(string $room)
    {
        $this->room->delete($this->sender, $room);

        return $this;
    }

    /**
     * Make sender leave multiple rooms.
     * @param array $rooms
     * @return $this
     */
    public function leaveAll(array $rooms = [])
    {
        $this->room->deleteAll($this->sender, $rooms);

        return $this;
    }

    /**
     * Emit data and reset some status.
     * @param string $event
     * @param $data
     * @return bool
     */
    public function emit(string $event, $data)
    {
        $fds = $this->getFds();
        $assigned = !empty($this->to);

        // if no fds are found, but rooms are assigned
        // that means trying to emit to a non-existing room
        // skip it directly instead of pushing to a task queue
        if (empty($fds) && $assigned) {
            return false;
        }

        $result = app('swoole.server')->task([
            'action' => static::PUSH_ACTION,
            'data'   => [
                'sender'    => $this->sender,
                'fds'       => $fds,
                'broadcast' => $this->isBroadcast,
                'assigned'  => $assigned,
                'event'     => $event,
                'message'   => $data
            ]
        ]);

        $this->reset();

        return $result === false ? false : true;
    }

    /**
     *  An alias of `join` function.
     * @param string $room
     * @return $this
     */
    public function in(string $room)
    {
        $this->join($room);

        return $this;
    }

    /**
     *  Register an event name with a closure binding.
     * @param string $event
     * @param $callback
     * @return $this
     */
    public function on(string $event, $callback)
    {
        if (!is_string($callback) && !is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid websocket callback. Must be a string or callable.'
            );
        }

        $this->callbacks[$event] = $callback;

        return $this;
    }

    /**
     *  Check if this event name exists.
     * @param string $event
     * @return bool
     */
    public function eventExists(string $event)
    {
        return array_key_exists($event, $this->callbacks);
    }

    /**
     *  Execute callback function by its event name.
     * @param string $event
     * @param null $data
     * @return null
     */
    public function call(string $event, $data = null)
    {
        if (!$this->eventExists($event)) {
            return null;
        }

        return App::call($this->callbacks[$event], [
            'websocket' => $this,
            'data'      => $data
        ]);
    }

    /**
     * Close current connection.
     * @param int|null $fd
     * @return mixed
     */
    public function close(int $fd = null)
    {
        return app('swoole.server')->close($fd ?: $this->sender);
    }

    /**
     * Set sender fd.
     * @param int $fd
     * @return $this
     */
    public function setSender(int $fd)
    {
        $this->sender = $fd;

        return $this;
    }

    /**
     * Get current sender fd.
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Get broadcast status value.
     * @return mixed
     */
    public function getIsBroadcast()
    {
        return $this->isBroadcast;
    }

    /**
     * Get push destinations (fd or room name).
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get all fds we're going to push data to.
     * @return array
     */
    protected function getFds()
    {
        $fds = array_filter($this->to, function ($value) {
            return is_integer($value);
        });
        $rooms = array_diff($this->to, $fds);

        foreach ($rooms as $room) {
            $fds = array_merge($fds, $this->room->getClients($room));
        }

        return array_values(array_unique($fds));
    }

    /**
     * Reset some data status.
     * @param bool $force
     * @return $this
     */
    public function reset($force = false)
    {
        $this->isBroadcast = false;
        $this->to = [];

        if ($force) {
            $this->sender = null;
        }

        return $this;
    }
}