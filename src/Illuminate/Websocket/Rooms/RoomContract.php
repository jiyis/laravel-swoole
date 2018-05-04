<?php

namespace Jiyis\Illuminate\Websocket\Rooms;

interface RoomContract
{
    /**
     * Do some init stuffs before workers started.
     */
    public function prepare();

    /**
     * Add a socket to a room.
     * @param int $fd
     * @param string $room
     * @return mixed
     */
    public function add(int $fd, string $room);

    /**
     * Add a socket to multiple rooms.
     * @param int $fd
     * @param array $rooms
     * @return mixed
     */
    public function addAll(int $fd, array $rooms);

    /**
     *  Delete a socket from a room.
     * @param int $fd
     * @param string $room
     * @return mixed
     */
    public function delete(int $fd, string $room);

    /**
     * Delete a socket from all rooms.
     * @param int $fd
     * @return mixed
     */
    public function deleteAll(int $fd);

    /**
     * Get all sockets by a room key.
     * @param string $room
     * @return mixed
     */
    public function getClients(string $room);

    /**
     * Get all rooms by a fd.
     * @param int $fd
     * @return mixed3
     */
    public function getRooms(int $fd);
}
