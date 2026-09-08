<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing_model extends CI_Model
{
    public function get_summary()
    {
        $fallback = array(
            'total_rooms' => 66,
            'available_rooms' => 11,
            'occupied_rooms' => 55,
            'occupancy_rate' => 83,
            'starting_price' => 1500000,
        );

        if ($this->table_exists('gh_room_database')) {
            $row = $this->db
                ->select('COUNT(id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS starting_price', FALSE)
                ->get('gh_room_database')
                ->row_array();

            return array(
                'total_rooms' => (int) $row['total_rooms'],
                'available_rooms' => (int) $row['available_rooms'],
                'occupied_rooms' => 0,
                'occupancy_rate' => 0,
                'starting_price' => isset($row['starting_price']) ? (int) $row['starting_price'] : $fallback['starting_price'],
            );
        }

        if ( ! $this->table_exists('rooms')) {
            return $fallback;
        }

        $total = (int) $this->db->count_all('rooms');
        $available = (int) $this->db->where('status', 'available')->count_all_results('rooms');
        $occupied = (int) $this->db->where('status', 'occupied')->count_all_results('rooms');
        $price = $this->db->select_min('price', 'starting_price')->where('price >', 0)->get('rooms')->row_array();

        return array(
            'total_rooms' => $total,
            'available_rooms' => $available,
            'occupied_rooms' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
            'starting_price' => isset($price['starting_price']) ? (int) $price['starting_price'] : $fallback['starting_price'],
        );
    }

    public function get_room_types()
    {
        if ($this->table_exists('gh_room_database')) {
            $rows = $this->db
                ->select('CONCAT(property_code, " - ", room_type) AS name', FALSE)
                ->select('property_code', FALSE)
                ->select('room_type', FALSE)
                ->select('COUNT(id) AS total_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS price_from', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_room_database')
                ->group_by('property_code, room_type')
                ->order_by('property_code', 'ASC')
                ->order_by('FIELD(room_type, "Standart", "Deluxe", "VIP")', '', FALSE)
                ->order_by('room_type', 'ASC')
                ->get()
                ->result_array();

            return $rows ? $rows : $this->fallback_room_types();
        }

        if ( ! $this->table_exists('rooms')) {
            return $this->fallback_room_types();
        }

        $this->db->select('COALESCE(rt.name, "Room") AS name, COUNT(r.id) AS total_rooms, MIN(r.price) AS price_from, SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE);
        $this->db->from('rooms r');
        $this->db->join('room_types rt', 'rt.id = r.room_type_id', 'left');
        $this->db->group_by('r.room_type_id, rt.name');
        $this->db->order_by('price_from', 'ASC');
        $rows = $this->db->get()->result_array();

        return $rows ? $rows : $this->fallback_room_types();
    }

    public function get_featured_rooms()
    {
        if ($this->table_exists('gh_room_database')) {
            return $this->get_public_room_type_cards();
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('COALESCE(rt.name, "Room") AS type_name', FALSE)
                ->select('COUNT(r.id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->select('MIN(CASE WHEN r.price > 0 THEN r.price END) AS price', FALSE)
                ->select('MAX(CASE WHEN r.price > 0 THEN r.price END) AS max_price', FALSE)
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->group_by('r.room_type_id, rt.name')
                ->order_by('price', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_public_type_rows($rows);
            }
        }

        return $this->fallback_public_room_type_cards();
    }

    public function get_all_public_rooms()
    {
        if ($this->table_exists('gh_room_database')) {
            return $this->get_public_room_type_cards();
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('COALESCE(rt.name, "Room") AS type_name', FALSE)
                ->select('COUNT(r.id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->select('MIN(CASE WHEN r.price > 0 THEN r.price END) AS price', FALSE)
                ->select('MAX(CASE WHEN r.price > 0 THEN r.price END) AS max_price', FALSE)
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->group_by('r.room_type_id, rt.name')
                ->order_by('price', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_public_type_rows($rows);
            }
        }

        return $this->fallback_public_room_type_cards();
    }

    public function get_amenities()
    {
        return array(
            array('name' => 'Kamar Furnished', 'description' => 'Kasur, lemari, meja, kursi, dan perlengkapan dasar siap pakai.'),
            array('name' => 'Kontrol Fasilitas', 'description' => 'AC, listrik, air, kamar mandi, dan area umum dicek berkala.'),
            array('name' => 'Akses 24 Jam', 'description' => 'Penghuni tetap leluasa masuk-keluar sesuai tata tertib.'),
            array('name' => 'Area Umum', 'description' => 'Koridor, lobby, parkir, dan fasilitas bersama dikelola terjadwal.'),
            array('name' => 'Maintenance Tercatat', 'description' => 'Keluhan dan pekerjaan teknis dilacak sampai selesai.'),
            array('name' => 'Booking Mudah', 'description' => 'Calon penghuni bisa inquiry dan jadwalkan survey dari website.'),
        );
    }

    public function get_testimonials()
    {
        return array(
            array('name' => 'Ayu', 'room' => 'GH 1', 'quote' => 'Kamar rapi dan proses masuknya jelas. Cocok untuk yang butuh tempat tinggal praktis.'),
            array('name' => 'Rizky', 'room' => 'GH 2', 'quote' => 'Lokasi mudah dijangkau, pengelola responsif saat ada kendala fasilitas.'),
            array('name' => 'Diva', 'room' => 'Cappadocia', 'quote' => 'Yang saya suka, biaya dan aturan tinggal dijelaskan sejak awal.'),
        );
    }

    public function get_cost_items()
    {
        return array(
            array('label' => 'Sewa bulanan', 'value' => 'Mulai Rp 1.500.000'),
            array('label' => 'Deposit', 'value' => 'Umumnya 1x sewa'),
            array('label' => 'Biaya tambahan', 'value' => 'Sesuai fasilitas kamar'),
            array('label' => 'Survey', 'value' => 'By appointment'),
        );
    }

    public function get_house_rules()
    {
        return array(
            'Data penghuni wajib lengkap sebelum check-in.',
            'Pembayaran mengikuti tanggal jatuh tempo yang disepakati.',
            'Tamu dan parkir mengikuti aturan masing-masing properti.',
            'Kerusakan fasilitas wajib dilaporkan ke pengelola.',
        );
    }

    public function get_nearby_places()
    {
        return array(
            array('place' => 'Akses transport', 'distance' => 'dekat area utama'),
            array('place' => 'Minimarket', 'distance' => 'jalan kaki'),
            array('place' => 'Kuliner sekitar', 'distance' => 'banyak pilihan'),
            array('place' => 'Area parkir', 'distance' => 'tersedia terbatas'),
        );
    }

    public function get_property_stats()
    {
        if ($this->table_exists('gh_room_database')) {
            return $this->db
                ->select('property_code AS property_name, COUNT(id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_room_database')
                ->group_by('property_code')
                ->order_by('property_code', 'ASC')
                ->get()
                ->result_array();
        }

        if ( ! $this->table_exists('rooms')) {
            return array(
                array('property_name' => 'GH 1', 'total_rooms' => 29, 'available_count' => 7),
                array('property_name' => 'GH 2', 'total_rooms' => 34, 'available_count' => 4),
                array('property_name' => 'Apartemen', 'total_rooms' => 3, 'available_count' => 1),
            );
        }

        return $this->db
            ->select('code AS property_name, COUNT(id) AS total_rooms, SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
            ->from('rooms')
            ->group_by('code')
            ->order_by('code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_room_type_detail($slug)
    {
        $slug = $this->normalize_slug($slug);
        if ($slug === '') {
            return FALSE;
        }

        foreach ($this->get_public_room_type_cards() as $card) {
            if ($card['slug'] === $slug) {
                return $this->normalize_public_room_type_detail($card);
            }
        }

        return FALSE;
    }

    public function get_room_detail($id)
    {
        if ($id <= 0) {
            return $this->fallback_room_detail(1);
        }

        if ($this->table_exists('gh_room_database')) {
            $room = $this->db
                ->select('id, property_code, room_code, room_type, room_name, room_label, deposit, monthly_price, is_mess, floor_no')
                ->from('gh_room_database')
                ->where('id', $id)
                ->get()
                ->row_array();

            if ($room) {
                return $this->normalize_gh_room_detail($room);
            }
        }

        if ($this->table_exists('rooms')) {
            $room = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, r.notes, rt.name AS type_name, rt.description AS type_description')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->where('r.id', $id)
                ->get()
                ->row_array();

            if ($room) {
                $room['facilities'] = $this->get_room_assets((int) $room['id']);
                return $this->normalize_room_detail($room);
            }
        }

        return $this->fallback_room_detail($id);
    }

    public function get_similar_rooms(array $room)
    {
        if ($this->table_exists('gh_room_database') && isset($room['id'])) {
            $rows = $this->db
                ->select('id, property_code, room_code, room_type, room_label, monthly_price, is_mess')
                ->from('gh_room_database')
                ->where('id !=', (int) $room['id'])
                ->where('monthly_price IS NOT NULL', NULL, FALSE)
                ->where('is_mess', 0)
                ->order_by('monthly_price', 'ASC')
                ->order_by('property_code', 'ASC')
                ->limit(3)
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_gh_rooms($rows);
            }
        }

        if ($this->table_exists('rooms') && isset($room['id'])) {
            $rows = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, rt.name AS type_name')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->where('r.id !=', (int) $room['id'])
                ->where('r.status', 'available')
                ->order_by('r.price', 'ASC')
                ->limit(3)
                ->get()
                ->result_array();

            if ($rows) {
                return $rows;
            }
        }

        return array(
            array('id' => 1, 'code' => 'GH 1', 'name' => 'GH 1 - VIP', 'type_name' => 'VIP', 'price' => 2000000, 'status' => 'available'),
            array('id' => 2, 'code' => 'GH 2', 'name' => 'GH 2 - Standart', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('id' => 3, 'code' => 'GH 2', 'name' => 'GH 2 - VIP', 'type_name' => 'VIP', 'price' => 3000000, 'status' => 'available'),
        );
    }

    public function get_similar_room_types(array $room)
    {
        $similar = array();
        foreach ($this->get_public_room_type_cards() as $card) {
            if (isset($room['slug']) && $card['slug'] === $room['slug']) {
                continue;
            }

            if ($card['code'] === $room['code'] || $card['type_name'] === $room['type_name']) {
                $similar[] = $this->normalize_public_room_type_detail($card);
            }

            if (count($similar) >= 3) {
                break;
            }
        }

        if (count($similar) < 3) {
            foreach ($this->get_public_room_type_cards() as $card) {
                if (isset($room['slug']) && $card['slug'] === $room['slug']) {
                    continue;
                }

                $exists = FALSE;
                foreach ($similar as $item) {
                    if ($item['slug'] === $card['slug']) {
                        $exists = TRUE;
                        break;
                    }
                }

                if ( ! $exists) {
                    $similar[] = $this->normalize_public_room_type_detail($card);
                }

                if (count($similar) >= 3) {
                    break;
                }
            }
        }

        return $similar;
    }

    public function get_room_gallery(array $room)
    {
        return array(
            array(
                'category' => 'Kamar',
                'title' => $room['name'] . ' - Sudut tidur',
                'src' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar',
                'title' => $room['name'] . ' - Area kerja',
                'src' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar',
                'title' => $room['name'] . ' - Penyimpanan',
                'src' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar Mandi',
                'title' => 'Kamar mandi bersih',
                'src' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Fasilitas',
                'title' => 'Koridor dan akses',
                'src' => 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Fasilitas',
                'title' => 'Ruang bersama',
                'src' => 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Area Umum',
                'title' => 'Area santai',
                'src' => 'https://images.unsplash.com/photo-1600210492493-0946911123ea?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Area Umum',
                'title' => 'Fasad properti',
                'src' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=86',
            ),
        );
    }

    public function save_booking_request(array $payload)
    {
        if ($payload['fullname'] === '' || $payload['phone'] === '') {
            return FALSE;
        }

        $this->ensure_booking_table();
        if ( ! $this->table_exists('gh_booking_requests')) {
            return FALSE;
        }

        return $this->db->insert('gh_booking_requests', array(
            'fullname' => $payload['fullname'],
            'phone' => $payload['phone'],
            'room_interest' => $payload['room_interest'],
            'move_in_plan' => $payload['move_in_plan'],
            'message' => $payload['message'],
            'source' => 'landing',
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    private function ensure_booking_table()
    {
        if ($this->table_exists('gh_booking_requests')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `gh_booking_requests` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `fullname` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(40) NOT NULL,
            `room_interest` VARCHAR(150) DEFAULT NULL,
            `move_in_plan` VARCHAR(60) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `source` VARCHAR(40) NOT NULL DEFAULT 'landing',
            `status` ENUM('new','contacted','survey','converted','cancelled') NOT NULL DEFAULT 'new',
            `created_at` DATETIME NOT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status_created` (`status`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private function table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    private function get_room_assets($room_id)
    {
        if ( ! $this->table_exists('room_assets') || ! $this->table_exists('asset_types')) {
            return array();
        }

        $rows = $this->db
            ->select('at.label, ra.description, ra.asset_condition')
            ->from('room_assets ra')
            ->join('asset_types at', 'at.id = ra.asset_type_id', 'left')
            ->where('ra.room_id', $room_id)
            ->order_by('at.label', 'ASC')
            ->get()
            ->result_array();

        $facilities = array();
        foreach ($rows as $row) {
            $facilities[] = $row['label'] ? $row['label'] : $row['description'];
        }

        return array_values(array_filter($facilities));
    }

    private function normalize_room_detail(array $room)
    {
        $room['type_name'] = $room['type_name'] ? $room['type_name'] : 'Room';
        $room['type_description'] = $room['type_description'] ? $room['type_description'] : 'Kamar siap huni dengan fasilitas utama untuk tinggal bulanan.';
        $room['facilities'] = $room['facilities'] ? $room['facilities'] : array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam');
        $room['public_description'] = 'Kamar ' . $room['name'] . ' di ' . $room['code'] . ' cocok untuk penghuni yang ingin tempat tinggal rapi, tenang, dan mudah dijangkau.';
        $room['deposit_estimate'] = (int) $room['price'];
        return $room;
    }

    private function map_gh_rooms(array $rows)
    {
        $rooms = array();
        foreach ($rows as $row) {
            $rooms[] = array(
                'id' => (int) $row['id'],
                'code' => $row['property_code'],
                'name' => $row['room_label'],
                'price' => (int) $row['monthly_price'],
                'status' => ((int) $row['is_mess'] === 1 || ! $row['monthly_price']) ? 'maintenance' : 'available',
                'type_name' => $row['room_type'],
            );
        }

        return $rooms;
    }

    private function get_public_room_type_cards()
    {
        $rows = $this->db
            ->select('property_code', FALSE)
            ->select('room_type AS type_name', FALSE)
            ->select('COUNT(id) AS total_rooms', FALSE)
            ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
            ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS price', FALSE)
            ->select('MAX(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS max_price', FALSE)
            ->select('MIN(CASE WHEN deposit IS NOT NULL AND is_mess = 0 THEN deposit END) AS deposit_estimate', FALSE)
            ->from('gh_room_database')
            ->group_by('property_code, room_type')
            ->order_by('property_code', 'ASC')
            ->order_by('FIELD(room_type, "Standart", "Deluxe", "VIP")', '', FALSE)
            ->order_by('room_type', 'ASC')
            ->get()
            ->result_array();

        $cards = array();
        foreach ($rows as $index => $row) {
            $cards[] = array(
                'id' => $index + 1,
                'code' => $row['property_code'],
                'name' => $row['property_code'] . ' - ' . $row['type_name'],
                'slug' => $this->create_public_room_slug($row['property_code'], $row['type_name']),
                'price' => (int) $row['price'],
                'max_price' => (int) $row['max_price'],
                'status' => ((int) $row['available_count'] > 0) ? 'available' : 'maintenance',
                'type_name' => $row['type_name'],
                'total_rooms' => (int) $row['total_rooms'],
                'available_count' => (int) $row['available_count'],
                'deposit_estimate' => (int) $row['deposit_estimate'],
            );
        }

        return $cards ? $cards : $this->fallback_public_room_type_cards();
    }

    private function fallback_public_room_type_cards()
    {
        $cards = array();
        $fallback = array(
            array('code' => 'GH 1', 'type_name' => 'Standart', 'total_rooms' => 19, 'available_count' => 19, 'price' => 1500000, 'max_price' => 1500000),
            array('code' => 'GH 1', 'type_name' => 'Deluxe', 'total_rooms' => 3, 'available_count' => 3, 'price' => 1750000, 'max_price' => 1750000),
            array('code' => 'GH 1', 'type_name' => 'VIP', 'total_rooms' => 4, 'available_count' => 4, 'price' => 2000000, 'max_price' => 2000000),
            array('code' => 'GH 2', 'type_name' => 'Standart', 'total_rooms' => 17, 'available_count' => 17, 'price' => 2000000, 'max_price' => 2000000),
            array('code' => 'GH 2', 'type_name' => 'Deluxe', 'total_rooms' => 9, 'available_count' => 9, 'price' => 2500000, 'max_price' => 2500000),
            array('code' => 'GH 2', 'type_name' => 'VIP', 'total_rooms' => 8, 'available_count' => 8, 'price' => 3000000, 'max_price' => 3000000),
        );

        foreach ($fallback as $index => $row) {
            $row['id'] = $index + 1;
            $row['name'] = $row['code'] . ' - ' . $row['type_name'];
            $row['slug'] = $this->create_public_room_slug($row['code'], $row['type_name']);
            $row['status'] = ((int) $row['available_count'] > 0) ? 'available' : 'maintenance';
            $row['deposit_estimate'] = 0;
            $cards[] = $row;
        }

        return $cards;
    }

    private function map_public_type_rows(array $rows)
    {
        $cards = array();
        foreach ($rows as $index => $row) {
            $available = isset($row['available_count']) ? (int) $row['available_count'] : 0;
            $cards[] = array(
                'id' => $index + 1,
                'code' => isset($row['code']) ? $row['code'] : 'GUL HOUSE',
                'name' => (isset($row['code']) ? $row['code'] . ' - ' : 'Kamar ') . $row['type_name'],
                'slug' => $this->create_public_room_slug(isset($row['code']) ? $row['code'] : 'GUL HOUSE', $row['type_name']),
                'price' => (int) $row['price'],
                'max_price' => (int) $row['max_price'],
                'status' => $available > 0 ? 'available' : 'maintenance',
                'type_name' => $row['type_name'],
                'total_rooms' => (int) $row['total_rooms'],
                'available_count' => $available,
                'deposit_estimate' => 0,
            );
        }

        return $cards;
    }

    private function normalize_public_room_type_detail(array $card)
    {
        $priceLabel = 'Rp ' . number_format((int) $card['price'], 0, ',', '.');
        if ((int) $card['max_price'] > (int) $card['price']) {
            $priceLabel .= ' - Rp ' . number_format((int) $card['max_price'], 0, ',', '.');
        }

        $card['notes'] = 'Ringkasan publik per gedung dan tipe kamar.';
        $card['price_label'] = $priceLabel;
        $card['type_description'] = $card['name'] . ' memiliki ' . (int) $card['available_count'] . ' kamar tersedia dari ' . (int) $card['total_rooms'] . ' kamar. Harga dapat berbeda sesuai fasilitas dan kondisi kamar.';
        $card['public_description'] = 'Pilihan ' . $card['type_name'] . ' di ' . $card['code'] . ' untuk calon penghuni yang ingin survey kamar tanpa memilih nomor kamar terlebih dahulu.';
        $card['facilities'] = array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam');

        return $card;
    }

    private function create_public_room_slug($property, $type)
    {
        return $this->normalize_slug($property . '-' . $type);
    }

    private function normalize_slug($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    private function pick_featured_gh_rooms(array $rows, $limit)
    {
        $selected = array();
        $seenTypes = array();

        foreach ($rows as $row) {
            if (isset($seenTypes[$row['room_type']])) {
                continue;
            }

            $selected[] = $row;
            $seenTypes[$row['room_type']] = TRUE;

            if (count($selected) >= $limit) {
                return $this->map_gh_rooms($selected);
            }
        }

        foreach ($rows as $row) {
            if (count($selected) >= $limit) {
                break;
            }

            $key = $row['property_code'] . '|' . $row['room_code'] . '|' . $row['room_label'];
            $exists = FALSE;
            foreach ($selected as $selectedRow) {
                $selectedKey = $selectedRow['property_code'] . '|' . $selectedRow['room_code'] . '|' . $selectedRow['room_label'];
                if ($selectedKey === $key) {
                    $exists = TRUE;
                    break;
                }
            }

            if ( ! $exists) {
                $selected[] = $row;
            }
        }

        return $this->map_gh_rooms($selected);
    }

    private function normalize_gh_room_detail(array $row)
    {
        $room = array(
            'id' => (int) $row['id'],
            'code' => $row['property_code'],
            'name' => $row['room_label'],
            'price' => (int) $row['monthly_price'],
            'status' => ((int) $row['is_mess'] === 1 || ! $row['monthly_price']) ? 'maintenance' : 'available',
            'notes' => $row['floor_no'] ? 'Lantai ' . $row['floor_no'] . ' - kode kamar ' . $row['room_code'] : 'Kode kamar ' . $row['room_code'],
            'type_name' => $row['room_type'],
            'type_description' => 'Kamar ' . $row['room_type'] . ' dari database kamar Gul House. Harga dan deposit mengikuti data operasional terakhir.',
            'facilities' => array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam'),
            'public_description' => 'Kamar ' . $row['room_label'] . ' di ' . $row['property_code'] . ' cocok untuk penghuni yang ingin tempat tinggal rapi, tenang, dan mudah dijangkau.',
            'deposit_estimate' => $row['deposit'] ? (int) $row['deposit'] : (int) $row['monthly_price'],
        );

        return $room;
    }

    private function fallback_room_detail($id)
    {
        $fallback = array(
            'id' => $id,
            'code' => 'GH 2',
            'name' => 'GH 2 - Standart',
            'price' => 2000000,
            'status' => 'available',
            'notes' => '',
            'type_name' => 'Standart',
            'type_description' => 'Kamar siap huni untuk sewa bulanan dengan fasilitas utama yang terdata.',
            'facilities' => array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Kamar mandi', 'Akses 24 jam'),
        );

        return $this->normalize_room_detail($fallback);
    }

    private function fallback_room_types()
    {
        return array(
            array('name' => 'Standart', 'total_rooms' => 36, 'price_from' => 1500000, 'available_count' => 7),
            array('name' => 'Deluxe', 'total_rooms' => 12, 'price_from' => 1750000, 'available_count' => 2),
            array('name' => 'VIP', 'total_rooms' => 15, 'price_from' => 2000000, 'available_count' => 2),
            array('name' => 'Executive', 'total_rooms' => 1, 'price_from' => 3000000, 'available_count' => 0),
        );
    }
}
