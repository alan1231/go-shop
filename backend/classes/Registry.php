<?php

class Registry {
    private static array $items = [];

    public static function set(string $key, $value): void {
        self::$items[$key] = $value;
    }

    public static function get(string $key) {
        return self::$items[$key] ?? null;
    }
}
