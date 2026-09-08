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
                ->select('room_type AS name, COUNT(id) AS total_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS price_from', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_room_database')
                ->group_by('room_type')
                ->order_by('price_from', 'ASC')
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
            $rows = $this->db
                ->select('id, property_code, room_code, room_type, room_label, monthly_price, is_mess')
                ->from('gh_room_database')
                ->where('monthly_price IS NOT NULL', NULL, FALSE)
                ->where('is_mess', 0)
                ->order_by('property_code', 'ASC')
                ->order_by('FIELD(room_type, "VIP", "Deluxe", "Standart")', '', FALSE)
                ->order_by('room_name', 'ASC')
                ->order_by('room_code', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->pick_featured_gh_rooms($rows, 8);
            }
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, rt.name AS type_name')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->where('r.status', 'available')
                ->order_by('r.code', 'ASC')
                ->order_by('r.name', 'ASC')
                ->limit(6)
                ->get()
                ->result_array();

            if ($rows) {
                return $rows;
            }
        }

        return array(
            array('code' => 'GH 1', 'name' => 'Ephesus 217', 'type_name' => 'Standart', 'price' => 1500000, 'status' => 'available'),
            array('code' => 'GH 2', 'name' => 'Cappadocia 408', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('code' => 'GH 2', 'name' => 'Canakkale 202', 'type_name' => 'VIP', 'price' => 3000000, 'status' => 'available'),
        );
    }

    public function get_all_public_rooms()
    {
        if ($this->table_exists('gh_room_database')) {
            $rows = $this->db
                ->select('id, property_code, room_code, room_type, room_label, monthly_price, is_mess')
                ->from('gh_room_database')
                ->order_by('CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 0 ELSE 1 END', 'ASC', FALSE)
                ->order_by('monthly_price', 'ASC')
                ->order_by('property_code', 'ASC')
                ->order_by('room_code', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_gh_rooms($rows);
            }
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, rt.name AS type_name')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->order_by('FIELD(r.status, "available", "reserved", "maintenance", "occupied")', '', FALSE)
                ->order_by('r.price', 'ASC')
                ->order_by('r.code', 'ASC')
                ->limit(12)
                ->get()
                ->result_array();

            if ($rows) {
                return $rows;
            }
        }

        return array(
            array('id' => 7, 'code' => 'GH 1', 'name' => 'Ephesus 203', 'type_name' => 'Standart', 'price' => 1500000, 'status' => 'available'),
            array('id' => 19, 'code' => 'GH 1', 'name' => 'Ephesus 217', 'type_name' => 'Standart', 'price' => 1500000, 'status' => 'available'),
            array('id' => 54, 'code' => 'GH 2', 'name' => 'Cappadocia 408', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('id' => 58, 'code' => 'GH 2', 'name' => 'Cappadocia 412', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('id' => 31, 'code' => 'GH 2', 'name' => 'Canakkale 202', 'type_name' => 'VIP', 'price' => 3000000, 'status' => 'available'),
            array('id' => 3, 'code' => 'GH 1', 'name' => 'Alanya 102', 'type_name' => 'VIP', 'price' => 2000000, 'status' => 'occupied'),
        );
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
            array('id' => 2, 'code' => 'GH 1', 'name' => 'Alanya 101', 'type_name' => 'VIP', 'price' => 2000000, 'status' => 'available'),
            array('id' => 54, 'code' => 'GH 2', 'name' => 'Cappadocia 408', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('id' => 61, 'code' => 'GH 2', 'name' => 'Cappadocia 416', 'type_name' => 'VIP', 'price' => 3000000, 'status' => 'available'),
        );
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
            'name' => 'Cappadocia 408',
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
