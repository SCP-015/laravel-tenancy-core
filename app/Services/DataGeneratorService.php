<?php

namespace App\Services;

class DataGeneratorService
{
    private static $firstNames = [
        'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gita', 'Hadi', 'Indra', 'Joko',
        'Kartika', 'Lina', 'Maya', 'Nina', 'Omar', 'Putri', 'Qori', 'Rina', 'Sari', 'Tina',
        'Umar', 'Vina', 'Wati', 'Xena', 'Yani', 'Zara', 'Agus', 'Bayu', 'Candra', 'Dian',
        'Erna', 'Fajar', 'Gilang', 'Hana', 'Irwan', 'Jihan', 'Kiki', 'Lestari', 'Mira', 'Nanda'
    ];

    private static $lastNames = [
        'Pratama', 'Sari', 'Wijaya', 'Putri', 'Santoso', 'Lestari', 'Kusuma', 'Wati', 'Handoko', 'Dewi',
        'Setiawan', 'Maharani', 'Nugroho', 'Anggraini', 'Permana', 'Safitri', 'Kurniawan', 'Rahayu', 'Gunawan', 'Fitria',
        'Susanto', 'Puspita', 'Hakim', 'Novita', 'Rahman', 'Indah', 'Saputra', 'Melati', 'Hidayat', 'Sinta'
    ];

    private static $streets = [
        'Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. Kuningan', 'Jl. Rasuna Said',
        'Jl. Diponegoro', 'Jl. Ahmad Yani', 'Jl. Veteran', 'Jl. Merdeka', 'Jl. Pahlawan',
        'Jl. Kartini', 'Jl. Cut Nyak Dien', 'Jl. Dewi Sartika', 'Jl. Imam Bonjol', 'Jl. Hayam Wuruk'
    ];

    private static $domains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'company.co.id'
    ];

    private static $sources = [
        'Website Karir', 'LinkedIn', 'JobStreet', 'Indeed', 'Referral', 'Social Media'
    ];

    private static $noticePeriods = [
        'immediately', '1_week', '2_weeks', '1_month', '3_months'
    ];

    private static $interviewTypes = ['online', 'offline'];

    private static $meetingRooms = [
        'Ruang Meeting A', 'Ruang Meeting B', 'Ruang Diskusi 1', 'Ruang Diskusi 2', 'Ruang Konferensi'
    ];

    private static $usedEmails = [];
    private static $counter = 0;

    /**
     * Generate random first name
     */
    public static function firstName(): string
    {
        return self::$firstNames[array_rand(self::$firstNames)];
    }

    /**
     * Generate random last name
     */
    public static function lastName(): string
    {
        return self::$lastNames[array_rand(self::$lastNames)];
    }

    /**
     * Generate unique email
     */
    public static function uniqueEmail(): string
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            $firstName = strtolower(self::firstName());
            $lastName = strtolower(self::lastName());
            $domain = self::$domains[array_rand(self::$domains)];
            $number = self::$counter > 0 ? self::$counter : '';
            
            $email = $firstName . '.' . $lastName . $number . '@' . $domain;
            self::$counter++;
            $attempts++;
            
        } while (in_array($email, self::$usedEmails) && $attempts < $maxAttempts);
        
        self::$usedEmails[] = $email;
        return $email;
    }

    /**
     * Generate random date between two dates
     */
    public static function dateBetween(string $startDate, string $endDate): string
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $randomTimestamp = random_int($start, $end);
        
        return date('Y-m-d', $randomTimestamp);
    }

    /**
     * Generate random datetime between two dates
     */
    public static function dateTimeBetween(string $startDate, string $endDate): string
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $randomTimestamp = random_int($start, $end);
        
        return date('Y-m-d H:i:s', $randomTimestamp);
    }

    /**
     * Generate random street address
     */
    public static function streetAddress(): string
    {
        $street = self::$streets[array_rand(self::$streets)];
        $number = random_int(1, 999);
        
        return $street . ' No. ' . $number;
    }

    /**
     * Generate random phone number
     */
    public static function phoneNumber(): string
    {
        $prefixes = ['0812', '0813', '0821', '0822', '0851', '0852', '0853'];
        $prefix = $prefixes[array_rand($prefixes)];
        
        return $prefix . random_int(1000000, 9999999);
    }

    /**
     * Generate random number between min and max
     */
    public static function numberBetween(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Get random element from array
     */
    public static function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Get multiple random elements from array
     */
    public static function randomElements(array $array, int $count): array
    {
        if ($count >= count($array)) {
            return $array;
        }
        
        $keys = array_rand($array, $count);
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        $result = [];
        foreach ($keys as $key) {
            $result[] = $array[$key];
        }
        
        return $result;
    }

    /**
     * Generate random paragraph
     */
    public static function paragraph(int $sentences = 3): string
    {
        $paragraphs = [
            'Saya adalah seorang profesional yang berpengalaman dalam bidang teknologi informasi.',
            'Memiliki kemampuan komunikasi yang baik dan dapat bekerja dalam tim.',
            'Selalu berusaha untuk memberikan yang terbaik dalam setiap pekerjaan.',
            'Tertarik untuk mengembangkan karir di perusahaan yang memiliki visi yang jelas.',
            'Memiliki pengalaman dalam mengelola proyek dan tim kerja.',
            'Dapat beradaptasi dengan lingkungan kerja yang dinamis dan penuh tantangan.'
        ];
        
        $result = [];
        for ($i = 0; $i < $sentences; $i++) {
            $result[] = $paragraphs[array_rand($paragraphs)];
        }
        
        return implode(' ', $result);
    }

    /**
     * Generate application source
     */
    public static function applicationSource(): string
    {
        return self::$sources[array_rand(self::$sources)];
    }

    /**
     * Generate notice period
     */
    public static function noticePeriod(): string
    {
        return self::$noticePeriods[array_rand(self::$noticePeriods)];
    }

    /**
     * Generate interview type
     */
    public static function interviewType(): string
    {
        return self::$interviewTypes[array_rand(self::$interviewTypes)];
    }

    /**
     * Generate meeting room
     */
    public static function meetingRoom(): string
    {
        return self::$meetingRooms[array_rand(self::$meetingRooms)];
    }

    /**
     * Generate Google Meet link
     */
    public static function googleMeetLink(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $part1 = substr(str_shuffle($chars), 0, 3);
        $part2 = substr(str_shuffle($chars), 0, 4);
        $part3 = substr(str_shuffle($chars), 0, 3);
        
        return "https://meet.google.com/{$part1}-{$part2}-{$part3}";
    }

    /**
     * Reset static counters (useful for testing)
     */
    public static function reset(): void
    {
        self::$usedEmails = [];
        self::$counter = 0;
    }
}
