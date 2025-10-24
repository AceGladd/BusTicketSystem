<?php

class Database {
    private $db;
    private static $instance = null;

    private function __construct() {
        try {
            $dbPath = __DIR__ . '/../database/tickets.db';
            $dbDir = dirname($dbPath);

            // Create database directory if it doesn't exist
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0777, true);
            }

            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $this->initDatabase();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    private function initDatabase() {
        // Create tables
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                full_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'firma_admin', 'user')),
                company_id TEXT,
                balance REAL DEFAULT 800,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES bus_company(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS bus_company (
                id TEXT PRIMARY KEY,
                name TEXT UNIQUE NOT NULL,
                logo_path TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS trips (
                id TEXT PRIMARY KEY,
                company_id TEXT NOT NULL,
                departure_city TEXT NOT NULL,
                destination_city TEXT NOT NULL,
                departure_time DATETIME NOT NULL,
                arrival_time DATETIME NOT NULL,
                price REAL NOT NULL,
                capacity INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES bus_company(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS tickets (
                id TEXT PRIMARY KEY,
                trip_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                seat_number INTEGER NOT NULL,
                status TEXT DEFAULT 'active' CHECK(status IN ('active', 'cancelled')),
                total_price REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS booked_seats (
                id TEXT PRIMARY KEY,
                ticket_id TEXT NOT NULL,
                trip_id TEXT NOT NULL,
                seat_number INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
                UNIQUE(trip_id, seat_number)
            );

            CREATE TABLE IF NOT EXISTS coupons (
                id TEXT PRIMARY KEY,
                code TEXT UNIQUE NOT NULL,
                discount REAL NOT NULL,
                company_id TEXT,
                usage_limit INTEGER NOT NULL,
                usage_count INTEGER DEFAULT 0,
                expire_date DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES bus_company(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS user_coupons (
                id TEXT PRIMARY KEY,
                coupon_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                ticket_id TEXT NOT NULL,
                is_used INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
            );

            CREATE INDEX IF NOT EXISTS idx_trips_cities ON trips(departure_city, destination_city);
            CREATE INDEX IF NOT EXISTS idx_trips_time ON trips(departure_time);
            CREATE INDEX IF NOT EXISTS idx_tickets_user ON tickets(user_id);
            CREATE INDEX IF NOT EXISTS idx_booked_seats_trip ON booked_seats(trip_id);
        ");

        $this->seedInitialData();
    }

    private function seedInitialData() {
        // Check if data already exists
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            return; // Data already seeded
        }

        // Generate UUIDs
        $adminId = $this->generateUUID();
        $metroId = $this->generateUUID();
        $kamilKocId = $this->generateUUID();
        $pashaId = $this->generateUUID();
        $firmaAdminId = $this->generateUUID();
        $firmaAdmin2Id = $this->generateUUID();
        $userId = $this->generateUUID();

        // Insert bus companies
        $this->db->exec("
            INSERT INTO bus_company (id, name, logo_path) VALUES
            ('{$metroId}', 'Metro Turizm', 'metro.png'),
            ('{$kamilKocId}', 'Kamil Koç', 'kamilkoc.png'),
            ('{$pashaId}', 'Pamukkale Turizm', 'pamukkale.png');
        ");

        // Insert users (password: hashed version of the plain passwords)
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $firmaPass = password_hash('firma123', PASSWORD_DEFAULT);
        $userPass = password_hash('user123', PASSWORD_DEFAULT);

        $this->db->exec("
            INSERT INTO users (id, full_name, email, password, role, company_id, balance) VALUES
            ('{$adminId}', 'Admin User', 'admin@example.com', '{$adminPass}', 'admin', NULL, 1000),
            ('{$firmaAdminId}', 'Metro Admin', 'metro@example.com', '{$firmaPass}', 'firma_admin', '{$metroId}', 1000),
            ('{$firmaAdmin2Id}', 'Kamil Koç Admin', 'kamilkoc@example.com', '{$firmaPass}', 'firma_admin', '{$kamilKocId}', 1000),
            ('{$userId}', 'Ahmet Yılmaz', 'user@example.com', '{$userPass}', 'user', NULL, 800);
        ");

        // Insert sample trips
        $trips = [
            [$this->generateUUID(), $metroId, 'İstanbul', 'Ankara', '2025-10-20 09:00:00', '2025-10-20 14:30:00', 350, 45],
            [$this->generateUUID(), $metroId, 'İstanbul', 'İzmir', '2025-10-21 10:00:00', '2025-10-21 18:00:00', 400, 45],
            [$this->generateUUID(), $kamilKocId, 'Ankara', 'Antalya', '2025-10-22 08:00:00', '2025-10-22 16:00:00', 450, 50],
            [$this->generateUUID(), $kamilKocId, 'İzmir', 'Ankara', '2025-10-23 11:00:00', '2025-10-23 17:00:00', 380, 50],
            [$this->generateUUID(), $pashaId, 'İstanbul', 'Antalya', '2025-10-24 20:00:00', '2025-10-25 06:00:00', 500, 40],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($trips as $trip) {
            $stmt->execute($trip);
        }

        // Insert sample coupons
        $coupons = [
            [$this->generateUUID(), 'METRO20', 20, $metroId, 100, '2025-12-31 23:59:59'],
            [$this->generateUUID(), 'KAMILKOC15', 15, $kamilKocId, 50, '2025-11-30 23:59:59'],
            [$this->generateUUID(), 'GENELINDI10', 10, NULL, 200, '2025-12-31 23:59:59'],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO coupons (id, code, discount, company_id, usage_limit, expire_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($coupons as $coupon) {
            $stmt->execute($coupon);
        }
    }

    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function generateUUIDPublic() {
        return $this->generateUUID();
    }
}

// Initialize database connection
$db = Database::getInstance()->getConnection();
