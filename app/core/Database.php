<?php
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            $c = config('db');
            self::$pdo = new PDO(
                "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}",
                $c['user'], $c['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
        }
        return self::$pdo;
    }
    public static function run(string $sql, array $p = []): PDOStatement
    {
        $s = self::pdo()->prepare($sql);
        $s->execute($p);
        return $s;
    }
    public static function all(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $r = self::run($sql, $p)->fetch(); return $r ?: null; }
    public static function val(string $sql, array $p = []) { $v = self::run($sql, $p)->fetchColumn(); return $v === false ? null : $v; }
    public static function id(): int { return (int)self::pdo()->lastInsertId(); }
}